<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use EduTatarRuBot\Models\Client;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\KeyboardButton;
use Longman\TelegramBot\Request;

/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 */
class GenericmessageCommand extends SystemCommand
{
	/**
	 * @var string
	 */
	protected $name = 'genericmessage';

	/**
	 * @var string
	 */
	protected $description = 'Handle generic message';

	/**
	 * @var string
	 */
	protected $version = '1.1.0';

	/**
	 * @var bool
	 */
	protected $need_mysql = true;

	/**
	 * Command execute method if MySQL is required but not available
	 *
	 * @return \Longman\TelegramBot\Entities\ServerResponse
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function executeNoDb()
	{
		// Do nothing
		return Request::emptyResponse();
	}

	/**
	 * Command execute method
	 *
	 * @return \Longman\TelegramBot\Entities\ServerResponse
	 * @throws \Longman\TelegramBot\Exception\TelegramException
	 */
	public function execute()
	{
		//If a conversation is busy, execute the conversation command after handling the message
		$conversation = new Conversation(
			$this->getMessage()->getFrom()->getId(),
			$this->getMessage()->getChat()->getId()
		);

		//Fetch conversation command if it exists and execute it
		if ($conversation->exists() && ($command = $conversation->getCommand())) {
			return $this->telegram->executeCommand($command);
		}
		$message = $this->getMessage();
		$text = $message->getText(true);
		$chat_id = $message->getChat()->getId();

		$keyboard = \EduTatarRuBot\Models\MessageQueue::getKeyboardButtons();
		foreach ($keyboard as $buttonRow) {
			foreach ($buttonRow as $buttonCode => $buttonText) {
				/**
				 * @var $button KeyboardButton
				 */
				if ($buttonText == $text) {
					return $this->telegram->executeCommand($buttonCode);
				}
			}
		}

		$client = new Client();
		if ($client->addClientProcess($chat_id, $text)) {
			return Request::emptyResponse();
		}

		$text = 'Не волнуйтесь, всё идёт по плану. Мы уже следим за всем чем нужно.';

		$data = [
			'chat_id' => $chat_id,
			'text' => $text,
			'parse_mode' => 'markdown',
		];
		return \EduTatarRuBot\Request::sendMessage($data);

	}
}
