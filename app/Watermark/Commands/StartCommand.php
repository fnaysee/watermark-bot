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

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
/**
 * User "/keyboard" command
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
    protected $version = '1.0.0';
    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $msg = $this->getMessage();

        $chat    = $msg->getChat();
        $user    = $msg->getFrom();
        $text    = trim($msg->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        if(empty($msg))
        {
            return Request::emptyResponse();
        }

        //Only answer requests from private chats
        if ($chat->isGroupChat() || $chat->isSuperGroup() || $chat->isChannel() || $msg->getLeftChatMember())
        {
            return Request::emptyResponse();
        }

        //Conversation start
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());
        $startcmd = &$this->conversation->startcmd;

        $commands = [
            'watermark'   => 'Add watermark',
        ];

        //Detecting command from received text (from normal keyboard)
        foreach ($commands as $cmd => $txt)
        {
        	if($text == $txt)
            {
                $this->conversation = new Conversation($user_id, $chat_id, $cmd);
                $this->conversation->update();
                return $this->telegram->executeCommand($cmd);
            }
        }

        $response_message = 'Welcome to watermark adder bot';

        $keyboard = new Keyboard([
            ['text' => 'Add watermark'],
        ]);

        $keyboard->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->setSelective(false);

        $data = [
            'chat_id'      => $chat_id,
            'text'         => $response_message,
            'reply_markup' => $keyboard,
        ];

        return Request::sendMessage($data);
    }
}