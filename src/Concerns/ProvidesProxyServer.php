<?php

namespace Laravel\Dusk\Concerns;

use Illuminate\Routing\UrlGenerator;
use Laravel\Dusk\Http\ProxyServer;
use PHPUnit\Framework\Attributes\Before;

trait ProvidesProxyServer
{
    #[Before]
    public function setUpProvidesProxyServer(): void
    {
        $this->afterApplicationCreated(function () {
            $proxy = $this->app->make(ProxyServer::class)->listen();
            $this->app->make(UrlGenerator::class)->forceRootUrl($proxy->url());
        });

        $this->beforeApplicationDestroyed(function () {
            $this->app->make(ProxyServer::class)->flush();
            $this->app->make(UrlGenerator::class)->forceRootUrl(null);
        });
    }
}
