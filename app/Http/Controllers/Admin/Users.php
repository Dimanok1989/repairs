<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Main;
use App\Http\Controllers\Auth\User;
use Illuminate\Http\Request;
use Session;
use Cookie;
use Crypt;

use App\Models\UserModel;
use App\Models\ProjectModel;
use DB;

class Users extends Main
{

    /**
     * Получение списка пользователей
     */
    public static function getUsersList(Request $request) {

        // Првоерка прав доступа к разделу
        if (!$chek = parent::checkRight('admin', $request->token))
            return parent::error("Доступ к списку сотрудников ограничен", 1001);

        // Смещение в БД
        $page = (int) $request->page > 1 ? (int) $request->page : 1;
        $offset = $request->page ? $page * 40 : 0;

        // Список пользвоателей
        $users = UserModel::getUsersList($offset);
        
        foreach ($users as $key => $row)
            $users[$key] = self::modUserRow($row);

        return parent::json([
            'page' => $page+1,
            'users' => $users,
            'end' => count($users) < 40 ? true : false, // Больше строк нет
        ]);

    }

    /**
     * Метод обработки каждой строки пользователя для пдополнения её недостающими данными
     * 
     * @param Object $row Строка с данными пользователя
     * 
     * @return Object
     */
    public static function modUserRow($row) {

        $row->fio = $row->firstname ? $row->firstname : "";
        $row->fio .= $row->lastname ? " {$row->lastname}" : "";
        $row->fio .= $row->fathername ? " {$row->fathername}" : "";
        $row->fio = trim($row->fio) != "" ? trim($row->fio) : false;

        $row->date = parent::createDate($row->lastVisit);

        return $row;

    }

    /**
     * Метод формирвоания данных для создания нового сотрудника
     */
    public static function getDataForUser(Request $request) {

        if (!$chek = parent::checkRight('admin', $request->token))
            return parent::error("Доступ к данным сотрудников ограничен", 2000);

        $user = false;

        if ($request->id !== false AND $request->id !== null) {

            if (!$user = UserModel::getUsersList(false, $request->id))
                return parent::error("Данные пользователя не получены", 3001);

        }

        return parent::json([
            'user' => $user,
            'group' => UserModel::getGroupList(),
        ]);

    }

    /**
     * Метод соххранения данных пользователя
     */
    public static function saveUser(Request $request) {

        if (!$chek = parent::checkRight('admin', $request->token))
            return parent::error("Доступ к изменению данных сотрудников ограничен", 2000);

        $inputs = [];
        if (!$request->firstname)
            $inputs[] = 'firstname';

        if (!$request->lastname)
            $inputs[] = 'lastname';

        if (!$request->login AND !$request->id)
            $inputs[] = 'login';

        if (!$request->group)
            $inputs[] = 'group';

        if ($inputs)
            return parent::error("Не заполнены обязательные поля", 2001, $inputs);

        // Обновление данных сотрудника
        if ($request->id)
            return self::userDataUpdate($request);

        // Проверка логина
        if ($login = UserModel::checkLogin($request->login))
            return parent::error("Данный логин уже используется", 2002, ['login']);

        $phone = $request->phone ? parent::checkPhone($request->phone) : NULL;

        // Првоерка номера телефона
        if ($request->phone AND !$phone)
            return parent::error("Номер телефона указан в неправильном формате", 2003, ['phone']);
        elseif ($request->phone AND UserModel::checkPhone($phone))
            return parent::error("Номер телефона уже используется дургим сотрудником", 2004, ['phone']);
        else
            $request->phone = $phone;

        // Генерация случайного пароля
        $pass = User::getRandomPass();

        // Шифровка пароля
        $request->pass = User::passHash($pass);

        // Создание нового пользователя
        if (!$id = UserModel::addNewUser($request))
            return parent::error("Новый пользователь не создан", 2005);

        // Данные для строки пользователя
        if (!$user = UserModel::getUsersList(false, $id))
            return parent::error("Данные пользователя не получены", 2006);

        // Запись истории пароля
        UserModel::writePassStory([
            'userId' => $id,
            'pass' => Crypt::encrypt($pass),
        ]);

        return parent::json([
            'id' => $id,
            'user' => self::modUserRow($user),
            'pass' => $pass,
        ]);

    }

