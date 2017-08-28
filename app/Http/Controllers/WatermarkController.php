<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;

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
        $hook_url = getenv('TELEGRAM_REQUESTS_URL') . '/public/handle-telegram-updates';

        try {
            // Create Telegram API object
            $telegram = new \Longman\TelegramBot\Telegram($bot_api_key, $bot_username);

            //Set verify to false for test in local, you can remove or comment it
            \Longman\TelegramBot\Request::setClient(new \GuzzleHttp\Client([
                'base_uri' => 'https://api.telegram.org',
                'verify'   => false,
            ]));

            // Set webhook
            $result = $telegram->setWebhook($hook_url);

            if ($result->isOk())
            {
                echo $result->getDescription();
            }
        }
        catch (Longman\TelegramBot\Exception\TelegramException $e)
        {
            echo $e->getMessage();
        }
    }

    public function handleTelegramUpdates()
    {
        if(empty(getenv('BOT_API_TOKEN')))
        {
            throw new \Exception('Bot token is required', 500);
        }

        $telegram = new \Longman\TelegramBot\Telegram(getenv('BOT_API_TOKEN'), getenv('BOT_USERNAME'));

        \Longman\TelegramBot\Request::setClient(new \GuzzleHttp\Client([
            'base_uri' => 'https://api.telegram.org',
            'verify'   => false,
        ]));

        //Adding our commands path to default commands paths
        //We placed all kind of commands in one directory
        $telegram->addCommandsPaths([
            app()->path('/Watermark/Commands/')
        ]);

        //Enable requests limiter, to prevent attack like requests
        $telegram->enableLimiter();

        //from .env file at root
        $mysql_credentials = [
           'host'     => getenv('BOT_DB_HOST'),
           'user'     => getenv('BOT_DB_USERNAME'),
           'password' => getenv('BOT_DB_PASSWORD'),
           'database' => getenv('BOT_DB_DATABASE'),
        ];

        $telegram->enableMySql($mysql_credentials);

        try
        {
            $telegram->handle();
        }
        catch (\Exception $exception)
        {
            var_dump($exception->getMessage());
        }
    }
}
