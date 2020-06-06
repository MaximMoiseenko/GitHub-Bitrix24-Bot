<?php declare(strict_types=1);


namespace Demo;

/**
 * WARNING: This class was created  is only for demonstration.
 */

class GitHubImBot
{
	/**
	 * Debug mode switch flag
	 * @var bool
	 */
	protected $debug = true;

	/**
	 * Runtime configuration file full path
	 * @var string
	 */
	protected $configFile;

	/**
	 * Loaded runtime configuration data
	 * @var array
	 */
	protected $config = [];

	/**
	 * Log file full path
	 * @var string
	 */
	protected $logFile;

	/**
	 * Authorize data, received from event
	 * @var array
	 */
	protected $auth = [];

	/**
	 * Request headers
	 * @var array
	 */
	protected $headers = [];

	/**
	 * Backward action url
	 * @var string
	 */
	protected $handlerBackUrl;

	/**
	 * Event type
	 * @var string
	 */
	protected $event = '';

	/**
	 * Event handler
	 * @var \Closure
	 */
	protected $eventHandlers = [];

	/**
	 * Row request data from post body
	 * @var string
	 */
	protected $payloadRow;

	/**
	 * Parsed request data
	 * @var \stdClass|array
	 */
	protected $payload;


	/**
	 * Forwards event flow.
	 * @return $this
	 */
	public function init(): self
	{
		if ($this->configFile === null)
		{
			$this->configFile = __DIR__.'/.runtime.php';
		}
		$this->config = $this->getConfig();

		// oauth
		if (isset($this->config['AUTH']))
		{
			$this->auth = $this->config['AUTH'];
		}
		if (isset($_POST["auth"]))
		{
			$this->auth = $_POST["auth"];
		}
		// iframe mode
		elseif (isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'DEFAULT')
		{
			if (isset($_REQUEST["AUTH_ID"]))
			{
				$this->auth['access_token'] = htmlspecialchars($_REQUEST["AUTH_ID"]);
			}
			if (isset($_REQUEST["AUTH_EXPIRES"]))
			{
				$this->auth['expires_in'] = htmlspecialchars($_REQUEST["AUTH_EXPIRES"]);
			}
			if (isset($_REQUEST["APP_SID"]))
			{
				$this->auth['application_token'] = htmlspecialchars($_REQUEST["APP_SID"]);
			}
			if(isset($_REQUEST["REFRESH_ID"]))
			{
				$this->auth['refresh_token'] = htmlspecialchars($_REQUEST["REFRESH_ID"]);
			}
			if (isset($_REQUEST["DOMAIN"]))
			{
				$this->auth['domain'] = htmlspecialchars($_REQUEST["DOMAIN"]);
				$this->auth['client_endpoint'] =
					(strpos($_SERVER['HTTP_REFERER'],'https://')===0? 'https': 'http')."://".
					htmlspecialchars($_REQUEST["DOMAIN"]).'/rest/';
			}
		}

		if (function_exists('getallheaders'))
		{
			$this->headers = getallheaders();
		}
		elseif (!empty($_SERVER['HTTP_X_GITHUB_EVENT']))
		{
			$this->headers['X-GitHub-Event'] = $_SERVER['HTTP_X_GITHUB_EVENT'];
			$this->headers['X-Hub-Signature'] = $_SERVER['HTTP_X_HUB_SIGNATURE'];
			$this->headers['X-GitHub-Delivery'] = $_SERVER['HTTP_X_GITHUB_DELIVERY'];
			$this->headers['User-Agent'] = $_SERVER['HTTP_USER_AGENT'];
			$this->headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
		}

		// look for event id from bitrix
		if (!empty($_POST['event']))
		{
			$this->event = htmlspecialchars($_POST['event']);
		}
		// iframe mode
		elseif(isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] == 'DEFAULT')
		{
			$this->event = 'DEFAULT';
		}
		// look for event id from github
		elseif (!empty($this->headers['X-GitHub-Event']))
		{
			$this->event = 'GitHub-'. $this->headers['X-GitHub-Event'];
		}

