<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;

class PagesAdmin extends Main
{

    /** Страница администрирования сотрудников  */
    public static function users() {

        if (!parent::checkRight('admin'))
            return abort(404);

        return view('admin.users');

    }

    /** Страница групп пользователей */
    public static function usersgroups() {

        if (!parent::checkRight('admin'))
            return abort(404);

        return view('admin.usersgroups');

    }

    /** Настройка проектов */
    public static function projects() {

        if (!parent::checkRight('admin'))
            return abort(404);

        return view('admin.projects');

    }

    /** Страница настройки проекта */
    public static function project(Request $request) {

        if (!parent::checkRight('admin') OR !$data = \App\Models\ProjectModel::getProjectsList($request->id))
            return abort(404);

        return view('admin.project', [
            'id' => $request->id,
            'data' => $data,
        ]);

    }

}