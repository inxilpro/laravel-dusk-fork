<?php

namespace Laravel\Dusk\Driver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class AsyncWebDriver extends RemoteWebDriver
{
    /**
     * @param  string  $selenium_server_url
     * @param  DesiredCapabilities|array|null  $desired_capabilities
     * @param  int|null  $connection_timeout_in_ms
     * @param  int|null  $request_timeout_in_ms
     * @param  string|null  $http_proxy
     * @param  int|null  $http_proxy_port
     * @param  DesiredCapabilities|null  $required_capabilities
     * @return AsyncWebDriver
     */
    public static function create(
        $selenium_server_url = 'http://localhost:4444/wd/hub',
        $desired_capabilities = null,
        $connection_timeout_in_ms = null,
        $request_timeout_in_ms = null,
        $http_proxy = null,
        $http_proxy_port = null,
        DesiredCapabilities $required_capabilities = null,
    ) {
        $factory = new AsyncWebDriverFactory(
            $selenium_server_url, $desired_capabilities, $connection_timeout_in_ms,
            $request_timeout_in_ms, $http_proxy, $http_proxy_port, $required_capabilities,
        );

        return new AsyncWebDriver(...$factory());
    }
}
