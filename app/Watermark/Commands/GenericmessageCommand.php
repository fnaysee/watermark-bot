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
use Longman\TelegramBot\Request;
use Yii;
/**
 * Generic message command
 *
 * Gets executed when any type of message is sent.
 * Edited a bit to rediret any unknown requests to the start command
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
        $msg = $this->getMessage();

        $chat    = $msg->getChat();
        $user    = $msg->getFrom();
        $text    = trim($msg->getText(true));
        $chat_id = $chat->getId();
        $user_id = $user->getId();

        //If a conversation is busy, execute the conversation command after handling the message
        $conversation = new Conversation(
            $chat_id,
            $user_id
        );

        //Fetch conversation command if it exists and execute it
        if ($conversation->exists() && ($command = $conversation->getCommand())) 
        {
            return $this->telegram->executeCommand($command);
        }
        else 
        {
            $command = 'start';
            return $this->telegram->executeCommand($command);
        }
    }
}