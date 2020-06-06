<?php declare(strict_types=1);


namespace Demo;

/**
 * WARNING: This class was created  is only for demonstration.
 */

class GitHubImBot
{
	/** @var bool */
	protected $debug = true;

	/** @var string  */
	protected $configFile;

	/** @var array */
	protected $config = [];

	/** @var string  */
	protected $logFile;

	/** @var array */
	protected $auth = [];

	/** @var array */
	protected $headers = [];

	/** @var string */
	protected $handlerBackUrl;

	/** @var string */
	protected $event = '';

	/** @var \Closure */
	protected $eventHandler;

	/** @var string */
	protected $payloadRow;

	/** @var \stdClass|array */
	protected $payload;


	public function __construct()
	{
		$this->configFile = __DIR__.'/.config.php';
	}


	/**
	 * Forwards event flow.
	 * @return $this
	 */
	public function init(): self
	{
		$this->config = $this->getConfig();

		// oauth
		if(isset($_POST["auth"]))
		{
			$this->auth = $_POST["auth"];
		}

		// iframe mode
		elseif(isset($_REQUEST['PLACEMENT']) && $_REQUEST['PLACEMENT'] === 'DEFAULT')
		{
			if(isset($_REQUEST["AUTH_ID"]))
			{
				$this->auth['access_token'] = htmlspecialchars($_REQUEST["AUTH_ID"]);
			}
			if(isset($_REQUEST["AUTH_EXPIRES"]))
			{
				$this->auth['expires_in'] = htmlspecialchars($_REQUEST["AUTH_EXPIRES"]);
			}
			if(isset($_REQUEST["APP_SID"]))
			{
				$this->auth['application_token'] = htmlspecialchars($_REQUEST["APP_SID"]);
			}
			if(isset($_REQUEST["REFRESH_ID"]))
			{
				$this->auth['refresh_token'] = htmlspecialchars($_REQUEST["REFRESH_ID"]);
			}
			if(isset($_REQUEST["DOMAIN"]))
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
			if (isset($this->eventHandler[$this->event]))
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
		$this->eventHandler[$event] = $handler;

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
		if (isset($this->eventHandler[$event]) && $this->eventHandler[$event] instanceof \Closure)
		{
			/** @var \Closure $handler */
			$handler = $this->eventHandler[$event]->bindTo($this, __CLASS__);
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
	 * @param boolean $authRefresh - If authorize is expired, refresh token
	 * @return mixed
	 */
	public function restCommand($method, array $params = [], array $auth = [], $authRefresh = true)
	{
		$queryUrl = $auth["client_endpoint"]. $method. '.json';

		$queryData = http_build_query(array_merge($params, array("auth" => $auth["access_token"])));

		$this->log(
			Array('URL' => $queryUrl, 'PARAMS' => array_merge($params, array("auth" => $auth["access_token"]))),
			'Rest command: '.$method
		);

		$curl = curl_init();

		curl_setopt_array($curl, array(
			\CURLOPT_POST => 1,
			\CURLOPT_HEADER => 0,
			\CURLOPT_RETURNTRANSFER => 1,
			\CURLOPT_SSL_VERIFYPEER => ($this->debug !== true),
			\CURLOPT_URL => $queryUrl,
			\CURLOPT_POSTFIELDS => $queryData,
		));

		$result = curl_exec($curl);

		if($result === false)
		{
			$this->log(curl_error($curl), 'Http query error');
			curl_close($curl);
		}
		else
		{
			curl_close($curl);

			$result = json_decode($result, true);

			$this->log($result,'Rest response');

			if ($authRefresh && isset($result['error']) && in_array($result['error'], array('expired_token', 'invalid_token')))
			{
				$auth = $this->restAuth($auth);
				if ($auth)
				{
					$result = $this->restCommand($method, $params, $auth, false);
				}
			}
		}

		return $result;
	}

	/**
	 * Get new authorize data if you authorize is expire.
	 *
	 * @param array $auth - Authorize data, received from event
	 * @return bool|mixed
	 */
	public function restAuth($auth)
	{
		if(!isset($auth['refresh_token']))
		{
			return false;
		}

		$queryData = http_build_query($queryParams = array(
			'grant_type' => 'refresh_token',
			'client_id' => \C_REST_CLIENT_ID,
			'client_secret' => \C_REST_CLIENT_SECRET,
			'refresh_token' => $auth['refresh_token'],
		));

		$this->log(Array('URL' => 'https://oauth.bitrix.info/oauth/token/', 'PARAMS' => $queryParams), 'Request auth data');

		$curl = curl_init();

		curl_setopt_array($curl, array(
			\CURLOPT_HEADER => 0,
			\CURLOPT_RETURNTRANSFER => 1,
			\CURLOPT_SSL_VERIFYPEER => ($this->debug !== true),
			\CURLOPT_URL => 'https://oauth.bitrix.info/oauth/token/'. '?'. $queryData,
		));

		$result = curl_exec($curl);

		if($result === false)
		{
			$this->log(curl_error($curl), 'Http query error');
			curl_close($curl);
		}
		else
		{
			curl_close($curl);

			$result = json_decode($result, true);

			$this->log($result,'Response auth');

			if (!isset($result['error']))
			{
				$result['application_token'] = $auth['application_token'];
				$this->config['AUTH'] = $result;

				$this->saveConfig($this->config);
			}
			else
			{
				$result = false;
			}
		}

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