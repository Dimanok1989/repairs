<?php

namespace App\Models;

use DB;

class ProjectModel
{

    /**
     * Создание нового заказчика
     */
    public static function saveNewProject($request) {

        return DB::table('projects')->insert([
            'name' => $request->name,
            'login' => $request->login,
        ]);

    }

    /**
     * Метод вывода данных проекта 
     */
    public static function getProjectsList($id = false) {

        $data = DB::table('projects');

        if ($id)
            $data = $data->where('id', $id);

        $data = $data->orderBy('name')->get();

        return $id ? ($data[0] ?? false) : $data;

    }

    /**
     * Список прав доступа к заказчикам
     */
    public static function getProjectsAccessList($id = false, $type = 1, $client = false) {

        $where = [
            ['typeAccess', $type],
        ];

        if ($id)
            $where[] = ['typeId', $id];

        if ($client)
            $where[] = ['projectId', $client];

        return DB::table('projects_access')
        ->where($where)
        ->get();

    }

    /**
     * Список открытого доступа к заказчику по идентификаторам сотрудников или групп
     * 
     * @param Int $id Идентификатор заказчика
     * @param Int $type 1 - Группа, 2 - Сотрудник
     * @param Array $ids
     */
    public static function getAccessDoneProjectList($id, $type = 1, $ids) {

        return DB::table('projects_access')
        ->where([
            ['projectId', $id],
            ['typeAccess', $type],
            ['access', 1]
        ])
        ->whereIn('typeId', $ids)
        ->get();

    }
    
    /**
     * Обновление строк доступа к заказчикам
     */
    public static function updateProjectsAccessList($arr = []) {

        $success = [];
        
        foreach ($arr as $row) {
            
            $success[] = DB::statement("INSERT INTO `projects_access` SET `projectId` = '{$row['projectId']}', `typeAccess` = '1', `typeId` = '{$row['typeId']}', `access` = '{$row['access']}' ON DUPLICATE KEY UPDATE `access` = '{$row['access']}'");

        }

        return $success;

    }

    /**
     * Получение идентификатора заказчика по его имени
     */
    public static function getProjectsIdFromName($name = false) {

        $data = DB::table('projects')->where('login', $name)->limit(1)->get();
        return count($data) ? $data[0] : false;

    }

    /**
     * Получение списка заказчиков, доступных пользователю
     * 
     * @param Array $id Список идентификаторов заказчиков, доступных для пользователя
     */
    public static function getProjectsListForUser($id = []) {

        return DB::table('projects')->whereIn('id', $id)->orderBy('name')->get();

    }

    /**
     * Получение списка неисправностей по разделу
     */
    public static function getProjectBreakList($razdel = false, $id = false) {

        $where = [];

        if ($razdel)
            $where[] = ['razdel', $razdel];

        if ($id AND !is_array($id))
            $where[] = ['id', $id];

        $data = DB::table('project_break');

        if ($where)
            $data = $data->where($where);

        if ($id AND is_array($id))
            $data = $data->whereIn('id', $id);
        
        return $data->orderBy('name')->get();

    }

    /**
     * Получение списка пунктов отмены заявки
     */
    public static function getProjectCanseledList($razdel = false, $id = false) {

        $where = [];

        if ($razdel)
            $where[] = ['razdel', $razdel];

        if ($id AND !is_array($id))
            $where[] = ['id', $id];

        $data = DB::table('project_canseled');

        if ($where)
            $data = $data->where($where);

        if ($id AND is_array($id))
            $data = $data->whereIn('id', $id);
        
        return $data->orderBy('name')->get();

    }

    /**
     * Список пунктов завершения
     */
    public static function getProjectRepairsList($ids = []) {

        return DB::table('project_repair')->whereIn('id', $ids)->get();

    }

    /**
     * Список подпунктов завершения
     */
    public static function getProjectSubRepairsList($ids = []) {

        return DB::table('project_repair_subpoints')->whereIn('id', $ids)->get();

    }

