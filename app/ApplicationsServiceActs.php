<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ApplicationsServiceActs extends Model
{

    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'applications_service_acts';

    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = true;

}
