<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class TelegramOutgoingMessage extends Model
{

    /**
    * Связанная с моделью таблица.
    *
    * @var string
    */
   protected $table = 'telegram_outgoing_messages';

   public static function getMessageQueue() {

        return DB::table('telegram_outgoing_messages')
        ->select('telegram_outgoing_messages.*', 'projects.bottoken')
        ->leftjoin('projects', 'projects.id', '=', 'telegram_outgoing_messages.client_id')
        ->where('response', NULL)
        ->limit(1)
        ->get();

   }

}