    /**
     * Список подпунктов завершения
     */
    public static function getProjectCanselList($ids = []) {

        return DB::table('project_canseled')->whereIn('id', $ids)->get();

    }

    /**
     * Список подпунктов завершения
     */
    public static function getProjectCanselListForClietnProject($request) {

        return DB::table('project_canseled')
        ->where([
            ['razdel', $request->client],
            ['type', $request->project],
            ['del', 0],
        ])->get();

    }

    /**
     * Сохранение нового пункта неисправности
     */
    public static function createNewPointBreak($data) {

        return DB::table('project_break')->insertGetId($data);

    }

    /**
     * Сохранение нового пункта отмены заявки
     */
    public static function createNewPointCansel($data) {

        return DB::table('project_canseled')->insertGetId($data);

    }

    /**
     * Удаление возврат пункта неисправностей
     */
    public static function pointBreakShow($id = false, $del = false) {

        return DB::table('project_break')
        ->where('id', $id)->limit(1)
        ->update([
            'del' => $del
        ]);

    }

    /**
     * Удаление возврат пункта отмены заявки
     */
    public static function pointCanselShow($id = false, $del = false) {

        return DB::table('project_canseled')
        ->where('id', $id)->limit(1)
        ->update([
            'del' => $del
        ]);

    }

    /**
     * Получение списка пунктов ремонта по разделу
     */
    public static function getProjectRepairList($razdel = false, $id = false, $type = false) {

        $where = [];

        if ($razdel)
            $where[] = ['razdel', $razdel];

        if ($id)
            $where[] = ['id', $id];

        if ($type) {
            $where[] = ['type', $type];
            $where[] = ['del', 0];
        }

        return DB::table('project_repair')
        ->where($where)
        ->orderBy('name')->get();

    }

    /**
     * Получение списка подпунктов ремонта по разделу
     */
    public static function getProjectSubRepairList($razdel = false, $id = false, $repairId = false) {

        $where = [];

        if ($razdel)
            $where[] = ['razdel', $razdel];

        if ($id)
            $where[] = ['id', $id];

        if ($repairId)
            $where[] = ['del', 0];

        $data = DB::table('project_repair_subpoints')
        ->where($where);

        if (is_array($repairId))
            $data = $data->whereIn('repairId', $repairId);

        return $data->orderBy('name')->get();

    }

    /**
     * Сохранение нового пункта ремонта
     */
    public static function createNewPointRepair($data) {

        return DB::table('project_repair')
        ->insertGetId($data);

    }

    /**
     * Обновление пункта ремонта
     */
    public static function updatePointRepair($data, $request) {

        return DB::table('project_repair')
        ->where('id', $request->id)
        ->limit(1)
        ->update($data);

    }

    /**
     * Сохранение нового подпункта ремонта
     */
    public static function createNewSubPointRepair($data) {

        return DB::table('project_repair_subpoints')
        ->insertGetId($data);

    }

    /**
     * Обновление подпункта ремонта
     */
    public static function updateSubPointRepair($data, $request) {

        return DB::table('project_repair_subpoints')
        ->where('id', $request->id)
        ->limit(1)
        ->update($data);

    }

    /**
     * Удаление возврат пункта ремонта
     */
    public static function pointRepairShow($id = false, $del = false) {

        return DB::table('project_repair')
        ->where('id', $id)->limit(1)
        ->update([
            'del' => $del
        ]);

    }

    /**
     * Удаление возврат подпункта ремонта
     */
    public static function subPointRepairShow($id = false, $del = false) {

        return DB::table('project_repair_subpoints')
        ->where('id', $id)->limit(1)
        ->update([
            'del' => $del
        ]);

    }

    /**
     * Сохранение настроек заказчика
     */
    public static function setSettingClientData($id, $data) {

        return DB::table('projects')->where('id', $id)->limit(1)->update($data);

    }

    public static function setSettingClientDataLogChange($data) {

        return DB::table('projects_change_log')->insert($data);

    }

}