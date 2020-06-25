<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\TelegramOutgoingMessage;
use Telegram\Bot\Api as TelegramApi;

class Telegram extends \App\Http\Controllers\Main
{

    /**
     * Метод сохранения сообщения в базу очереди
     * 
     * @param int $client_id Идентификатор заказчика
     * @param int|string $chat_id Идентификатор чата или группы
     * @param string $text Текст сообщения
     * @param array $reply_markup Массив кнопок для сообщения
     * 
     * @return object
     */
    public static function newMessage($client_id, $chat_id, $text, $reply_markup = []) {

        $message = new TelegramOutgoingMessage;

        $message->client_id = $client_id;
        $message->chat_id = $chat_id;
        $message->text = $text;
        $message->reply_markup = json_encode($reply_markup);

        $message->save();

        return $message;

    }

    /**
     * Метод чтения очереди и отправки соообщения
     * Вызывается каждую минуту кроной, расчитан на время работы в 1 минуту
     * 
     * @return array
     */
    public static function sendQueue() {

        $start = $time = time();
        $data = [];

        while ($time - $start < 60) {

            $data[] = self::findMessage();

            sleep(1);
            $time = time();

        }

        return parent::json($data);

    }

    /**
     * Поиск сообщений для отправки
     */
    public static function findMessage() {

        $data = TelegramOutgoingMessage::getMessageQueue();

        if (!count($data))
            return parent::error("Нет сообщений в очереди");

        $send = $data[0];
        $response = self::sendMessage($send->bottoken, $send->chat_id, $send->text, json_decode($send->reply_markup));

        $message = TelegramOutgoingMessage::find($send->id);
        $message->response = $response;
        $message->save();

        return $message;

    }

    /**
     * Отправка сообщений
     * 
     * @param string $token Токен используемого бота
     * @param int|string $chat_id Идентификатор чата или группы
     * @param string $text Текст сообщения
     * @param array $reply_markup Массив кнопок для сообщения
     * 
     * @return json
     */
    public static function sendMessage($token, $chat_id, $text, $reply_markup = []) {

        $telegram = new TelegramApi($token);

        $response = $telegram->sendMessage([
            'parse_mode' => 'Markdown',
            'chat_id' => $chat_id, 
            'text' => $text,
            'reply_markup' => $reply_markup,
        ]);

        return $response;

    }

    public static function sendMessageOld($token, $chat_id, $text) {

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', "http://tmstar.ru/telegram{$token}/sendMessage?chat_id={$chat_id}&text={$text}");

        return $response->getBody();

    }

}