<?php

namespace App\Models;

use DB;

class ApplicationModel
{

    /**
     * Добавление новой заявки в БД
     */
    public static function createNewApplication($data) {

        return DB::table('applications')->insertGetId($data);

    }

    /**
     * Последний идентификатор заявки заказчика
     * 
     * @param Int $id Идентификатор заказчика
     */
    public static function getLastIdApplication($id) {

        return DB::table('applications')->where('clientId', $id)->max('ida');

    }

    /**
     * Счетчик открытых заявок по проектам
     */
    public static function getCountActiveApplication($clients = []) {

        $data = DB::table('applications')
        ->select('clientId', 'project', DB::raw('COUNT(id) as count'))
        ->where([
            ['done', NULL],
            ['combine', NULL],
            ['del', NULL],
        ])
        ->whereIn('clientId', $clients)
        ->groupBy('clientId', 'project')
        ->get();

        return $data;

    }

    public static function getApplicationsList($request) {

        $where = [];

        // Фильтр по проектам
        if ($request->project)
            $where[] = ['applications.project', $request->project];

        // Только актуальные заявки
        if ($request->actual) {
            $where[] = ['applications.done', NULL];
            $where[] = ['applications.combine', NULL];
            $where[] = ['applications.del', NULL];
        }

        // Список вариантов объединения заявок
        if ($request->combinelist AND $request->bus) {
            $where[] = ['applications.combine', NULL];
            $where[] = ['applications.del', NULL];
            $where[] = ['applications.bus', $request->bus];
            $where[] = ['applications.id', '!=', $request->id];
            $where[] = ['applications.clientId', $request->clientId];
        }

        $data = DB::table('applications')
        ->select(
            'applications.*',
            'projects.login as clientLogin',
            'projects.name as clientName'
        )
        ->join('projects', 'projects.id', '=', 'applications.clientId')
        ->where($where);

        if ($request->client)
            $data = $data->whereIn('applications.clientId', is_array($request->client) ? $request->client : [$request->client]);

        return $data->orderBy('applications.priority', 'DESC')->orderBy('applications.date', 'DESC')->paginate(25);

    }

    /**
     * Получение основных данных одной заявки
     */
    public static function getApplicationData($id) {

        $data = DB::table('applications')
        ->select(
            'applications.*',
            'projects.login as clientLogin',
            'projects.name as clientName',
            'projects.telegram',
            'projects.bottoken'
        )
        ->join('projects', 'projects.id', '=', 'applications.clientId');

        if (is_array($id))
            $data = $data->whereIn('applications.id', $id);
        else
            $data = $data->where('applications.id', $id)->limit(1);
        
        $data = $data->get();
        
        if (is_array($id))
            return $data;

        return count($data) ? $data[0] : false;

    }

    /**
     * Установка проблемной заявки
     */
    public static function setApplicationProblem($id) {

        return DB::table('applications')->where('id', $id)->limit(1)->update(['problem' => 1]);

    }

    /**
     * Установка проблемной заявки
     */
    public static function deleteApplication($request) {

        return DB::table('applications')
        ->where('id', $request->id)
        ->limit(1)
        ->update([
            'delUserId' => $request->__user->id ?? false,
            'delComment' => $request->comment,
            'del' => DB::raw('NOW()'),
        ]);

    }

    /**
     * Объединение заявок
     */
    public static function combineApplication($request) {

        return DB::table('applications')
        ->where('id', $request->id)
        ->limit(1)
        ->update([
            'combine' => $request->combine,
        ]);

    }

    /**
     * Подсчет объединенных заявок
     */
    public static function getDataCombinedApplication($ids) {

        return DB::table('applications')->whereIn('combine', $ids)->get();

    }

    /**
     * Мтод сохранения информации о загруженных файлах
     */
    public static function storagedFilesData($data) {

        return DB::table('files')->insertGetId([
            'name' => $data['name'],
            'razdel' => $data['razdel'],
            'size' => $data['size'],
            'ext' => $data['ext'],
            'path' => $data['path'],
            'filename' => $data['filename'],
            'mimeType' => $data['mimeType'],
            'userId' => $data['userId'],
            'ip' => $data['ip']
        ]);

    }

    /**
     * Метод получения списка изобравжений
     * 
     * @param Array $list
     */
    public static function getImagesData($list) {

        return DB::table('files')->whereIn('id', $list)->get();

    }

    /**
     * Вывод комментариев
     * 
     * @param Array $ids
     */
    public static function getApplicationComments($ids) {

        $data = DB::table('applications_comment')
        ->select(
            'applications_comment.*',
            'files.filename',
            'files.path',
            'files.name',
            'users.firstname',
            'users.lastname',
            'users.fathername'
        )
        ->leftjoin('files', 'files.id', '=', 'applications_comment.image')
        ->leftjoin('users', 'users.id', '=', 'applications_comment.userId');

        if (is_array($ids))
            $data = $data->whereIn('applications_comment.applicationId', $ids);
        else
            $data = $data->where('applications_comment.id', $ids)->limit(1);

        return $data->get();

    }

    /**
     * Запись нового комментария
     */
    public static function writeNewComment($data) {

        return DB::table('applications_comment')->insertGetId($data);

    }

    /**
     * Запись сервиса
     */
    public static function createService($data) {

        return DB::table('applications_service')->insertGetId($data);

    }

    /**
     * Обновление заявки для её завершения
     */
    public static function updateApplicationRowForDone($id, $data) {

        return DB::table('applications')->where('id', $id)->limit(1)->update($data);

    }

}