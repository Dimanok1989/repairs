<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Main;

use Illuminate\Http\Request;
use Session;
use Validator;

use App\Models\ProjectModel;

class Projects extends Main
{

    /** Список проектов в разделе */
    static $projects = [
        1 => "Видеонаблюдение",
        2 => "Навигация",
        3 => "Система автоинформирования",
    ];

    /**
     * Список проектов в разделе
     */
    public static function getProjectsName($id = false) {

        return self::$projects;

    }

    public static function emodjiproect($id) {

        switch ($id) {
            case '1':
                $emo = "🎥";
                break;

            case '2':
                $emo = "📡";
                break;

            case '3':
                $emo = "📟";
                break;
            
            default:
                $emo = "";
                break;
        }

        return $emo;

    }

    /**
     * Список проектов в заявок
     */
    public static function getProjectsList(Request $request) {

        // Проверка прав доступа к разделу
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к настройке заказчика ограничен", 1001);

        // Смещение в БД
        $page = (int) $request->page > 1 ? (int) $request->page : 1;
        $offset = $request->page ? $page * 40 : 0;

        $rows = ProjectModel::getProjectsList();

        return parent::json([
            'page' => $page+1,
            'rows' => $rows,
            'end' => count($rows) < 40 ? true : false, // Больше строк нет
        ]);

    }

    /**
     * Получение всех данных раздела заказчика
     */
    public static function getProjectsData(Request $request) {

        // Основные данные раздела
        if (!$data = ProjectModel::getProjectsList($request->id))
            return parent::error("Данные раздела не найдены", 1002);

        $data = self::getClientAllDataOneRow($data);
        $projects = self::getProjectsName();

        return parent::json([
            'project' => $data,
            'types' => $projects,
        ]);

    }

    public static function getClientAllDataOneRow($data) {

        // Получение списка пунктов неисправностей
        $data->break = (Object) [];
        foreach (ProjectModel::getProjectBreakList($data->id) as $row)
            $data->break->{$row->type}[] = $row;

        // Получение списка подпунктов ремонта
        $subrepair = (Object) [];
        foreach (ProjectModel::getProjectSubRepairList($data->id) as $row)
            $subrepair->{$row->repairId}[] = $row;

        // Получение списка пунктов ремонта
        $data->repair = (Object) [];
        foreach (ProjectModel::getProjectRepairList($data->id) as $row) {
            $row->subpoints = isset($subrepair->{$row->id}) ? $subrepair->{$row->id} : [];
            $data->repair->{$row->type}[] = $row;
        }

        $data->date = parent::createDate($data->create_at);

        return $data;

    }

    /**
     * Сохранение пункта неисправности
     */
    public static function savePointBreak(Request $request) {

        // Првоерка прав доступа
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ ограничен", 1003);

        if (!$request->name)
            return parent::error("Не указано наименование пункта неисправности", 1004, ['name']);

        $data = [
            'razdel' => $request->razdel,
            'type' => $request->project,
            'name' => $request->name,
            'userIdAdd' => $request->__user->id ?? NULL,
        ];

        $id = ProjectModel::createNewPointBreak($data);

        $data['id'] = $id;
        $data['del'] = 0;

        return parent::json([
            'type' => $request->type,
            'id' => $id,
            'point' => $data,
        ]);

    }

    /**
     * Удаление возврат пункта неисправностей
     */
    public static function removeBreakPoint(Request $request) {

        // Првоерка прав доступа
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ ограничен", 1005);

        // Данные пункта
        $point = ProjectModel::getProjectBreakList(false, $request->id);
        $point = count($point) ? $point[0] : false;

        if (!$point)
            return parent::error("Данные не найдены", 1006);

        // Идентификатор удаления
        $del = $point->del == 1 ? 0 : 1;

        // Обнволение данных для вывода
        $point->del = $del;

        ProjectModel::pointBreakShow($point->id, $del);

        return parent::json([
            'point' => $point,
        ]);

    }

    /**
     * Сохранение пункта по емонту
     */
    public static function savePointRepair(Request $request) {

        // Првоерка прав доступа
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ ограничен", 1007);

        $inputs = [];

        if (!$request->name)
            $inputs[] = "name";

        if (!$request->norma AND !$request->master)
            $inputs[] = "norma";

        if ($inputs)
            return parent::error("Заполнены не все поля", 1008, $inputs);

        // Сохранение подпункта ремонта
        if ($request->point)
            return self::saveSubPointRepair($request);

        $data = [
            'razdel' => $request->razdel,
            'type' => $request->project,
            'name' => $request->name,
            'changed' => $request->forchanged ? 1 : 0,
            'fond' => $request->forchangedfond ? 1 : 0,
            'userIdAdd' => $request->__user->id ?? NULL,
            'master' => 0,
            'norm' => 0,
        ];

        if ($request->master)
            $data['master'] = 1;
        else
            $data['norm'] = $request->norma;

        $id = ProjectModel::createNewPointRepair($data);
        
        $data['id'] = $id;
        $data['del'] = 0;

        return parent::json([
            'type' => $request->type,
            'point' => $data,
        ]);

    }

