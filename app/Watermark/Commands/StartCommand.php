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

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

/**
 * System "/start" command
 * Start command is our starting position to show the menu,
 * because telegram show a start button to anyone who wants work with our bot for first time
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Show a custom keyboard with reply markup';

    /**
     * @var string
     */
    protected $usage = '/start';

    /**
     * @var string
     */
    protected $version = '1.1.0';

//    protected $private_only = true;

    /**
     * Command execute method.
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();

        $text    = trim($message->getText(true));
        $chat    = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $message->getFrom()->getId();

        // Only answer requests from private chats
        if (!$chat->isPrivateChat()) {
            return Request::emptyResponse();
        }

        // Reset the /watermark conversation.
        if (($c = new Conversation($user_id, $chat_id, 'watermark'))->exists()) {
            $c->notes = [];
            $c->update();
        }

        $commands = [
            'Add watermark' => 'watermark',
        ];

        // Detecting command from received text (from normal keyboard)
        if ($cmd = $commands[$text] ?? null) {
            return $this->telegram->executeCommand($cmd);
        }

        // Send the welcome text!
        return Request::sendMessage([
            'chat_id'      => $chat_id,
            'text'         => 'Welcome to watermark adder bot',
            'reply_markup' => (new Keyboard([
                'Add watermark',
            ]))
                ->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->setSelective(false),
        ]);
    }
}
