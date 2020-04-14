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

}