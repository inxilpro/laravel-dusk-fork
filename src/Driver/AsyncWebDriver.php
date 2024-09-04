<?php

namespace Laravel\Dusk\Driver;

use Facebook\WebDriver\Remote\HttpCommandExecutor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverCapabilities;

class AsyncWebDriver extends RemoteWebDriver
{
    // The RemoteWebDriver constructor is private, so to allow us to
    // create a new instance using our factory class, we need to extend
    // the class and open up the constructor.
    public function __construct(
        HttpCommandExecutor $executor,
        string $session_id,
        WebDriverCapabilities $capabilities,
        $is_w3c_compliant = false
    ) {
        parent::__construct($executor, $session_id, $capabilities, $is_w3c_compliant);
    }
}
