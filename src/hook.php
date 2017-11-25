<?php
date_default_timezone_set('UTC');
define('BOT_USER', 'ForwardDateBot');
define('BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN');

$loader = require __DIR__.'/vendor/autoload.php';

use Telegram\Bot\Api;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Helpers\Emojify;


$telegram = new Api(BOT_TOKEN);

$updates = $telegram->getWebhookUpdates();


function nice_time($secs) {
    ($secs == null)? $secs = time():null;
    $secs = time() - $secs;
    if($secs >= 0){
    $ret = null;
    $bit = array(
        ' year' => $secs / 31556926 % 12,
        ' week' => $secs / 604800 % 52,
        ' day' => $secs / 86400 % 7,
        ' hour' => $secs / 3600 % 24,
        ' minute' => $secs / 60 % 60,
        ' second' => $secs % 60
    );

    foreach ($bit as $k => $v) {
        if ($v > 1) {
            $ret[] = $v . $k . 's';
        }
        if ($v == 1) {
            $ret[] = $v . $k;
        }
    }
    if($ret != null){
      if(count($ret) >= 2){
        array_splice($ret, count($ret) - 1, 0, 'and');
      }
    }else{
      $ret[0] ='A few seconds';
    }
      return join(' ',$ret) . ' ago.';
    }else{
      return "In future";
    }
}



if(isset($request->message) && isset($request->message->forward_date)){
    $dt = new DateTime('@'.$request->message->forward_date);
    $dt->setTimeZone(new DateTimeZone('UTC'));
    $niceDate = $dt->format('F j, Y, g:i a');

    $dt2 = new DateTime('@'.$request->message->date);
    $dt2->setTimeZone(new DateTimeZone('UTC'));
    $niceDate2 = $dt2->format('F j, Y, g:i a');


    $publicChat = $request->message->forward_from_chat->username;
    if($publicChat){
        $firstName = "@".$publicChat;
    }else{
        $firstName = "*".$request->message->forward_from->first_name."*";
        $userName = $request->message->forward_from->username;
        if($userName){
            $firstName = "@".$userName;
        }
    }

    $firstName = addcslashes($firstName, '\*_`\[');
    $telegram->sendMessage(['text' => "*Date of the original message sent by* ".$firstName.": ".$niceDate." UTC (".nice_time(strtotime($niceDate)).")" /*\n\n_Not sure about UTC time? Well, you sent me that message on ".$niceDate2." (UTC)_"*/,
        'parse_mode' => 'markdown',
        'chat_id' => $request->message->chat->id,
        'reply_to_message_id' => $request->message->message_id,
        'disable_web_page_preview' => true,
    ]);
}else if (isset($request->message) && isset($request->message->entities) && $request->message->entities[0]->type == "bot_command") {
    $command = substr($request->message->text, $request->message->entities[0]->offset, $request->message->entities[0]->length);

    if(str_replace("@".BOT_USER, '', $command) == "/start"){

        $telegram->sendMessage(['text' => "Hello, ".$request->message->from->first_name."! Please forward me a forwarded message and I'll tell you when it was sent by the original sender.",
            'parse_mode' => 'markdown',
            'chat_id' => $request->message->chat->id,
            'disable_web_page_preview' => true
        ]);
    }

}else{
    $telegram->sendMessage(['text' => "It seems that this message was not forwarded. Please forward me a forwarded message 😅",
        'parse_mode' => 'markdown',
        'chat_id' => $request->message->chat->id,
        'reply_to_message_id' => $request->message->message_id,
        'disable_web_page_preview' => true,
    ]);
}






?>