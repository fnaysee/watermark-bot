<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Request;
use Intervention\Image\ImageManager;
/**
 * User "/watermark" command
 * This command handles requests from normal keyboard,
 * Requests from inline keyboard are handled directly in CallbackqueryCommand
 */
class WatermarkCommand extends UserCommand
{
    /**
     * @var string
     */
    protected $name = 'watermark';
    /**
     * @var string
     */
    protected $description = 'Adds watermark to received image';
    /**
     * @var string
     */
    protected $usage = '/watermark';
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

        $string    = '';
        $file_id   = '';
        $file_type = '';

        if(empty($msg))
        {
            return Request::emptyResponse();
        }

        if ($chat->isGroupChat() || $chat->isSuperGroup())
        {
            $data['reply_markup'] = Keyboard::forceReply(['selective' => true]);
        }

        //Conversation start
        //Conversation lets us save current user state, for example which menu is user in. Using this feature needs db
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = ['watermark'];

        //cache data from the tracking session if any
        $state = 0;

        if (isset($notes['watermark']['state']))
        {
            $state = $notes['watermark']['state'];
        }

        if($this->isReturnButton($text))
        {
            $this->doReturn();
            return Request::emptyResponse();
        }

        if(!$string = $text)
        {
            $string = $msg->getCaption();
        }

        if($photo = $msg->getPhoto())
        {
            $photo = end($photo);
            $file_id = $photo->getFileId();//$photo[0]['file_id'];
            $file_type = 'photo';
        }

        switch ($state)
        {
            case 0:
                $notes['watermark']['state'] = 1;
                $this->conversation->update();
                $response_message = 'Send me a photo or a message that contains a photo';

                $data = [
                    'chat_id'      => $chat_id,
                    'text'         => $response_message,
                    'reply_markup' => self::getKeyboard(),
                ];

                return Request::sendMessage($data);
            case 1:
                if($text || empty($file_id))
                {
                    $data = [
                        'chat_id'      => $chat_id,
                        'text'         => 'Send me a photo or a message that contains a photo',
                        'reply_markup' => self::getKeyboard(),
                    ];

                    return Request::sendMessage($data);
                }

                $data = [
                    'chat_id'       => $chat_id,
                    'caption'         => $string,
                    'photo'         => $file_id,
                    'reply_markup'  => self::getInlineKeyboard(),
                ];

                return Request::sendPhoto($data);
            case 2: //This one is being called from our callback query
                $data = [
                    'chat_id'       => $notes['watermark']['message_details']['chat_id'],
                    'caption'       => $notes['watermark']['message_details']['string'],
                    'reply_markup'  => self::getInlineKeyboard(),
                ];

                $watermark = mb_substr($string, 0, 50);

                if(!empty($watermark))
                {
                    //Downloading photo
                    $photo_obj = Request::getFile([
                        'file_id' => $notes['watermark']['message_details']['file_id'],
                    ]);

                    //var_dump("https://api.telegram.org/file/bot" . getenv('BOT_API_TOKEN') . "/" . $photo_obj->result->file_path);
                    //die();

                    $filename = basename($photo_obj->result->file_path);

                    //Make image using intervention library
                    $manager = new ImageManager();
                    $img = $manager->make("https://api.telegram.org/file/bot" . getenv('BOT_API_TOKEN') . "/" . $photo_obj->result->file_path);

                    $x = (int)300;
                    $y = (int)500;
                    //Write the text to the image
                    $img->text($watermark, $x, $y, function($font) {
                        $font->file(app()->basePath('public/fonts/OpenSans-Regular.ttf'));
                        $font->size(30);
                        $font->color('#fff');
                    });

                    //Make sure image directory exists
                    if(!file_exists(app()->basePath('public/images')))
                    {
                        mkdir(app()->basePath('public/images'));
                    }

                    //Save edited image
                    $img->save(app()->basePath('public/images/' . $filename));

                    //Send image to telegram
                    $data['photo'] = getenv('TELEGRAM_REQUESTS_URL') . '/public/images/' . $filename;
                    $result = Request::sendPhoto($data);

                    //Remove image
                    unlink(app()->basePath('public/images/' . $filename));

                    //Update command state in conversation
                    $notes['watermark']['state'] = 1;
                    $notes['watermark']['message_details'] = [];
                    $this->conversation->update();

                    return $result;
                }
        }

        return Request::emptyResponse();
    }

    public function isReturnButton($message) {
        if($message == 'Return')
        {
            return true;
        }

        return false;
    }

    public function doReturn()
    {
        if(!empty($this->conversation))
        {
            $notes = &$this->conversation->notes;
            $notes = [];
            $this->conversation->update();
        }

        $this->telegram->executeCommand('start');
    }

    public static function getKeyboard()
    {
        $keyboard = new Keyboard([
            ['text' => 'Return'],
        ]);

        if($keyboard)
        {
            $keyboard->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->setSelective(false);
            return $keyboard;
        }

        return null;
    }

    public static function getInlineKeyboard()
    {
        $keyboard = new InlineKeyboard([
            ['text' => 'Add watermark', 'callback_data' => 'addWatermark'],
        ]);

        if($keyboard)
        {
            $keyboard->setResizeKeyboard(true)
                ->setOneTimeKeyboard(true)
                ->setSelective(false);
            return $keyboard;
        }

        return null;
    }
}