    /**
     * Сохранение подпункта ремонта
     */
    public static function saveSubPointRepair($request) {

        $data = [
            'razdel' => $request->razdel,
            'repairId' => $request->point,
            'name' => $request->name,
            'changed' => $request->forchanged ? 1 : 0,
            'fond' => $request->forchangedfond ? 1 : 0,
            'userIdAdd' => $request->__user->id ?? NULL,
            'norm' => $request->norma,
        ];

        $id = ProjectModel::createNewSubPointRepair($data);
        
        $data['id'] = $id;
        $data['del'] = 0;

        return parent::json([
            'slave' => $request->point,
            'project' => $request->project,
            'point' => $data,
        ]);

    }

    /**
     * Удаление возврат пункта ремонта
     */
    public static function removeRepairPoint(Request $request) {

        // Првоерка прав доступа
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ ограничен", 1009);

        // Данные пункта
        $point = ProjectModel::getProjectRepairList(false, $request->id);
        $point = count($point) ? $point[0] : false;

        if (!$point)
            return parent::error("Данные не найдены", 1010);

        // Идентификатор удаления
        $del = $point->del == 1 ? 0 : 1;

        // Обнволение данных для вывода
        $point->del = $del;

        ProjectModel::pointRepairShow($point->id, $del);

        return parent::json([
            'point' => $point,
        ]);

    }

    /**
     * Удаление возврат подпункта ремонта
     */
    public static function subPointRepairShow(Request $request) {

        // Проверка прав доступа
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ ограничен", 1011);

        // Данные пункта
        $point = ProjectModel::getProjectSubRepairList(false, $request->id);
        $point = count($point) ? $point[0] : false;

        if (!$point)
            return parent::error("Данные не найдены", 1012);

        // Идентификатор удаления
        $del = $point->del == 1 ? 0 : 1;

        // Обнволение данных для вывода
        $point->del = $del;

        ProjectModel::subPointRepairShow($point->id, $del);

        return parent::json([
            'point' => $point,
        ]);

    }

    /**
     * Метод сохранения настроек заказчика
     */
    public static function saveSettingsProject(Request $request) {

        // Проверка прав доступа
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ ограничен", 1012);

        // Проверка идентификатора заказчика
        $request->id = (int) $request->id;
        if (!$request->id)
            return parent::error("Ошибка идентификатора заказчика", 1013);

        // Првоерка заполнение обязательных полей
        $inputs = [];
        
        // Проверка наименования
        if (!$request->name)
            $inputs[] = "name";

        if ($inputs)
            return parent::error("Заполнены не все обязательные поля", 1014, $inputs);

        // Формирование данных
        $data = [
            'name' => $request->name,
            'telegram' => $request->telegram,
            'access' => $request->access ? 1 : 0,
            'listpoints' => $request->listpoints ? 1 : 0,
        ];

        // Обновление данных заказчика
        ProjectModel::setSettingClientData($request->id, $data);

        // Запись истории обвноелния данных
        ProjectModel::setSettingClientDataLogChange([
            'projectId' => $request->id,
            'userId' => $request->__user->id,
            'datastring' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);

        return parent::json([
            'updated' => $data, 
        ]);

    }

    /**
     * Метод сохранения прав доступа группы к заказчику
     */
    public static function saveClientAccess($request) {

        $data = []; // Данные для записи
        $access = []; // Список идентификтаоров, с открытым доступом в момент сохранения

        // Сбор данных для обвноления прав доступа
        if ($request->clientAccess) {
            foreach ($request->clientAccess as $projectId) {
                $access[] = $projectId;
                $data[] = [
                    'projectId' => $projectId,
                    'typeAccess' => 1,
                    'typeId' => $request->id,
                    'access' => 1,
                ];
            }
        }

        // Старые данные прав доступа группы к заказчикам
        $clientsAccessData = ProjectModel::getProjectsAccessList($request->id);

        // Сбор данных имеющихся настроек прав доступа к заказчику
        $clientsAccess = [];
        foreach ($clientsAccessData as $row) {

            if ($row->access == "1" AND !in_array($row->projectId, $access)) {
                $data[] = [
                    'projectId' => $row->projectId,
                    'typeAccess' => 1,
                    'typeId' => $request->id,
                    'access' => 0,
                ];
            }

        }

        // Выолнение запроса на обновление
        ProjectModel::updateProjectsAccessList($data);

        return $data;

    }

    /**
     * Создание нового заказчика
     */
    public static function saveNewProject(Request $request) {

        $inputs = [];

        if (!$request->name)
            $inputs[] = "name";

        if (!$request->login)
            $inputs[] = "login";

        if ($inputs)
            return parent::error("Заполнены не все обязательные поля", 1015, $inputs);

        // Валиация логина
        $validator = Validator::make($request->all(), [
            'login' => 'regex:/^[a-z0-9]+$/i|max:15',
        ]);

        if ($validator->fails())
            return parent::error("Логин должен содежать только латинские буквы или цифры, не иметь побелов и состоять не более, чем из 15 символов", 1016, ["login"]);

        // Првоерка наличия логина
        if (ProjectModel::getProjectsIdFromName($request->login))
            return parent::error("Такой логин уже используется", 1017, ["login"]);

        // Создание нового логина
        ProjectModel::saveNewProject($request);

        return parent::json([
            'project' => ProjectModel::getProjectsIdFromName($request->login),
        ]);

    }

}