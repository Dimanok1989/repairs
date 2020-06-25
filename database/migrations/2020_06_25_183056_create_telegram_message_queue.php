<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTelegramMessageQueue extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('telegram_incoming_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamp('create_at')->default(\DB::raw('CURRENT_TIMESTAMP'));;
            $table->text('text')->comment('Текст сообщения');
            $table->string('chat_id', 100)->comment('Идентификатор чата');
            $table->string('from_id', 100)->comment('Идентификатор отправителя');
            $table->string('first_name', 100)->comment('Имя пользователя');
            $table->string('last_name', 100)->comment('Фамилия пользователя');
            $table->string('username', 100)->comment('Логин');
            $table->tinyInteger('edited')->default(0)->comment('Редактирование сообщения');
            $table->tinyInteger('callback')->default(0)->comment('Сообщение от обратной функции');
        });
        Schema::create('telegram_outgoing_messages', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('client_id')->comment('Идентификатор клиента');
            $table->timestamps();
            $table->text('text')->comment('Текст сообщения');
            $table->string('chat_id', 100)->comment('Идентификатор чата');
            $table->text('reply_markup')->nullable()->comment('JSON строка с клавиатурой');
            $table->text('response')->nullable()->comment('Ответ сервера');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('telegram_incoming_messages');
        Schema::dropIfExists('telegram_outgoing_messages');
    }
}
