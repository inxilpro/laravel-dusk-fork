<?php

namespace Laravel\Dusk\Http;

use Illuminate\Contracts\Routing\UrlGenerator as UrlGeneratorContract;
use Illuminate\Support\Traits\ForwardsCalls;

/**
 * @method string query(string $path, array $query = [], mixed $extra = [], bool|null $secure = null)
 */
class UrlGenerator implements UrlGeneratorContract
{
    use ForwardsCalls;

    public function __construct(
        protected string $endpoint,
        protected string $appHost,
        protected UrlGeneratorContract $url,
    ) {
    }

    public function current()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function previous($fallback = false)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function to($path, $extra = [], $secure = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function secure($path, $parameters = [])
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function asset($path, $secure = null)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function route($name, $parameters = [], $absolute = true)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function signedRoute($name, $parameters = [], $expiration = null, $absolute = true)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function temporarySignedRoute($name, $expiration, $parameters = [], $absolute = true)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function action($action, $parameters = [], $absolute = true)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getRootControllerNamespace()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setRootControllerNamespace($rootNamespace)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function proxy(string $url): string
    {
        // TODO: Provide a way to register a callback that allows for more complex matching

        $host = parse_url($url, PHP_URL_HOST);

        return $host === $this->appHost
            ? $this->endpoint.'?url='.urlencode($url)
            : $url;
    }

    public function __call(string $name, array $arguments)
    {
        $result = $this->forwardDecoratedCallTo($this->url, $name, $arguments);

        if (is_string($result) && filter_var($result, FILTER_VALIDATE_URL)) {
            return $this->proxy($result);
        }

        return $result;
    }
}
