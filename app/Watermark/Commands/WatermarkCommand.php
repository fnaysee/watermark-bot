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

use Intervention\Image\AbstractFont;
use Intervention\Image\ImageManager;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;

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
    protected $version = '1.1.0';

    /**
     * @var bool
     */
    protected $private_only = true;

    /**
     * @var Conversation
     */
    protected $conversation;

    /**
     * Command execute method
     *
     * @return ServerResponse
     * @throws TelegramException
     */
    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $text    = trim($message->getText(true));

        // Bail if we're returning to main menu.
        if ($this->isReturnButton($text)) {
            return $this->doReturn();
        }

        $string = $text !== '' ? $text : $message->getCaption();

        //Conversation start
        //Conversation lets us save current user state, for example which menu is user in. Using this feature needs db
        $this->conversation = new Conversation($user_id, $chat_id, $this->getName());

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = ['watermark'];

        //cache data from the tracking session if any
        $state = $notes['watermark']['state'] ?? 0;

        $file_id = '';
        if ($photo = $message->getPhoto()) {
            // Get file_id of best quality photo.
            $file_id = end($photo)->getFileId();
        }

        switch ($state) {
            case 0:
                $notes['watermark']['state'] = 1;
                $this->conversation->update();

                return Request::sendMessage([
                    'chat_id'      => $chat_id,
                    'text'         => 'Send me a photo or a message that contains a photo',
                    'reply_markup' => self::getKeyboard(),
                ]);
            case 1:
                if ($text || empty($file_id)) {
                    return Request::sendMessage([
                        'chat_id'      => $chat_id,
                        'text'         => "Hmm, that doesn't look like a photo.\nSend me a photo or a message that contains a photo",
                        'reply_markup' => self::getKeyboard(),
                    ]);
                }

                return Request::sendPhoto([
                    'chat_id'      => $chat_id,
                    'caption'      => $string,
                    'photo'        => $file_id,
                    'reply_markup' => self::getInlineKeyboard(),
                ]);
            case 2: //This one is being called from our callback query

                $watermark = mb_substr($string, 0, 50);

                if ($watermark !== '') {
                    $filepath = $this->addWatermarkToFile(
                        $notes['watermark']['message_details']['file_id'],
                        $watermark
                    );

                    //Send image to telegram
                    $result = Request::sendPhoto([
                        'chat_id'      => $notes['watermark']['message_details']['chat_id'],
                        'photo'        => Request::encodeFile($filepath),
                        'caption'      => $notes['watermark']['message_details']['string'],
                        'reply_markup' => self::getInlineKeyboard(),
                    ]);

                    //Remove image
                    unlink($filepath);

                    //Update command state in conversation
                    $notes['watermark']['state']           = 1;
                    $notes['watermark']['message_details'] = [];
                    $this->conversation->update();

                    return $result;
                }
        }

        return Request::emptyResponse();
    }

    /**
     * Add a watermark to an image and returned the saved path.
     *
     * @param string $file_id
     * @param string $watermark
     *
     * @return string
     */
    protected function addWatermarkToFile(string $file_id, string $watermark): string
    {
        // Get photo file from Telegram.
        $photo = Request::getFile(compact('file_id'))->getResult();

        $tg_filepath    = $photo->getFilePath();
        $tg_filename    = basename($tg_filepath);
        $local_filepath = storage_path('images/' . $tg_filename);

        // Make image using intervention library.
        $img = (new ImageManager())->make(sprintf(
            'https://api.telegram.org/file/bot%s/%s',
            getenv('BOT_API_TOKEN'),
            $tg_filepath
        ));

        // Calculate a bit where to place the watermark.
        $x = ceil($img->width() * 0.1);
        $y = ceil($img->height() * 0.2); // Same as ($x - $font_size) below.

        // Write the text to the image.
        $img->text($watermark, $x, $y, function (AbstractFont $font) use ($img) {
            $font->file(resource_path('fonts/OpenSans-Regular.ttf'));
            $font->size(ceil($img->height() * 0.1));
            $font->color('#fff');
        });

        // Save edited image.
        $img->save($local_filepath);

        return $local_filepath;
    }

    /**
     * Check if "Return" has been selected.
     *
     * @param string $message
     *
     * @return bool
     */
    public function isReturnButton(string $message): bool
    {
        return $message === 'Return';
    }

    /**
     * Restart the bot by clearing the conversation and issuing the "/start" command.
     *
     * @return mixed
     * @throws TelegramException
     */
    public function doReturn()
    {
        if ($this->conversation !== null) {
            $this->conversation->notes = [];
            $this->conversation->update();
        }

        return $this->telegram->executeCommand('start');
    }

    /**
     * Get the default keyboard.
     *
     * @return Keyboard
     * @throws TelegramException
     */
    public static function getKeyboard(): Keyboard
    {
        return (new Keyboard([
            ['text' => 'Return'],
        ]))
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->setSelective(false);
    }

    /**
     * Get the default inline keyboard.
     *
     * @return InlineKeyboard
     * @throws TelegramException
     */
    public static function getInlineKeyboard(): InlineKeyboard
    {
        return (new InlineKeyboard([
            ['text' => 'Add watermark', 'callback_data' => 'addWatermark'],
        ]))
            ->setResizeKeyboard(true)
            ->setOneTimeKeyboard(true)
            ->setSelective(false);
    }
}
