<?php

namespace App\Models;

use DB;

class ServiceModel
{

    /**
     * Постраничный вывод данных сервиса
     * 
     * @param Array $id Массив запрашиваемых идентификаторов
     * 
     * @return Object
     */
    public static function getServicesData($id) {

        return DB::table('applications_service')->whereIn('id', $id)->get();

    }


    /**
     * Вывод строк для ленты
     */
    public static function getWorkTapeData($request) {
    
        return DB::table('applications_service as a')
        ->select('a.*', 'b.bus')
        ->join('applications as b', 'a.id', '=', 'b.done')
        ->orderBy('a.id', 'DESC')->paginate(25);

    }

    /**
     * Запись серийных номеров
     */
    public static function writeSerialsChangeNumber($arr) {

        return DB::table('device_change_serials')->insert($arr);

    }

    /** Список замены мерийных номеров */
    public static function getSerialsFromChangedDeviceApplications($ids) {

        return DB::table('device_change_serials')->whereIn('serviceId', $ids)->orderBy('date', 'DESC')->get();

    }


}