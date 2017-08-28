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
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Conversation;

use Yii;
use Longman\TelegramBot\Commands\UserCommands\EditmessageCommand;
/**
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */
class CallbackqueryCommand extends SystemCommand
{
    public $file_id;
    public $file_type;
    public $message;
    public $message_id;
    public $message_type;
    public $message_string;
    public $chat_id;

    /**
     * @var string
     */
    protected $name = 'callbackquery';
    /**
     * @var string
     */
    protected $description = 'Reply to callback query';
    /**
     * @var string
     */
    protected $version = '1.1.1';
    /**
     * Command execute method
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {

        $this->callback_query    = $this->getCallbackQuery();
        $this->callback_query_id = $this->callback_query->getId();
        $this->callback_data     = $this->callback_query->getData();
        $this->message           = $this->callback_query->getMessage();
        $this->message_id        = $this->message->getMessageId();
        $this->chat_id           = $this->message->getChat()->getId();

        $jobDone = false;

        if(method_exists($this, $this->callback_data))
        {
            $jobDone = call_user_func([$this, $this->callback_data]);
        }

        if($jobDone)
        {
            $data = [
                'callback_query_id' => $this->callback_query_id,
                'text'              => 'Done !',
                'show_alert'        => false,
                'cache_time'        => 5,
            ];

            return Request::answerCallbackQuery($data);
        }

        return Request::emptyResponse();
    }


    public function addWatermark()
    {
        $conv = new Conversation($this->chat_id, $this->chat_id, 'watermark');
        $notes = &$conv->notes;

        $photo = $this->message->getPhoto();
        $photo = end($photo);

        //Changing state of watermark command
        //state 2 in there adds the watermark to the image
        $notes['watermark']['state'] = 2;
        $notes['watermark']['message_details'] = ['chat_id' => $this->chat_id, 'file_id' => $photo->file_id, 'string' => $this->message->getText()];
        $conv->update();

        $data = [
            'chat_id' => $this->chat_id,
            'text' => 'Now send me an string with max 30 characters to be added as watermark to the image.'
        ];

        $result = Request::sendMessage($data);

        if($result->isOk())
        {
            return true;
        }

        return false;
    }
}