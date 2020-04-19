<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;
use Cookie;
use Crypt;

use App\Models\UserModel;
use DB;

class User extends Main
{

    /**
     * Метод генерации пароля
     * 
     * @param String $pass
     * @return String Захешированный пароль
     */
    public static function passHash($pass) {

        return md5(md5($pass).md5($pass));

    }
    

    /**
     * Метод генерации случайного пароля
     */
    public static function getRandomPass() {

        // Символы, которые будут использоваться в пароле
        $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP!@";

        // Количество символов в пароле.
        $max = 7;

        // Переменная с паролем
        $pass = null;

        while ($max--)
            $pass .= $chars[rand(0,StrLen($chars)-1)];

        return $pass;
        
    }    
    
    /**
     * Метод авторизации
     */
    public static function login(Request $req) {

        if (!$req->login AND !$req->password)
            return parent::error("Укажите логин и пароль", 1001);

        if (!$req->login)
            return parent::error("Укажите логин", 1002);

        if (!$req->password)
            return parent::error("Укажите пароль", 1003);

        $req->password = self::passHash($req->password);

        if (!$user = UserModel::login($req))
            return parent::error("Неверный логин или пароль", 1004);

        if ($user->ban == 1)
            return parent::error("Ваш профиль заблокирован", 1005);

        // Получение данных пользователя
        $user = self::getUserData($user->id);

        Session::put('user', $user);

        // Запись токена в БД
        UserModel::createToken($user->token, $user->id);
        UserModel::setLastTime($user->id);

        return parent::json($user);

    }

    /**
     * Метод получения данных пользователя
     */
    public static function getUserData($id, $token = false) {

        // Основные данные пользователя
        if (!$user = UserModel::getUserData($id))
            return false;

        // Текущий токен
        $user->token = $token ? $token : Session::getId();

        // Объект прав
        $user->access = (Object) [];

        // Данные прав по группе
        $group = UserModel::getUserGroupAccess($user->groupId);
        foreach ($group as $key => $row)
            if (!in_array($key, ['id','name','descript']))
                $user->access->$key = (int) $row;

        // Индивидуальные права пользователя
        $access = UserModel::getUserAccess($user->id);
        foreach ($access as $row)
            $user->access->{$row->access} = (int) $row->value;

        // Доступ к заказчикам
        $user->clientsAccess = [];

        if ($user->access->admin == 1) {
            $projects = \App\Models\ProjectModel::getProjectsList();
            foreach ($projects as $row)
                $user->clientsAccess[] = $row->id;
        }
        else {
            $projects = \App\Models\ProjectModel::getProjectsAccessList($user->groupId);
            foreach ($projects as $row)
                if ($row->access == "1")
                    $user->clientsAccess[] = $row->projectId;
        }

        // Время посещения разделов
        $times = (Object) [];
        foreach (UserModel::getTimeVisitRazdel($user->id) as $time)
            $times->{$time->razdel} = $time->date;

        $user->times = $times;

        // Новые данные
        $user->newData = self::getAllCountsData($user);

        return $user;

    }

    /** Данные по новым изменениям */
    public static function getAllCountsData($user) {

        return (Object) [
            'comments' => \App\Models\ServiceModel::countNewComment($user),
            'services' => \App\Models\ServiceModel::countNewServices($user),
        ];

    }

    /**
     * Данные пользователя по токену
     */
    public static function getUserDataFromToken($token = false) {

        if (!$user = UserModel::getUserDataFromToken($token))
            return false;

        return self::getUserData($user->userId, $token);

    } 

    /**
     * Метод выхода пользователя
     */
    public static function logout(Request $req) {

        UserModel::deleteToken($req->token);
        Session::pull('user');

        return parent::json([
            'message' => "До новых встреч"
        ]);

    }

    /**
     * Метод проверки токена
     */
    public static function checkToken(Request $req, $arr = false) {

        if (!$check = UserModel::getUserDataFromToken($req->token))
            return $arr ? false : parent::error("Недействительный токен", 2001);

        if (!$user = self::getUserData($check->userId, $req->token))
            return $arr ? false : parent::error("Владелец токена не найден", 2002);

        if ($user->ban == 1)
            return $arr ? false : parent::error("Ваш профиль заблокирован", 2003);

        return $arr ? $user : parent::json($user);

    }


    /**
     * Метод преобразования данных старой таблицы пользователей в новую
     */
    public static function parseUsers() {

        $old = DB::table('ttm_users')->get();

        foreach ($old as $user) {

            $name = explode(" ", trim($user->name));

            $new_user = [
                'id' => $user->id,
                'idGroup' => $user->asdu == 1 ? 2 : 1,
                'login' => $user->login,
                'phone' => $user->phone,
                // 'pass' => Crypt::encrypt($user->pass),
                'pass' => self::passHash($user->pass),
                'connect' => $user->connect,
                'surname' => $name[0],
                'name' => $name[1],
                'patronymic' => $user->fatherName,
                'ban' => $user->ban
            ];

            // DB::table('new_users')->insert($new_user);
            dump($new_user);
            
        }

    }


}