		$this->log(
			[
				"_SERVER" => $_SERVER,
				"_GET" => $_GET,
				"_POST" => $_POST,
				"headers" => $this->headers,
				"payload" => $this->payload,
				"event" => $this->event,
			],
			"Hit params"
		);

		return $this;
	}

	//region Event handlers

	/**
	 * Forwards event flow.
	 * @return $this
	 */
	public function dispatchEvent(): self
	{
		if (strpos($this->event, 'GitHub-') === 0)
		{
			if (isset($this->eventHandlers[$this->event]))
			{
				$this->callEventHandler($this->event);
			}
			else
			{
				$this->callEventHandler('GitHub-*');
			}
		}
		else
		{
			$this->callEventHandler($this->event);
		}

		return $this;
	}

	/**
	 * Setup event handler.
	 *
	 * @param string $event
	 * @param \Closure $handler
	 *
	 * @return $this
	 */
	public function setEventHandler(string $event, \Closure $handler): self
	{
		$this->eventHandlers[$event] = $handler;

		return $this;
	}


	/**
	 * Call event handler.
	 * @param string $event
	 *
	 * @return void
	 */
	public function callEventHandler(string $event): void
	{
		if (isset($this->eventHandlers[$event]) && $this->eventHandlers[$event] instanceof \Closure)
		{
			/** @var \Closure $handler */
			$handler = $this->eventHandlers[$event]->bindTo($this, __CLASS__);
			$handler();
		}
	}

	/**
	 * URL handler for events.
	 * @return string
	 */
	public function getHandlerBackUrl(): string
	{
		if ($this->handlerBackUrl === null)
		{
			//
			$this->handlerBackUrl =
				($_SERVER['SERVER_PORT'] == 443 || isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]==="on" ? 'https': 'http')."://".
				$_SERVER['SERVER_NAME'].
				(in_array($_SERVER['SERVER_PORT'], Array(80, 443))?'':':'.$_SERVER['SERVER_PORT']).
				$_SERVER['SCRIPT_NAME'];

		}
		return $this->handlerBackUrl;
	}

	//endregion


	//region Payload

	/**
	 * Request payload row data.
	 * @return string|null
	 */
	public function getPayloadRow(): ?string
	{
		if ($this->payloadRow === null && $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$this->payloadRow = file_get_contents('php://input');
		}

		return $this->payloadRow;
	}

	/**
	 * Request payload.
	 * @return \stdClass|array|null
	 */
	public function getPayload()
	{
		if ($this->payload === null && $_SERVER['REQUEST_METHOD'] === 'POST')
		{
			if ($_SERVER['CONTENT_TYPE'] === 'application/json')
			{
				$payloadRow = $this->getPayloadRow();
				if ($payloadRow !== '')
				{
					$this->payload = json_decode($payloadRow);
				}
			}
			elseif (isset($_POST['data']))
			{
				$this->payload = $_POST['data'];
			}
		}

		return $this->payload;
	}

	//endregion


	//region Verify request

	/**
	 * Validate request params.
	 * @return $this
	 */
	public function verifyRequest(): self
	{
		if (
			//imbot
			$this->event === 'ONIMBOTMESSAGEADD' ||
			$this->event === 'ONIMCOMMANDADD' ||
			$this->event === 'ONIMBOTJOINCHAT' ||
			$this->event === 'ONIMBOTDELETE' ||
			//rest
			$this->event === 'ONAPPINSTALL' ||
			$this->event === 'ONAPPUNINSTALL' ||
			$this->event === 'ONAPPUPDATE'
		)
		{
			if ($this->verifyBitrixRequest() !== true)
			{
				$this->fire500();
			}
		}
		elseif (
			strpos($this->event, 'GitHub-') === 0
		)
		{
			if ($this->verifyGitHubRequest() !== true)
			{
				$this->fire500();
			}
		}
		//	$this->event === 'DEFAULT'

		return $this;
	}

	/**
	 * Checks User-Agent
	 * @return bool
	 */
	protected function verifyBitrixRequest(): bool
	{
		//$valid = true;
		//if ($this->debug !== true)
		{
			$valid =
			(
				strpos($this->headers['User-Agent'], 'Bitrix') === 0 && // Bitrix24 Webhook Engine
				!empty($this->auth['access_token']) &&
				!empty($this->auth['client_endpoint']) &&
				!empty($this->auth['application_token'])
				//!empty($this->auth['refresh_token']) &&
			);

			if (!$valid)
			{
				$this->log("Verification Bitrix Request not passed");
			}
		}

		return $valid;
	}

	/**
	 * Checks X-Hub-Signature and User-Agent
	 * @return bool
	 */
	protected function verifyGitHubRequest(): bool
	{
		$valid = false;
		if (
			strpos($this->headers['User-Agent'], 'GitHub-Hookshot/') === 0 &&
			!empty($this->headers['X-Hub-Signature']) &&
			!empty($this->headers['X-GitHub-Delivery']) &&
			!empty($this->headers['X-GitHub-Event'])
		){

			$signature = 'sha1=' . \hash_hmac('sha1', $this->getPayloadRow(), \GITHUB_SECRET_TOKEN, false);

			$valid = ($signature === $this->headers['X-Hub-Signature']);
		}

		if (!$valid)
		{
			$this->log("Verification GitHub Request not passed");
		}

		return $valid;
	}

	//endregion


	//region Rest & OAuth

	/**
	 * Send rest query to Bitrix24.
	 *
	 * @param $method - Rest method, ex: methods
	 * @param array $params - Method params, ex: Array()
	 * @param array $auth - Authorize data, received from event
	 * @return mixed
	 */
	public function restCommand($method, array $params = [], array $auth = [])
	{
		\Demo\RestClient::$auth = $this->auth;

		$result = \Demo\RestClient::call($method, $params);

		$this->auth = \Demo\RestClient::$auth;

		return $result;
	}

	//endregion


	//region Config

	/**
	 * @param string $confFile
	 *
	 * @return $this
	 */
	public function setConfigFile(string $confFile): self
	{
		$this->configFile = $confFile;
		return $this;
	}

	/**
	 * @return self
	 */
	public function setParam($name, $value): self
	{
		$this->config[$name] = $value;

		return $this;
	}

	/**
	 * Loads application configuration.
	 * @return array
	 */
	public function getConfig(): array
	{
		if (file_exists($this->configFile))
		{
			return include $this->configFile;
		}
		return [];
	}

	/**
	 * Saves application configuration.
	 *
	 * @param array $appConfig
	 * @return bool
	 */
	function saveConfig(array $appConfig): bool
	{
		$config = "<?php\n";
		$config .= "return ". var_export($appConfig, true). ";\n";

		return file_put_contents($this->configFile, $config) !== false;
	}

	//endregion

	//region debug & log

	/**
	 * @param bool $mode
	 * @return $this
	 */
	public function debugMode(bool $mode): self
	{
		$this->debug = $mode;
		return $this;
	}

	/**
	 * @param string $logFile
	 *
	 * @return $this
	 */
	public function setLogFile(string $logFile): self
	{
		$this->logFile = $logFile;
		return $this;
	}

	/**
	 * Write data to log file. (by default disabled)
	 * WARNING: this method is only created for demonstration, never store log file in public folder
	 *
	 * @param mixed $data
	 * @param string $title
	 * @return self
	 */
	public function log($data, $title = ''): self
	{
		if ($this->debug && $this->logFile !== null)
		{
			$log =
				"\n------------------------\n".
				date("Y.m.d G:i:s")."\n".
				(strlen($title) > 0 ? $title."\n" : '').
				print_r($data, true);

			file_put_contents($this->logFile, $log, FILE_APPEND);
		}
		return $this;
	}

	//endregion

	//region Answer

	public function fire404(): void
	{
		header('HTTP/1.0 404 Not Found', true);
		echo "<html><head><title>File Not Found<title></head><body><h1>404 - File Not Found</h1></body></html>";
		exit;
	}

	public function fire500(): void
	{
		header('HTTP/1.1 500 Internal Server Error', true);
		echo "<html><head><title>Internal Server Error<title></head><body><h1>500 - Internal Server Error</h1></body></html>";
		exit;
	}

	//endregion
}