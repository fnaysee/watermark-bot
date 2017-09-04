<?php

namespace App\Http\Controllers;

use Exception;
use GuzzleHttp\Client;
use Laravel\Lumen\Routing\Controller as BaseController;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\TelegramLog;

class WatermarkController extends BaseController
{
    public function index()
    {
        //
    }

    public function setWebhookInfo()
    {
        $bot_api_key  = getenv('BOT_API_TOKEN');
        $bot_username = getenv('BOT_USERNAME');
        $hook_url     = getenv('TELEGRAM_REQUESTS_URL') . '/handle-telegram-updates';

        try {
            // Create Telegram API object
            $telegram = new Telegram($bot_api_key, $bot_username);

            //Set verify to false for test in local, you can remove or comment it
            Request::setClient(new Client([
                'base_uri' => 'https://api.telegram.org',
                'verify'   => false,
            ]));

            // Set webhook
            $result = $telegram->setWebhook($hook_url);

            if ($result->isOk()) {
                echo $result->getDescription();
            }
        } catch (TelegramException $e) {
            echo $e->getMessage();
        }
    }

    public function handleTelegramUpdates()
    {
        if (empty(getenv('BOT_API_TOKEN'))) {
            throw new Exception('Bot token is required', 500);
        }

        $telegram = new Telegram(getenv('BOT_API_TOKEN'), getenv('BOT_USERNAME'));

        Request::setClient(new Client([
            'base_uri' => 'https://api.telegram.org',
            'verify'   => false,
        ]));

        //Adding our commands path to default commands paths
        //We placed all kind of commands in one directory
        $telegram->addCommandsPaths([
            app()->path() . '/Watermark/Commands',
        ]);

        //Enable requests limiter, to prevent attack like requests
        $telegram->enableLimiter();

        TelegramLog::initDebugLog(storage_path('logs/debug.log'));
        TelegramLog::initErrorLog(storage_path('logs/error.log'));
        TelegramLog::initUpdateLog(storage_path('logs/update.log'));

        //from .env file at root
        $telegram->enableMySql([
            'host'     => getenv('BOT_DB_HOST'),
            'user'     => getenv('BOT_DB_USERNAME'),
            'password' => getenv('BOT_DB_PASSWORD'),
            'database' => getenv('BOT_DB_DATABASE'),
        ]);

        try {
            // Make sure temporary image directory exists.
            @mkdir(storage_path('images'));

            $telegram->handle();
        } catch (Exception $exception) {
            TelegramLog::error($exception);
            var_dump($exception->getMessage());
        }
    }
}
