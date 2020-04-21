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
     * Вывод строк для ленты комментариев
     */
    public static function getCommentsTapeData($request) {

        $where = [
            ['a.del', NULL],
        ];

        if ($request->__user->access->admin == 0 AND $request->__user->access->application_show_del == 0)
            $where[] = ['c.del', NULL];
    
        return DB::table('applications_comment as a')
        ->select('a.*', 'b.firstname', 'b.lastname', 'b.fathername', 'd.name', 'c.project')
        ->join('users as b', 'a.userId', '=', 'b.id')
        ->join('applications as c', 'a.applicationId', '=', 'c.id')
        ->join('projects as d', 'c.clientId', '=', 'd.id')
        ->where($where)
        ->orderBy('a.date', 'DESC')->paginate(35);

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

    /** Новые комментарии */
    public static function countNewComment($user) {

        $where = [
            ['a.date', '>', $user->times->comments ?? 0],
            ['a.userId', '!=', $user->id]
        ];

        if ($user->access->admin == 0 AND $user->access->application_show_del == 0)
            $where[] = ['b.del', NULL];

        return DB::table('applications_comment as a')
        ->join('applications as b', 'a.applicationId', '=', 'b.id')
        ->where($where)
        ->whereIn('b.clientId', $user->clientsAccess)
        ->count();

    }

    /** Новые сервисы */
    public static function countNewServices($user) {

        $where = [
            ['a.date', '>', $user->times->services ?? 0],
            ['a.userId', '!=', $user->id]
        ];

        if ($user->access->admin == 0 AND $user->access->application_show_del == 0)
            $where[] = ['b.del', NULL];

        return DB::table('applications_service as a')
        ->join('applications as b', 'a.applicationId', '=', 'b.id')
        ->where($where)
        ->whereIn('b.clientId', $user->clientsAccess)
        ->count();

    }


}