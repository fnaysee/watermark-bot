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
use Longman\TelegramBot\Commands\UserCommands\EditmessageCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Yii;

/**
 * Callback query command
 *
 * This command handles all callback queries sent via inline keyboard buttons.
 *
 * @see InlinekeyboardCommand.php
 */
class CallbackqueryCommand extends SystemCommand
{
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
    protected $version = '1.2.0';

    /**
     * Command execute method.
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $callback_data = $this->getCallbackQuery()->getData();

        if (method_exists($this, $callback_data)) {
            return $this->{$callback_data}();
        }

        return Request::emptyResponse();
    }

    /**
     * Send last message to user, requesting the watermark text.
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function addWatermark(): ServerResponse
    {
        $callback_query = $this->getCallbackQuery();
        $message        = $callback_query->getMessage();
        $chat_id        = $message->getChat()->getId();

        $conv  = new Conversation($chat_id, $chat_id, 'watermark');
        $notes = &$conv->notes;

        $string = $message->getText() ?: $message->getCaption() ?? '';

        $photo = $message->getPhoto();
        $photo = end($photo);

        // Changing state of watermark command
        // state 2 in there adds the watermark to the image
        $notes['watermark']['state']           = 2;
        $notes['watermark']['message_details'] = [
            'chat_id' => $chat_id,
            'file_id' => $photo->file_id,
            'string'  => $string,
        ];
        $conv->update();

        Request::sendMessage([
            'chat_id' => $chat_id,
            'text'    => 'Now send me a string with max 30 characters to be added as watermark to the image.',
        ]);

        return Request::answerCallbackQuery([
            'callback_query_id' => $callback_query->getId(),
            'text'              => 'Almost done!',
        ]);
    }
}
