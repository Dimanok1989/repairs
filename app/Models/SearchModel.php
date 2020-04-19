<?php

namespace App\Models;

use DB;

class SearchModel
{

    public static function searchApplications($request) {

        $where = [];

        // Доступ к просмотру удаленных заявок
        if ($request->__user->access->application_show_del == 0 AND $request->__user->access->admin == 0)
            $where[] = ['del', NULL];

        $data = DB::table('applications')
        ->select(
            'applications.*',
            'projects.login as clientLogin',
            'projects.name as clientName'
        )
        ->join('projects', 'projects.id', '=', 'applications.clientId')
        ->leftjoin('device_change_serials', 'device_change_serials.serviceId', '=', 'applications.done')
        ->where($where)
        ->whereIn('applications.clientId', $request->__user->clientsAccess) // Доступ к заказчикам
        ->where(function($query) use ($request) {
            $query->where('applications.bus', 'LIKE', "%{$request->text}%")
            ->orWhere('applications.comment', 'LIKE', "%{$request->text}%")
            ->orWhere('device_change_serials.serialOld', 'LIKE', "%{$request->text}%")
            ->orWhere('device_change_serials.serialNew', 'LIKE', "%{$request->text}%");

            $id = (int) $request->text;

            if ($id == $request->text)
                $query->orWhere('applications.id', 'LIKE', $request->text);

        })
        ->orderBy('applications.date', 'DESC')
        ->paginate(30);

        return $data;

    }

}