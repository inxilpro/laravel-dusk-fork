<?php

namespace Laravel\Dusk\Http;

use Exception;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Http\HttpServer as ReactHttpServer;
use React\Http\Message\Response;
use React\Http\Middleware\LimitConcurrentRequestsMiddleware;
use React\Http\Middleware\RequestBodyBufferMiddleware;
use React\Http\Middleware\RequestBodyParserMiddleware;
use React\Http\Middleware\StreamingRequestMiddleware;
use React\Promise\Promise;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use React\Stream\ReadableResourceStream;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\Mime\MimeTypes;
use Throwable;

class ProxyServer
{
    protected SocketServer $socket;

    protected int $in_flight = 0;

    protected array $connections = [];

    protected bool $flushing = false;

    public function __construct(
        protected HttpKernel $kernel,
        protected LoopInterface $loop,
        protected string $host = '127.0.0.1',
        protected int $port = 8089,
    ) {
    }

    public function listen(): static
    {
        $this->socket = new SocketServer("{$this->host}:{$this->port}", [], $this->loop);

        $this->socket->on('connection', function(ConnectionInterface $connection) {
            $this->connections[] = (object) [
                'active'     => false,
                'connection' => $connection,
            ];
        });

        $server = new ReactHttpServer($this->loop, new StreamingRequestMiddleware(),
            new LimitConcurrentRequestsMiddleware(100), new RequestBodyBufferMiddleware(32 * 1024 * 1024), // 32 MB
            new RequestBodyParserMiddleware(32 * 1024 * 1024, 100), // 32 MB (these need to be configurable)
            $this->handleRequest(...));

        $server->on('error', function(Exception $exception) {
            throw $exception;
        });

        $server->listen($this->socket);

        return $this;
    }

    protected function handleRequest(ServerRequestInterface $psr_request): Promise|Response
    {
        // If this is just a request for a static asset, just stream that content back
        if ($static_response = $this->staticResponse($psr_request)) {
            return $static_response;
        }

        $promise = $this->runRequestThroughKernel(
            Request::createFromBase((new HttpFoundationFactory())->createRequest($psr_request))
        );

        // Handle exception
        $promise->catch(function(Throwable $exception) {
            return Response::plaintext($exception->getMessage()."\n".$exception->getTraceAsString())
                ->withStatus(Response::STATUS_INTERNAL_SERVER_ERROR);
        });

        return $promise;
    }

    protected function runRequestThroughKernel(Request $request): Promise
    {
        $this->in_flight++;

        return new Promise(function(callable $resolve) use ($request) {
            $this->loop->futureTick(fn() => $this->loop->stop());

            $response = $this->kernel->handle($request);

            $this->kernel->terminate($request, $response);

            $resolve(new Response(status: $response->getStatusCode(), headers: value(function() use ($response) {
                $headers = $response->headers->all();

                if ( ! empty($cookies = $response->headers->getCookies())) {
                    $headers['Set-Cookie'] = [];
                    foreach ($cookies as $cookie) {
                        $headers['Set-Cookie'][] = $cookie->__toString();
                    }
                }

                return $headers;
            }), body: value(function() use ($response) {
                ob_start();
                $response->sendContent();

                return ob_get_clean();
            }), version: $response->getProtocolVersion()));

            $this->in_flight--;
        });
    }

    protected function staticResponse(ServerRequestInterface $psr_request): ?Promise
    {
        $path = $psr_request->getUri()->getPath();

        if (Str::contains($path, '../')) {
            return null;
        }

        $filepath = public_path($path);

        if (file_exists($filepath) && ! is_dir($filepath)) {
            $this->in_flight++;

            return new Promise(function(callable $resolve) use ($filepath) {
                $resolve(new Response(status: 200, headers: [
                            'Content-Type' => match (pathinfo($filepath, PATHINFO_EXTENSION)) {
                                'css' => 'text/css',
                                'js' => 'application/javascript',
                                'png' => 'image/png',
                                'jpg', 'jpeg' => 'image/jpeg',
                                'svg' => 'image/svg+xml',
                                'woff' => 'font/woff',
                                'woff2' => 'font/woff2',
                                'eot' => 'application/vnd.ms-fontobject',
                                'ttf' => 'font/ttf',
                                default => (new MimeTypes())->guessMimeType($filepath),
                            },
                        ], body: new ReadableResourceStream(fopen($filepath, 'r'))));
                $this->in_flight--;
            });
        }

        return null;
    }

    public function flush(): void
    {
        if ($this->flushing) {
            return;
        }

        $this->flushing = true;

        $this->loop->addPeriodicTimer(0.1, function(TimerInterface $timer) {
            if ($this->in_flight === 0) {
                foreach ($this->connections as $connection) {
                    $connection->connection->close();
                }
                $this->socket->close();
                $this->loop->cancelTimer($timer);
            }
        });

        $this->loop->run();
    }

    public function __destruct()
    {
        $this->flush();
    }
}
