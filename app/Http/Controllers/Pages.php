<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\Projects;

use Illuminate\Http\Request;
use Session;
use Cookie;

use App\Models\UserModel;
use App\Models\ApplicationModel;

class Pages extends \App\Http\Controllers\Main
{

    /** Главная страница проекта */
    public static function main() {

        if (Session::get('user')) {
            return view('main', [
                'applications' => \App\Http\Controllers\Service\Application::getAllApplicationsData(),
            ]);
        }

        return view('login');
        
    }

    /** Выход */
    public static function logout() {

        if ($user = Session::get('user')) {
        
            UserModel::deleteToken($user->token);
            Session::pull('user');

        }

        return redirect('/')->withCookie(Cookie::forget('token'));
        
    }

    /** Страница подачи заявки */
    public static function addRequest(Request $request) {

        // Поиск данных заказчика по имени
        if (!$data = \App\Models\ProjectModel::getProjectsIdFromName($request->project))
            return abort(404);

        if ($data->access == 0)
            return view('application.stoped', [
                'data' => $data,
            ]);

        // Получение всех остальных данных по проекту
        $data = Projects::getClientAllDataOneRow($data);

        $getprojects = Projects::getProjectsName(); // Все доступные проекты
        $projects = []; // Проекты для вывода

        // dd($data);

        foreach ($data->break as $key => $arr)
            foreach ($arr as $row)
                if ($row->del == 0 AND !isset($projects[$key]))
                    $projects[$key] = isset($getprojects[$key]) ? $getprojects[$key] : "Неизвестно";

        // dump($data);

        $projectskey = false;
        if (count($projects) == 1)
            foreach ($projects as $key => $value)
                $projectskey = $key;

        return view('application.add', [
            'data' => $data,
            'projects' => $projects,
            'count' => count($projects),
            'projectskey' => $projectskey,
        ]);

    }

    /** Страница выбора заказчика для подачи новой заявки */
    public static function SelectForaddApplication() {

        // Првоерка авторизации
        if (!$user = Session::get('user'))
            return abort(404);

        return view('application.selectForNew', [
            'projects' => \App\Models\ProjectModel::getProjectsListForUser($user->clientsAccess),
        ]);

    }

    /** Страница просмотра списка заявок по поиску */
    public static function showApplicationsList(Request $request) {

        return view('application.list', [
            
        ]);

    }

    /** Страница просмотра одной заявки */
    public static function showApplication(Request $request) {

        // Получение идентификатора заявки
        $id = parent::link2dec($request->link);

        // Проверка наличия заявки
        if (!$application = ApplicationModel::getApplicationData($id))
            return abort(404);

        // Переадресация из присоединённой заявки на основную
        if ($application->combine AND !parent::checkRight(['admin','application_combine'])) {
            $combine = parent::dec2link($application->combine);
            return redirect("/id{$combine}");
        }

        if ($application->del AND !parent::checkRight(['admin','application_del']))
            return abort(404);

        $application = \App\Http\Controllers\Service\Application::getApplicationsListEditRow([$application])[0];

        return view('application.main', [
            'application' => $application,
        ]);
        
    }

    /** Страница нстроек личного кабинета */
    public static function userSettings(Request $request) {

        dump(Session::get('user'), $_COOKIE);

        return view('user.settings');

    }

}