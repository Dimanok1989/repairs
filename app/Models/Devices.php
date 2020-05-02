<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Devices extends Model
{

    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'devices';

    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = false;

    public static function getDeviceList() {

        return DB::table('devices')
        ->select('devices.*', 'devices_group.name as groupName')
        ->leftjoin('devices_group', 'devices.groupId', '=', 'devices_group.id')
        ->orderBy('devices.name')->get();

    }

}
