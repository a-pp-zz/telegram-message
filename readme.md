# Telegram Message v1.0
Send messages to tg group via bot

## Preparing
* Create a bot
* Get the bot's API token from @BotFather
* Add your bot to the chat you'll be sending messages to
* Get the ID of the chat : https://api.telegram.org/bot$TELEGRAM_BOT_TOKEN/getUpdates

## Usage
```
<?php
	$tg = TelegramMessage::factory ();
	$tg->token('00000000000000000000000000000000000000000000000');
	$tg->chat_id('-0000000000000000000');
	//$tg->markdown();
	$tg->text("Ut semper!!!");
	//$tg->silent(true);
	$result = $tg->send();
?>

```
