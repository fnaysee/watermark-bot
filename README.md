# Watermark Bot
A simple telegram bot to add watermark to images

This is an example bot written on Lumen (micro framework based on laravel) and uses php-telegram-bot to show you how easily you can create a full feature bot using php-telegram-bot.

# Installation is easy:
1- Download the repo with git or zip.

2- Run composer update in the project directory to download dependencies.

3- Edit .env file at the root with your bot details (make sure you change the TELEGRAM_REQUEST_URL to your own address).

4- Open vendor/longman/telegram-bot and import structure.sql to your database.

5- In your browser run https://example.com/set-webhook

6- Now in your telegram client open your bot page and start using it.

# How it works
Simply send an image or forward it to the bot and it ask you for the watermark string to be added to the image, after sending the string it sends you the edited version of the image.

# Notes

1- This is just an example bot to let you see how you can use php-telegram-bot library not a ready to use script.

2- You are allowed to edit any thing in this project and use it in your free or commercial projects, but you may not sell it to anyone without changing its functionallity, or uploading same code on your own repo (If you want work on it simply fork the project)
