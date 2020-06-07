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
	// setup config
	->setConfigFile(__DIR__ . '/.runtime.php')

	// setup debug and log
	->debugMode(false)
	->setLogFile(__DIR__ . '/logs/hook.txt')


	->setEventHandler('GitHub-*', function (){
		/** @var \Demo\GitHubImBot $this */

		// $this->config['SUBSCRIPTIONS'][$dialogId] = true;
		$subscriptions = $this->config['SUBSCRIPTIONS'] ?: [];

		foreach ($subscriptions as $dialogId => $subscription)
		{
			$payload = $this->getPayload();


			$blocks = [];

			//sender
			if (isset($payload->sender) && !empty($payload->sender->avatar_url))
			{
				$blocks[] = Array("USER" => Array(
					"NAME" => $payload->sender->login,
					"AVATAR" => $payload->sender->avatar_url,
					"LINK" => $payload->sender->html_url,
				));
			}
			elseif (isset($payload->sender))
			{
				$blocks[] = Array("USER" => Array(
					"NAME" => $payload->sender->login,
					"AVATAR" => "https://github.githubassets.com/favicons/favicon.png",
					"LINK" => $payload->sender->html_url,
				));
			}

			$blocks[] = Array("DELIMITER" => ['SIZE' => 200, 'COLOR' => "#c6c6c6"]);

			//organization
			if (isset($payload->organization))
			{
				$blocks[] = Array("GRID" => Array(
					Array(
						"NAME" => "Organization",
						"VALUE" => $payload->organization->full_name,
						"LINK" => $payload->organization->html_url,
						"DISPLAY" => "BLOCK",
						"WIDTH" => "500"
					),
				));
			}

			//repository
			if (isset($payload->repository))
			{
				$blocks[] = Array("GRID" => Array(
					Array(
						"NAME" => "Repository",
						"VALUE" => $payload->repository->full_name,
						"LINK" => $payload->repository->html_url,
						"DISPLAY" => "BLOCK",
						"WIDTH" => "500"
					),
					Array(
						"NAME" => "",
						"VALUE" => $payload->repository->description,
						"DISPLAY" => "BLOCK"
					),
				));
			}

			$blocks[] = Array("DELIMITER" => ['SIZE' => 200, 'COLOR' => "#c6c6c6"]);


			//pusher
			if (isset($payload->pusher))
			{
				$blocks[] = Array("GRID" => Array(
					Array(
						"NAME" => "Pusher",
						"VALUE" => $payload->pusher->name,
						"DISPLAY" => "COLUMN"
					),
				));
			}
			// commits
			if (isset($payload->commits))
			{
				foreach ($payload->commits as $commit)
				{
					$blocks[] = array("GRID" => array(
						array(
							"NAME" => $commit->timestamp,
							"LINK" => $commit->url,
							"VALUE" => $commit->message,
							"DISPLAY" => "COLUMN"
						),
					));
				}
			}

			//issue
			if (isset($payload->issue))
			{
				$blocks[] = Array("GRID" => Array(
					Array(
						"NAME" => "Issue",
						"VALUE" => $payload->issue->title,
						"LINK" => $payload->issue->html_url,
						"DISPLAY" => "BLOCK",
						"WIDTH" => "500"
					),
					Array(
						"NAME" => "",
						"VALUE" => $payload->issue->body,
						"DISPLAY" => "BLOCK"
					),
				));
			}

			//comment
			if (isset($payload->comment))
			{
				$blocks[] = Array("DELIMITER" => ['SIZE' => 200, 'COLOR' => "#c6c6c6"]);
				$blocks[] = Array("GRID" => Array(
					Array(
						"NAME" => "",
						"VALUE" => "New issue comment",
						"LINK" => $payload->comment->html_url,
						"DISPLAY" => "BLOCK",
						"WIDTH" => "500"
					),
					Array(
						"NAME" => "Comment",
						"VALUE" => $payload->comment->body,
						"DISPLAY" => "BLOCK"
					),
				));
			}

			//action
			$actionType = '';
			if (isset($payload->action))
			{
				$actionType = ucfirst($payload->action);
			}

			// subject
			$subjectType = ucfirst(str_replace(['-','_'], ' ', $this->headers['X-GitHub-Event']));

			$message = [
				"DIALOG_ID" => $dialogId,
				"MESSAGE" => "[B]{$actionType} {$subjectType} notification from GitHub[/B]",
				"ATTACH" => Array(
					"ID" => 1,
					"COLOR" => "#000000",
					"BLOCKS" => $blocks,
				)
			];

			$this->restCommand('imbot.message.add', $message);
		}
	})


	// process request
	->init()
	->verifyRequest()

	// call event handler
	->dispatchEvent()
;

echo 'ok';
