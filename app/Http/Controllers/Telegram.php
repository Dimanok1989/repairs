<?php

namespace App\Http\Controllers;

class Telegram extends \App\Http\Controllers\Main
{

    public static function sendMessage($token, $chat_id, $text) {

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', "http://tmstar.ru/telegram{$token}/sendMessage?chat_id={$chat_id}&text={$text}");

        return $response->getBody();

    }

}