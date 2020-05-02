<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicesGroup extends Model
{
    
    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'devices_group';

    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = false;

}