    /**
     * Метод обновления данных сотрудника
     */
    public static function userDataUpdate($request) {

        // Старые данные пользователя
        if (!$old = UserModel::getUsersList(false, $request->id))
            return parent::error("Данные пользователя не получены", 4001);

        $old = self::modUserRow($old);

        // Проверка логина
        if ($login = UserModel::checkLogin($request->login) AND $old->login != $request->login)
            return parent::error("Данный логин уже используется", 4002, ['login']);

        // Првоерка номера телефона
        $phone = parent::checkPhone($request->phone);
        
        if ($request->phone AND !$phone)
            return parent::error("Номер телефона указан в неправильном формате", 4003, ['phone']);
        elseif (UserModel::checkPhone($phone) AND $old->phone != $phone)
            return parent::error("Номер телефона уже используется дургим сотрудником", 4004, ['phone']);
        else
            $request->phone = $phone;

        // Обновление данных сотрудника
        $upd = UserModel::userDataUpdate($request);

        // Данные для строки пользователя
        if (!$user = UserModel::getUsersList(false, $request->id))
            return parent::error("Обновленные данные сотрудника не получены", 4005);

        return parent::json([
            'upd' => $upd,
            'id' => $request->id,
            'user' => self::modUserRow($user),
        ]);

    }

    /**
     * Блокировка/Разблокировка сотрудника
     */
    public static function userBan(Request $request) {

        $ban = $request->ban == 1 ? 0 : 1;

        if (!$upd = UserModel::userBan($request->id, $ban))
            return parent::error("Не удалось обновить данные", 5001);
        
        if (!$user = UserModel::getUsersList(false, $request->id))
            return parent::error("Обновленные данные сотрудника не получены", 5002);

        return parent::json([
            'ban' => $upd,
            'user' => self::modUserRow($user),
        ]);

    }


    /**
     * Получение списка групп пользователей
     */
    public static function getUsersGroupsList(Request $request) {

        // Првоерка пав доступа к разделу
        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к разделу группы пользователей ограничен", 6000);

        // Смещение в БД
        $page = (int) $request->page > 1 ? (int) $request->page : 1;
        $offset = $request->page ? $page * 40 : 0;

        // Список пользвоателей
        $groups = UserModel::getGroupList();

        return parent::json([
            'page' => $page,
            'groups' => self::getCountUsersForGroup($groups),
            'end' => count($groups) < 40 ? true : false, // Больше строк нет
        ]);

    }

    /**
     * Подсчет количества активных пользователей в группе
     */
    public static function getCountUsersForGroup($groups) {

        $id = [];
        foreach ($groups as $row)
            $id[] = $row->id;
        
        $count = UserModel::getCountUsersForGroup($id);
        $arr = [];
        foreach ($count as $row)
            $arr[$row->groupId] = $row->count;

        foreach ($groups as $key => $row)
            $groups[$key]->countUsers = $arr[$row->id] ?? 0;

        return $groups;

    }

    /**
     * Данные для модального окна по группе пользовтелей
     */
    public static function getDataForUsersGroups(Request $request) {

        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к разделу группы пользователей ограничен", 6001);

        $group = [];

        if ($request->id !== false AND $request->id !== null) {

            if (!$group = UserModel::getGroupList($request->id))
                return parent::error("Данные пользователя не получены", 6002);

            $group = self::getCountUsersForGroup($group);

        }

        return parent::json([
            'group' => count($group) ? $group[0] : false,
        ]);

    }

    /** 
     * Сохранение группы пользователей
     */
    public static function saveGroup(Request $request) {

        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к разделу группы пользователей ограничен", 6003);

        $inputs = [];
        if (!$request->namegroup)
            $inputs[] = 'namegroup';

        if ($inputs)
            return parent::error("Не заполнены обязательные поля", 6004, $inputs);

        // Обновление данных сотрудника
        if ($request->id)
            return self::usersGroupDataUpdate($request);

        // Проверка совпадения имени группы
        if (UserModel::checkGroupName($request->namegroup))
            return parent::error("Группа пользователей с таким именем уже существует, придумайте другое имя, чтобы не путаться в дальнейшем", 6005, ['namegroup']);

        // Создание нового пользователя
        if (!$id = UserModel::addNewUsersGroup($request))
            return parent::error("Новая группа не создана", 6006);

        // Данные для строки пользователя
        if (!$group = UserModel::getGroupList($id))
            return parent::error("Данные группы не получены", 6007);

        $group = self::getCountUsersForGroup($group);

        return parent::json([
            'id' => $id,
            'group' => count($group) ? $group[0] : false,
        ]);

    }

