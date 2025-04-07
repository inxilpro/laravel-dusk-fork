<?php

namespace Laravel\Dusk\Driver;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\HttpCommandExecutor;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\WebDriverCommand;

class AsyncWebDriverFactory
{
    protected DesiredCapabilities $desired_capabilities;

    protected DesiredCapabilities $session_capabilities;

    protected bool $is_w3c_compliant;

    protected string $session_id;

    /**
     * Construct a new factory instance.
     */
	public function __construct(
		protected string $selenium_server_url = 'http://localhost:9515',
        DesiredCapabilities|array|null $desired_capabilities = null,
        protected ?int $connection_timeout_in_ms = null,
        protected ?int $request_timeout_in_ms = null,
        protected ?string $http_proxy = null,
        protected ?int $http_proxy_port = null,
        protected ?DesiredCapabilities $required_capabilities = null,
	) {
		$this->selenium_server_url = rtrim($this->selenium_server_url, '/');

        $this->desired_capabilities = match (true) {
            $desired_capabilities instanceof DesiredCapabilities => $desired_capabilities,
            is_array($desired_capabilities) => new DesiredCapabilities($desired_capabilities),
            default => new DesiredCapabilities(),
        };
	}

    /**
     * Configure AsyncWebDriver parameters via factory.
     *
     * @return array{0: AsyncCommandExecutor, 1: string, 2: DesiredCapabilities, 3: bool}
     */
    public function __invoke(): array
    {
        $this->initializeSession();

        $executor = $this->configureExecutor(
            new AsyncCommandExecutor($this->selenium_server_url, $this->http_proxy, $this->http_proxy_port)
        );

        return [$executor, $this->session_id, $this->session_capabilities, $this->is_w3c_compliant];
    }

    /**
     * Initialize the web driver session synchronously.
     *
     * @return void
     */
    protected function initializeSession(): void
    {
        $sync_executor = $this->configureExecutor(
            new HttpCommandExecutor($this->selenium_server_url, $this->http_proxy, $this->http_proxy_port)
        );

        $response = $sync_executor->execute(WebDriverCommand::newSession($this->parameters()));
        $value = $response->getValue();

        $this->is_w3c_compliant = isset($value['capabilities']);

        $this->session_capabilities = $this->is_w3c_compliant
            ? DesiredCapabilities::createFromW3cCapabilities($value['capabilities'])
            : new DesiredCapabilities($value['capabilities']);

        $this->session_id = $response->getSessionID();
    }

    /**
     * Apply timeouts/configuration to the command executor.
     *
     * @param  HttpCommandExecutor  $executor
     * @return HttpCommandExecutor
     */
	protected function configureExecutor(HttpCommandExecutor $executor): HttpCommandExecutor {
		if ($this->connection_timeout_in_ms !== null) {
			$executor->setConnectionTimeout($this->connection_timeout_in_ms);
		}

		if ($this->request_timeout_in_ms !== null) {
			$executor->setRequestTimeout($this->request_timeout_in_ms);
		}

		return $executor;
	}

    /**
     * Convert desired/required capabilities into session parameters.
     *
     * @return array
     */
	protected function parameters(): array {
		// Set W3C parameters first
		$parameters = [
            'capabilities' => [
                'firstMatch' => [
                    (object) $this->desired_capabilities->toW3cCompatibleArray(),
                ],
            ],
        ];

		// Handle *required* params
		if ($this->required_capabilities && count($this->required_capabilities->toArray())) {
			$parameters['capabilities']['alwaysMatch'] = (object) $this->required_capabilities->toW3cCompatibleArray();
			$this->desired_capabilities->setCapability('requiredCapabilities', (object) $this->required_capabilities->toArray());
		}

		$parameters['desiredCapabilities'] = (object) $this->desired_capabilities->toArray();

		return $parameters;
	}
}
