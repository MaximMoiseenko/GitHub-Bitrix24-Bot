<?php declare(strict_types=1);

/**
 *
 * GitHub Webhooks
 * https://developer.github.com/webhooks/
 *
 * Bitrix Rest manual
 * https://dev.1c-bitrix.ru/rest_help/
 *
 * Bitrix IM bot platform learning course
 * https://bitrix24.ru/~bot
 *
 */

require "./.settings.php";
require "./vendor/autoload.php";
require "./vendor/bitrix-tools/crest/src/crest.php";

error_reporting(E_ALL);


(new \Demo\GitHubImBot)
	// setup runtime data file
	->setConfigFile(__DIR__ . '/.runtime.php')

	// setup debug and log
	->debugMode(false)
	->setLogFile(__DIR__ . '/logs/rest.txt')


	// receive event "Application install"
	->setEventHandler('ONAPPINSTALL', function(){
		/** @var \Demo\GitHubImBot $this */

		// If your application supports different localizations
		// use $this->getPayload()['LANGUAGE_ID'] to load correct localization


		// register new bot
		$result = $this->restCommand('imbot.register', array(
			'CODE' => 'GitHubBotNotifier',
			'TYPE' => 'B',
			'EVENT_MESSAGE_ADD' => $this->getHandlerBackUrl(),
			'EVENT_WELCOME_MESSAGE' => $this->getHandlerBackUrl(),
			'EVENT_BOT_DELETE' => $this->getHandlerBackUrl(),
			'OPENLINE' => 'Y', // this flag only for Open Channel mode http://bitrix24.ru/~bot-itr
			'PROPERTIES' => array(
				'NAME' => 'GitHub Bot Notifier',
				'COLOR' => 'BLACK',
				'EMAIL' => 'test@test.ru',
				'PERSONAL_BIRTHDAY' => '2020-06-09',
				'WORK_POSITION' => 'Notifier bot',
				'PERSONAL_WWW' => 'https://github.com',
				'PERSONAL_GENDER' => 'M',
				'PERSONAL_PHOTO' => base64_encode(file_get_contents(__DIR__.'/github.png')),
			)
		));

		if (isset($result['result']) && (int)$result['result'] > 0)
		{
			$botId = (int)$result['result'];

			// save params
			$this->config['BOT_ID'] = $botId;
			$this->config['LANGUAGE_ID'] = $this->getPayload()['LANGUAGE_ID'];
			$this->config['VERSION'] = $this->getPayload()['VERSION'];
			$this->config['AUTH'] = $this->auth;

			$this->log([$botId, $result], 'Bot registered');
		}
		else
		{
			$this->log($result, 'Got error');
			return;
		}


		// subscribe command
		$result = $this->restCommand('imbot.command.register', Array(
			'BOT_ID' => $botId,
			'COMMAND' => 'subscribe',
			'COMMON' => 'Y',
			'HIDDEN' => 'N',
			'EXTRANET_SUPPORT' => 'N',
			'LANG' => Array(
				Array('LANGUAGE_ID' => 'en', 'TITLE' => 'Subscribe command'),
			),
			'EVENT_COMMAND_ADD' => $this->getHandlerBackUrl(),
		));

		if (isset($result['result']) && (int)$result['result'] > 0)
		{
			$commandSubscribeId = $result['result'];

			$this->config['COMMAND_SUBSCRIBE'] = $commandSubscribeId;

			$this->log([$commandSubscribeId, $result], 'Command subscribe registered');
		}
		else
		{
			$this->log($result, 'Got error');
		}


		// unsubscribe command
		$result = $this->restCommand('imbot.command.register', Array(
			'BOT_ID' => $botId,
			'COMMAND' => 'unsubscribe',
			'COMMON' => 'Y',
			'HIDDEN' => 'N',
			'EXTRANET_SUPPORT' => 'N',
			'LANG' => Array(
				Array('LANGUAGE_ID' => 'en', 'TITLE' => 'Unsubscribe command'),
			),
			'EVENT_COMMAND_ADD' => $this->getHandlerBackUrl(),
		));

		if (isset($result['result']) && (int)$result['result'] > 0)
		{
			$commandSubscribeId = $result['result'];

			$this->config['COMMAND_SUBSCRIBE'] = $commandSubscribeId;

			$this->log([$commandSubscribeId, $result], 'Command unsubscribe registered');
		}
		else
		{
			$this->log($result, 'Got error');
		}


		// help command
		$result = $this->restCommand('imbot.command.register', [
			'BOT_ID' => $botId,
			'COMMAND' => 'help',
			'COMMON' => 'N',
			'HIDDEN' => 'N',
			'EXTRANET_SUPPORT' => 'N',
			'LANG' => Array(
				Array('LANGUAGE_ID' => 'en', 'TITLE' => 'Get help message', 'PARAMS' => 'some text'),
			),
			'EVENT_COMMAND_ADD' => $this->getHandlerBackUrl(),
		]);

		if (isset($result['result']) && (int)$result['result'] > 0)
		{
			$commandHelpId = $result['result'];

			$this->config['COMMAND_HELP'] = $commandHelpId;

			$this->log([$commandHelpId, $result], 'Command help registered');
		}
		else
		{
			$this->log($result, 'Got error');
		}


		/**
		 * rest event bind
		 */
		$result = $this->restCommand('event.bind', [
			'EVENT' => 'OnAppUpdate',
			'HANDLER' => $this->getHandlerBackUrl()
		]);

		if (isset($result['result']) && $result['result'] === true)
		{
			$this->log($result, 'Event OnAppUpdate registered');
		}
		else
		{
			$this->log($result, 'Got error');
		}


		$this->saveConfig($this->config);
	})


	// receive event "Application uninstall"
	->setEventHandler('ONAPPUNINSTALL', function (){
		/** @var \Demo\GitHubImBot $this */

		// check the event - authorize this event or not
		if (!isset($this->config['AUTH']))
		{
			return;
		}

		// unset application id
		unset($this->config['AUTH']);

		// save params
		$this->saveConfig($this->config);

		// write debug log
		$this->log($this->getPayload()['application_token'], 'ImBot unregistered');
	})


	// receive event "Application install"
	->setEventHandler('ONAPPUPDATE', function (){
		/** @var \Demo\GitHubImBot $this */

		// check the event - authorize this event or not
		if (!isset($this->config['AUTH']))
		{
			return;
		}

		// got new version
		// do some logic in update event for VERSION
		if ($this->getPayload()['VERSION'] > $this->config['VERSION'])
		{
			// fix new version
			$this->config['VERSION'] = $this->getPayload()['VERSION'];

			// save params
			$this->saveConfig($this->config);


			/*
			// You can execute any method RestAPI, BotAPI or ChatAPI, for example delete or add a new command to the bot

			$result = $this->restCommand('...', Array(
				'...' => '...',
			), $this->auth);

			// For example delete "subscribe" command:

			$result = $this->restCommand('imbot.command.unregister', Array(
				'COMMAND_ID' => $this->config['COMMAND_ECHO'],
			), $this->auth);

			// send answer message
			$result = $this->restCommand('app.info', [], $this->auth);
			*/
		}

		$this->log($this->getPayload()['VERSION'], 'ImBot update event');
	})


	// receive event "open private dialog with bot" or "join bot to group chat"
	->setEventHandler('ONIMBOTJOINCHAT', function (){
		/** @var \Demo\GitHubImBot $this */

		// check the event - authorize this event or not
		if (!isset($this->config['AUTH']))
		{
			return;
		}

		$dialogId = $this->getPayload()['PARAMS']['DIALOG_ID'];
		$isSubscribed = ($this->config['SUBSCRIPTIONS'][$dialogId] === true);

		// send help message how to use chat-bot. For private chat and for group chat need send different instructions.
		$result = $this->restCommand('imbot.message.add', Array(
			"DIALOG_ID" => $dialogId,
			"MESSAGE" => "I'm GitHub notifier bot ;)[br] I can forward you notification from GitHub.",
			"KEYBOARD" => [
				["TEXT" => "Help", "COMMAND" => "help", "DISPLAY" => "LINE",],
				$isSubscribed ?
					["TEXT" => "Unsubscribe", "COMMAND" => "unsubscribe", "DISPLAY" => "LINE",
					 "BG_COLOR" => "#2a4c7c", "TEXT_COLOR" => "#fff",]
					:
					["TEXT" => "Subscribe", "COMMAND" => "subscribe", "DISPLAY" => "LINE",
					 "BG_COLOR" => "#29619b", "TEXT_COLOR" => "#fff",],
			]
		));

		// save params
		$this->saveConfig($this->config);

		$this->log($result, 'ImBot joined chat');
	})


	// receive event "delete chat-bot"
	->setEventHandler('ONIMBOTDELETE', function (){
		/** @var \Demo\GitHubImBot $this */

		// check the event - authorize this event or not
		if (!isset($this->config['AUTH']))
		{
			return;
		}

		$botId = $this->config['BOT_ID'];

		/**
		 * Unregister Imbot
		 */
		$result = $this->restCommand('imbot.unregister', ['BOT_ID' => $botId]);

		if (isset($result['result']) && $result['result'] === true)
		{
			$this->log($result, 'Imbot registered');
		}
		else
		{
			$this->log($result, 'Got error');
		}


		// unset application id
		unset(
			$this->config['BOT_ID'],
			$this->config['SUBSCRIPTIONS']
		);

		// save params
		$this->saveConfig($this->config);

		// write debug log
		$this->log($botId, 'ImBot unregistered');
	})


	// receive event "new message for bot"
	->setEventHandler('ONIMBOTMESSAGEADD', function (){
		/** @var \Demo\GitHubImBot $this */

		// check the event - authorize this event or not
		if (!isset($this->config['AUTH']))
		{
			return;
		}

		list($message) = explode(" ", $this->getPayload()['PARAMS']['MESSAGE']);

		$dialogId = $this->getPayload()['PARAMS']['DIALOG_ID'];


		$this->restCommand('imbot.message.add', Array(
			"DIALOG_ID" => $dialogId,
			"MESSAGE" => "I'm just a bot! Do you need help?",
			"KEYBOARD" => [
				["TEXT" => "Help", "COMMAND" => "help"],
			]
		));

		$this->log($message, 'ImBot got message');
	})


	// receive event "new command for bot"
	->setEventHandler('ONIMCOMMANDADD', function (){
		/** @var \Demo\GitHubImBot $this */

		// check the event - authorize this event or not
		if (!isset($this->config['AUTH']))
		{
			return;
		}

		foreach ($this->getPayload()['COMMAND'] as $command)
		{
			// write debug log
			$this->log($command, 'ImBot got command');

			$dialogId = $this->getPayload()['PARAMS']['DIALOG_ID'];
			$isSubscribed = ($this->config['SUBSCRIPTIONS'][$dialogId] === true);

			if ($command['COMMAND'] === 'help')
			{
				$this->restCommand('imbot.command.answer', Array(
					"COMMAND_ID" => $command['COMMAND_ID'],
					"MESSAGE_ID" => $command['MESSAGE_ID'],
					"MESSAGE" =>
						"I'm GitHub notifier bot ;)[br] I can forward you notification from GitHub.[br] ".
						(
						$isSubscribed ?
							"You subscription is activate now. [send=/unsubscribe]Unsubscribe[/send][br]"
							:
							"You subscription is not active. [send=/subscribe]Subscribe[/send][br]"
						).
						"For more information follow links below:"
				));

				$this->restCommand('imbot.command.answer', Array(
					"COMMAND_ID" => $command['COMMAND_ID'],
					"MESSAGE_ID" => $command['MESSAGE_ID'],
					"MESSAGE" =>
						"[URL=https://developer.github.com/webhooks/]GitHub Webhooks[/URL][br]"
				));

				$this->restCommand('imbot.command.answer', Array(
					"COMMAND_ID" => $command['COMMAND_ID'],
					"MESSAGE_ID" => $command['MESSAGE_ID'],
					"MESSAGE" =>
						"[URL=https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=93]Bitrix IM bot platform learning course[/URL][br]"
				));
			}

			elseif ($command['COMMAND'] === 'status')
			{
				$this->restCommand('imbot.command.answer', Array(
					"COMMAND_ID" => $command['COMMAND_ID'],
					"MESSAGE_ID" => $command['MESSAGE_ID'],
					"MESSAGE" =>
						"I'm GitHub notifier bot ;)[br] I can forward you notification from GitHub.[br] ".
						(
						$isSubscribed ?
							"You subscription is activate now. [send=/unsubscribe]Unsubscribe[/send][br]"
							:
							"You subscription is not active. [send=/subscribe]Subscribe[/send][br]"
						).
						"For more information follow links below:",

					"KEYBOARD" => [
						["TEXT" => "Help", "COMMAND" => "help", "DISPLAY" => "LINE",],
						$isSubscribed ?
							["TEXT" => "Unsubscribe", "COMMAND" => "unsubscribe", "DISPLAY" => "LINE",
							 "BG_COLOR" => "#2a4c7c", "TEXT_COLOR" => "#fff",]
							:
							["TEXT" => "Subscribe", "COMMAND" => "subscribe", "DISPLAY" => "LINE",
							 "BG_COLOR" => "#29619b", "TEXT_COLOR" => "#fff",],
					]
				));
			}

			elseif ($command['COMMAND'] === 'subscribe')
			{
				// subscription ON
				$this->config['SUBSCRIPTIONS'][$dialogId] = true;

				$this->saveConfig($this->config);

				$this->restCommand('imbot.command.answer', Array(
					"COMMAND_ID" => $command['COMMAND_ID'],
					"MESSAGE_ID" => $command['MESSAGE_ID'],
					"MESSAGE" => "You subscription has been activated!"
				));
			}

			elseif ($command['COMMAND'] === 'unsubscribe')
			{
				// subscription OFF
				unset($this->config['SUBSCRIPTIONS'][$dialogId]);

				$this->saveConfig($this->config);

				$this->restCommand('imbot.command.answer', Array(
					"COMMAND_ID" => $command['COMMAND_ID'],
					"MESSAGE_ID" => $command['MESSAGE_ID'],
					"MESSAGE" => "You subscription has been deactivated!"
				));
			}

		}
	})


	// process request
	->init()
	->verifyRequest()

	// call event handler
	->dispatchEvent()
;

echo 'ok';