    /**
     * Обновление данных группы
     */
    public static function usersGroupDataUpdate($request) {

        // Старые данные группы
        if (!$old = UserModel::getGroupList($request->id, true))
            return parent::error("Данные группы пользователя не получены", 6008);

        // Проверка логина
        if (UserModel::checkGroupName($request->login) AND $old->name != $request->namegroup)
            return parent::error("Данный логин уже используется", 6009, ['login']);

        // Обновление данных группы
        $upd = UserModel::usersGroupDataUpdate($request);

        // Данные для строки пользователя
        if (!$group = UserModel::getGroupList($request->id))
            return parent::error("Обновленные данные группы пользователей не получены", 6010);

        $group = self::getCountUsersForGroup($group);

        return parent::json([
            'upd' => $upd,
            'id' => $request->id,
            'group' => count($group) ? $group[0] : false,
        ]);

    }

    /**
     * Метод получения списка настройки прав доступа по группе
     */
    public static function usersGroupGetAccess(Request $request) {

        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к настрокам прав группы пользователей ограничен", 6011);

        if (!$group = UserModel::getGroupList($request->id))
            return parent::error("Данные группы пользователей не получены", 6012);

        $group = count($group) ? $group[0] : false;

        // Список колонок с описанием
        $access = UserModel::getAccessRowsGroup();

        // Получение списка заказчиков
        $clients = \App\Models\ProjectModel::getProjectsList();

        // Права доступа группы к заказчикам
        $clientsAccessData = \App\Models\ProjectModel::getProjectsAccessList($request->id);
        $clientsAccess = [];

        // Сбор данных открытых прав доступа к заказчику
        foreach ($clientsAccessData as $row)
            $clientsAccess[$row->projectId] = (int) $row->access;

        return parent::json([            
            'access' => $access,
            'group' => $group,
            'clients' => $clients,
            'clientsAccess' => $clientsAccess,
        ]);

    }

    /**
     * Сохранение прав доступа по группе
     */
    public static function saveGroupAccess(Request $request) {

        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к настрокам прав группы пользователей ограничен", 6013);

        // Данные с текущими правами группы
        if (!$group = UserModel::getGroupList($request->id))
            return parent::error("Данные группы пользователей не получены", 6014);

        $group = count($group) ? $group[0] : false;

        // Сбор данных
        $access = [];
        foreach ($request->input() as $key => $value)
            if ($key != 'id' AND isset($group->$key) AND $value == "on")
                $access[$key] = 1;

        // Дополнение пустых данных
        foreach ($group as $key => $value)
            if (!in_array($key, ['id','name','descript','color']) AND !isset($access[$key]))
                $access[$key] = 0;
        
        // Обновление данных в БД
        $upd = UserModel::saveGroupAccess($request->id, $access);

        // Обновление прав доступа к заказчикам
        $clientAccess = \App\Http\Controllers\Admin\Projects::saveClientAccess($request);

        return parent::json([
            'upd' => $upd,
            'access' => $access,
            'group' => $group,
            'clientAccess' => $clientAccess,
        ]);

    }


    /**
     * Вывод индифидуальных прав сотрудника
     */
    public static function userGetAccess(Request $request) {

        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к настрокам прав пользователей ограничен", 6015);

        // Данные сотрудника
        if (!$user = UserModel::getUsersList(false, $request->id))
            return parent::error("Данные сотрудника не получены", 6016);

        $user = self::modUserRow($user);

        // Индивидуальные права
        $useraccess = [];
        foreach (UserModel::getUserAccess($user->id) as $row)
            $useraccess[$row->access] = $row->value;

        // Список колонок с описанием
        $access = [];
        foreach (UserModel::getAccessRowsGroup() as $value) {

            $access[$value->COLUMN_NAME] = $value->COLUMN_COMMENT;
            
            if (!isset($useraccess[$value->COLUMN_NAME]))
                $useraccess[$value->COLUMN_NAME] = false;

        }

        return parent::json([
            'access' => $access,
            'useraccess' => $useraccess,
            'user' => $user,
        ]);

    }

