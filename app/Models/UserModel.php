<?php

namespace App\Models;

use DB;

class UserModel
{
    
    /**
     * Метод получения данных пользователя
     */
    public static function login($data) {

        $data = DB::table('users')
        ->select('id','ban')
        ->where([
            ['login', $data->login],
            ['pass', $data->password]
        ])
        ->get();

        return count($data) ? $data[0] : [];

    }

    /**
     * Метод записи времени последнего посещения
     */
    public static function setLastTime($id = 0) {

        return DB::table('users')
        ->where('id', $id)
        ->limit(1)
        ->update([
            'lastVisit' => date("Y-m-d H:i:s"),
        ]);

    }

    /**
     * Метод получения данных пользователя
     */
    public static function getUserData($id) {

        $data = DB::table('users')
        ->where('id', $id)
        ->get();

        return count($data) ? $data[0] : false;

    }

    /**
     * Метод получения прав пользователя по группе
     */
    public static function getUserGroupAccess($id) {

        $data = DB::table('users_group')->where('id', $id)->limit(1)->get();

        return count($data) ? $data[0] : [];

    }

    /**
     * Метод получения прав пользователя
     * 
     * @param Int $id
     * 
     * @param Object
     */
    public static function getUserAccess($id = 0) {

        return DB::table('users_access')
        ->where('userId', $id)
        ->get();

    }

    /**
     * Удаление старых прав сотрудника
     */
    public static function removeOldUserAccess($id, $arr = []) {

        return DB::table('users_access')
        ->where('userId', $id)->whereIn('access', $arr)
        ->delete();

    }

    /**
     * Добавление новых индивидуальных прав
     */
    public static function addNewUserAccess($arr) {

        return DB::table('users_access')->insert($arr);

    }

    /**
     * Метод данных пользователя по токену
     * 
     * @param String $token Искомый токен
     */
    public static function getUserDataFromToken($token) {

        $data = DB::table('users_token')
        ->where([
            ['token', $token],
            ['del', null]
        ])
        ->get();

        return count($data) ? $data[0] : false;

    }


    /**
     * Метод записи токена
     */
    public static function createToken($token, $id) {

        return DB::table('users_token')->insert([
            'userId' => $id,
            'token' => $token,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'agent' => $_SERVER['HTTP_USER_AGENT']
        ]);

    }

    /**
     * Удаление токена
     */
    public static function deleteToken($token) {

        return DB::table('users_token')
        ->where('token', $token)
        ->update(['del' => date("Y-m-d H:i:s")]);

    }

    /**
     * Списоквсех сотрудников
     */
    public static function getUsersList($offset = 0, $id = false) {

        $data = DB::table('users')
        ->select('users.*', 'users_group.admin', 'users_group.name', 'users_group.color')
        ->leftJoin('users_group', 'users.groupId', '=', 'users_group.id')
        ->orderBy('firstname');

        if ($id)
            $data = $data->where('users.id', $id)->limit(1);
        else
            $data = $data->offset($offset)->limit(40);

        $data = $data->get();

        return count($data) == 1 ? $data[0] : $data;

    }

    /**
     * Список груп сотрудников
     */
    public static function getGroupList($id = false, $only = false) {

        $data = DB::table('users_group');

        if ($id)
            $data = $data->where('id', $id);

        $data = $data->orderBy('name')->get();

        if ($only)
            return count($data) ? $data[0] : false;

        return $data;

    }

    /**
     * Список колонок таблицы группы пользователей с комментариями
     */
    public static function getAccessRowsGroup() {

        $data = DB::table('information_schema.COLUMNS')
        ->select('COLUMN_COMMENT', 'COLUMN_NAME')
        ->where([
            ['TABLE_NAME', 'users_group'],
            ['TABLE_SCHEMA','repairs.kolgaev.ru'],
        ])
        ->whereNotIn('COLUMN_NAME', [
            'id', 'name', 'color', 'descript'
        ])
        ->get();

        return $data;

    }

    /**
     * Сохранение параметров группы
     */
    public static function saveGroupAccess($id, $access) {

        return DB::table('users_group')
        ->where('id', $id)->limit(1)
        ->update($access);

    }

    /**
     * Метод поиска логина
     */
    public static function checkLogin($login) {

        return DB::table('users')->where('login', $login)->count();

    }

    /**
     * Метод поиска номера телефона
     */
    public static function checkPhone($phone) {

        return DB::table('users')->where('phone', $phone)->count();

    }

    /**
     * Метод записи нового сотрудника
     * 
     * @param Request $request
     * 
     * @return Int
     */
    public static function addNewUser($request) {

        return DB::table('users')->insertGetId([
            'login' => $request->login,
            'phone' => $request->phone,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'fathername' => $request->fathername,
            'pass' => $request->pass,
            'groupId' => $request->group,
        ]);

    }

    /**
     * Обновление данных сотрудника
     * 
     * @param Request $request
     * 
     * @return Bool
     */
    public static function userDataUpdate($request) {

        return DB::table('users')->where('id', $request->id)->limit(1)
        ->update([
            'phone' => $request->phone,
            'email' => $request->email,
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'fathername' => $request->fathername,
            'groupId' => $request->group,
        ]);

    }

    /**
     * Метод записи истории изменений пароля
     */
    public static function writePassStory($data) {

        return DB::table('users_pass_story')->insert([
            'userId' => $data['userId'],
            'pass' => $data['pass'],
        ]);

    }

    /**
     * Блокировка/Разблокировка сотрудника
     * 
     * @param Int $id
     * @param Int $ban
     * 
     * @return Bool
     */
    public static function userBan($id, $ban) {

        return DB::table('users')
        ->where('id', $id)->limit(1)
        ->update([
            'ban' => $ban,
        ]);

    }

    /**
     * Метод поиска наименование группы
     */
    public static function checkGroupName($name) {

        return DB::table('users_group')->where('name', $name)->count();

    }

    /**
     * Подсчет количества пользователей по каждой группе
     * 
     * @param Array $id Массив идентификаторов групп
     */
    public static function getCountUsersForGroup($id = []) {

        return DB::table('users')
        ->select('groupId', DB::raw('COUNT(id) as count'))
        ->where('ban', 0)->whereIn('groupId', $id)
        ->groupBy('groupId')
        ->get();

    }

    /**
     * Метод создания новой группы
     * 
     * @param Request $request
     * 
     * @return Int
     */
    public static function addNewUsersGroup($request) {

        return DB::table('users_group')->insertGetId([
            'name' => $request->namegroup,
            'descript' => $request->descriptgroup,
            'color' => $request->colorgroup,
        ]);

    }

    /**
     * Обновление данных группы
     * 
     * @param Request $request
     * 
     * @return Bool
     */
    public static function usersGroupDataUpdate($request) {

        return DB::table('users_group')
        ->where('id', $request->id)->limit(1)
        ->update([
            'name' => $request->namegroup,
            'descript' => $request->descriptgroup,
            'color' => $request->colorgroup,
        ]);

    }


}