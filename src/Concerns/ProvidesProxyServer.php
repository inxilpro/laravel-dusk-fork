<?php

namespace Laravel\Dusk\Concerns;

use Closure;
use Exception;
use Illuminate\Support\Collection;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Http\ProxyServer;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Runner\Version;
use ReflectionFunction;
use Throwable;

trait ProvidesProxyServer
{
    #[Before]
    public function setUpProvidesProxyServer(): void
    {
        $this->afterApplicationCreated(fn() => app(ProxyServer::class)->listen());
        $this->beforeApplicationDestroyed(fn() => app(ProxyServer::class)->flush());
    }
}