    /**
     * Метод сохранения индивидуальных прав
     */
    public static function saveUserAccess(Request $request) {

        if (!parent::checkRight('admin', $request->token))
            return parent::error("Доступ к настрокам прав пользователей ограничен", 6017);

        // Данные сотрудника
        if (!$user = UserModel::getUsersList(false, $request->id))
            return parent::error("Данные сотрудника не получены", 6018);

        $user = self::modUserRow($user);

        // Индивидуальные права
        $useraccess = [];
        foreach (UserModel::getUserAccess($user->id) as $row)
            $useraccess[$row->access] = $row->value;

        // Список колонок прав с описанием
        $access = [];
        foreach (UserModel::getAccessRowsGroup() as $value)
            $access[$value->COLUMN_NAME] = $value->COLUMN_COMMENT;

        // Новые индивидуальные права
        $newaccess = [];
        foreach ($request->input() as $key => $row)
            if ($key != "id" AND strripos($key, "sel-") !== false)
                $newaccess[str_replace("sel-", "", $key)] = (int) $row;

        // Поиск прав для удаления
        $delaccess = [];
        foreach ($useraccess as $key => $row)
            if (!isset($newaccess[$key]) OR isset($newaccess[$key]))
                $delaccess[] = $key;

        // Удаление старых прав
        if ($delaccess)
            UserModel::removeOldUserAccess($user->id, $delaccess);

        // Добавление новых прав
        $arr = [];
        foreach ($newaccess as $key => $value) {
            $arr[] = [
                'userId' => $user->id,
                'access' => $key,
                'value' => $value,
            ];
        }
        
        if ($arr)
            UserModel::addNewUserAccess($arr);

        return parent::json([            
            'access' => $access,
            'useraccess' => $useraccess,
            'newaccess' => $newaccess,
            'delaccess' => $delaccess,
        ]);

    }

    /**
     * Список пользователей доавбленных в избранный список коллег другими пользователями
     */
    public static function getFavoritUsersList(Request $request) {

        // Спиоск сотрудников в друзьях
        $users = [];

        foreach (UserModel::getFavoritUsersList($request) as $user) {
            $user->favorit = "none";
            $users[] = self::createRowCollegue($user);
        }

        // Проверка доступа к заказчику
        if ($request->projectId)
            return self::checkUserListForClient($users, $request->projectId);

        return $users;

    }

    /**
     * Поиск коллег
     */
    public static function searchCollegue(Request $request) {

        $users = [];

        foreach (UserModel::searchCollegue($request) as $user)
            $users[] = self::createRowCollegue($user);

        return self::checkUserListForClient($users, $request->projectId);

    }

    /**
     * Обработка строки польвзаотеля для списка коллег
     */
    public static function createRowCollegue($row) {

        if ($row->indAdmin !== null)
            $row->admin = $row->indAdmin;

        unset($row->indAdmin);

        $row->fio = parent::getUserFio($row->firstname, $row->lastname, $row->fathername, 0);

        return $row;

    }

    /**
     * Перепроверка пользователей по досутпу к заказчику
     */
    public static function checkUserListForClient($rows, $projectId) {

        $users = [];

        $userIds = $groupIds = [];
        foreach ($rows as $user) {

            $ids[] = $user->id;

            if (!in_array($user->groupId, $groupIds))
                $groupIds[] = $user->groupId;

        }

        $userAcc = $groupAcc = [];

        foreach (ProjectModel::getAccessDoneProjectList($projectId, 1, $groupIds) as $row)
            if ($row->access == 1)
                $groupAcc[$row->typeId] = $row->access;

        foreach (ProjectModel::getAccessDoneProjectList($projectId, 2, $userIds) as $row)
            if ($row->access == 1)
                $userAcc[$row->typeId] = $row->access;

        foreach ($rows as $user) {
            
            if (isset($userAcc[$user->id]) OR isset($groupAcc[$user->groupId]) OR $user->admin == 1)
                $users[] = $user;

        }

        return $users;

    }

    /**
     * Добавление/Удаление коллеги из избранного
     */
    public static function userFavorit(Request $request) {

        if (!$request->id OR $request->id == "")
            return parent::error("Неправильный идентификатор", 7000);

        $fav = UserModel::getFavCollegueData($request);

        // Добавление в избранное
        if (!count($fav)) {
            return parent::json([
                'add' => UserModel::addFavCollegue($request),
            ]);
        }

        // Удаление из избранного
        return parent::json([
            'del' => UserModel::delFavCollegue($request),
        ]); 

    }

}