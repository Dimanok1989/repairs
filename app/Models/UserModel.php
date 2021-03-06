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
     * Список всех сотрудников
     */
    public static function getUsersList($offset = 0, $id = false) {

        $data = DB::table('users')
        ->select('users.*', 'users_group.admin', 'users_group.name', 'users_group.color')
        ->leftJoin('users_group', 'users.groupId', '=', 'users_group.id')
        ->orderBy('firstname');

        if (is_array($id))
            $data = $data->whereIn('users.id', $id);
        elseif ($id)
            $data = $data->where('users.id', $id)->limit(1);
        else
            $data = $data->offset($offset)->limit(40);

        $data = $data->get();

        return count($data) == 1 ? $data[0] : $data;

    }

    /**
     * Количество активных сотрудников
     */
    public static function countActiveUsers() {

        return DB::table('users')->where('ban', 0)->count();

    }

    /**
     * Количество заблокированных сотрудников
     */
    public static function countBanUsers() {

        return DB::table('users')->where('ban', 1)->count();

    }

    /**
     * Вывод списка пользователей для админ-панели
     */
    public static function getUsersListDataForAdmin($request) {

        $where = [];

        if (!$request->text)
            $where[] = ['ban', 0];

        $data = DB::table('users')
        ->select('users.*', 'users_group.admin', 'users_group.name', 'users_group.color')
        ->leftJoin('users_group', 'users.groupId', '=', 'users_group.id')
        ->where($where);

        if ($request->text) {
            $data = $data->where(function($query) use ($request) {
                $query->where(DB::raw("CONCAT(IFNULL(users.firstname,''),IFNULL(users.lastname,''),IFNULL(users.fathername,''))"), 'LIKE', "%{$request->text}%")
                ->orWhere('users.login', 'LIKE', "%{$request->text}%")
                ->orWhere('users.phone', 'LIKE', "%{$request->phone}%")
                ->orWhere('users.email', 'LIKE', "%{$request->text}%");
            });
        }

        $data = $data->orderBy('firstname')        
        ->paginate(30);

        return $data;

    }

    /**
     * Список пользователей доавбленных в друзья
     */
    public static function getFavoritUsersList($request) {

        return DB::table('users_favorit')
        ->select('users.*', 'users_group.admin', 'users_access.value as indAdmin')
        ->join('users', 'users.id', '=', 'users_favorit.favoritId')
        ->leftJoin('users_group', 'users_group.id', '=', 'users.groupId')
        ->leftJoin('users_access', function ($join) {
            $join->on('users_access.userId', '=', 'users.id')
            ->where('users_access.access', 'admin');
        })
        ->where([
            ['users.ban', 0],
            ['users_favorit.userId', $request->__user->id]
        ])
        ->orderBy('firstname')
        ->get();

    }

    /**
     * Проверка избранного коллеги
     */
    public static function getFavCollegueData($request) {

        return DB::table('users_favorit')->where([
            ['userId', $request->__user->id],
            ['favoritId', $request->id]
        ])->get();

    }

    /**
     * Добавление коллеги в избранное
     */
    public static function addFavCollegue($request) {

        return DB::table('users_favorit')->insert([
            'userId' => $request->__user->id,
            'favoritId' => $request->id
        ]);

    }

    /**
     * Удаление коллеги из избранное
     */
    public static function delFavCollegue($request) {

        return DB::table('users_favorit')->where([
            ['userId', $request->__user->id],
            ['favoritId', $request->id]
        ])->delete();

    }

    /**
     * Поиск коллеги
     */
    public static function searchCollegue($request) {

        return DB::table('users')
        ->select('users.*', 'users_group.admin', 'users_access.value as indAdmin', 'users_favorit.id as favorit')
        ->leftJoin('users_group', 'users_group.id', '=', 'users.groupId')
        ->leftJoin('users_access', function ($join) {
            $join->on('users_access.userId', '=', 'users.id')
            ->where('users_access.access', 'admin');
        })
        ->leftJoin('users_favorit', function ($join) use ($request) {
            $join->on('users_favorit.favoritId', '=', 'users.id')
            ->where('users_favorit.userId', $request->__user->id);
        })
        ->where([
            ['users.ban', 0],
            ['users.id', '!=', $request->__user->id]
        ])
        ->where(function ($query) use ($request) {
            $query->where(DB::raw("CONCAT(IFNULL(users.firstname,''),IFNULL(users.lastname,''),IFNULL(users.fathername,''))"), 'LIKE', "%{$request->search}%")
            ->orWhere('users.login', 'LIKE', "%{$request->search}%")
            ->orWhere('users.phone', 'LIKE', "%{$request->search}%")
            ->orWhere('users.email', 'LIKE', "%{$request->search}%");
        })
        ->orderBy('firstname')
        ->limit(15)
        ->get();

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
     * Список сотрудников в группе
     */
    public static function getUserFromGroup($ids) {

        return DB::table('users')->whereIn('groupId', $ids)->orderBy('firstname')->get();

    }

    /**
     * Список администраторов
     */
    public static function getAllAdmins() {

        return DB::table('users_group')
        ->select('users.*')
        ->join('users', 'users.groupId', '=', 'users_group.id')
        ->where('users_group.admin', 1)
        ->get();

    }

    /**
     * Список администраторов по индивидуальным правам
     */
    public static function getAllAdminsFromIndAccess() {

        return DB::table('users_access')
        ->select('users.*', 'users_access.value')
        ->join('users', 'users.id', '=', 'users_access.userId')
        ->where('users_access.access', 'admin')
        ->get();

    }

    /**
     * Список колонок таблицы группы пользователей с комментариями
     */
    public static function getAccessRowsGroup() {

        $data = DB::table('information_schema.COLUMNS')
        ->select('COLUMN_COMMENT', 'COLUMN_NAME')
        ->where([
            ['TABLE_NAME', 'users_group'],
            ['TABLE_SCHEMA', env('DB_DATABASE')],
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

    /**
     * Добавление время посещения раздела
     */
    public static function writeTimeVisitRazdel($data) {

        return DB::statement("INSERT INTO `users_views` SET `userId` = '{$data['userId']}', `razdel` = '{$data['razdel']}' ON DUPLICATE KEY UPDATE `count` = `count` + 1");

    }

    /**
     * Получение времени посещения разделов
     */
    public static function getTimeVisitRazdel($id) {

        return DB::table('users_views')->where('userId', $id)->get();

    }

    /**
     * Сброс пароля
     */
    public static function resetPass($req) {

        return DB::table('users')
        ->where('id', $req->id)->limit(1)
        ->update([
            'pass' => $req->pass,
        ]);

    }


}