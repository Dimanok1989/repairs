<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class MontageModel extends Model
{

    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'montage_data';

    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * Метод получения списка филиалов
     * 
     * @return Object
     */
    public static function getMainFolders() {

        return DB::table('montage_folders')->where([
            ['main', NULL],
            ['del', 0]
        ])->orderBy('name')->get();

    }

    /**
     * Метод получения наименований площадок
     * 
     * @return Object
     */
    public static function getMainSubFolders($ids) {

        return DB::table('montage_folders')->where('del', 0)->whereIn('main', $ids)->orderBy('name')->get();

    }

    /**
     * Метод получения данных папки по её имени
     * 
     * @param String $name Наименование папки
     * @return Object|Bool
     */
    public static function getFolderDataFromName($name) {

        $data = DB::table('montage_folders')->where('name', $name)->get();
        return count($data) ? $data[0] : false;

    }

    /**
     * Метод получения данных каталога
     * 
     * @param Int $id Идентификатор
     * @return Object|Bool
     */
    public static function getFolderData($id) {

        $data = DB::table('montage_folders')->where('id', $id)->limit(1)->get();
        return count($data) ? $data[0] : false;

    }

    /**
     * Создание новой папки для площадки
     * 
     * @param Request
     * @return Int
     */
    public static function createNewPlaceFolder($request) {

        return DB::table('montage_folders')->insertGetId([
            'name' => $request->newplace,
            'main' => $request->filial,
        ]);

    }

    /**
     * Создание нового монтажа
     * 
     * @param Array $data
     * @return Int
     */
    public static function createNewMontage($data) {

        return DB::table('montage')->insertGetId($data);

    }

    /**
     * Список монтажа, касаемых пользователя
     * 
     * @param Request
     * @return Object
     */
    public static function getMontageListForUser($request) {

        return DB::table('montage')->where('user', $request->__user->id);

    }

    /**
     * Данные одного монтажа
     * 
     * @param Int $id
     * @return Object
     */
    public static function getDataOneMontage($id) {

        $data = DB::table('montage')->where('id', $id)->get();
        return count($data) ? $data[0] : false;

    }

    /**
     * Список избранных сотрудников для монтажа
     */
    public static function getFavoritUsersList($request) {

        return DB::table('users_favorit')
        ->select(
            'users.*',
            'users_group.admin',
            'users_group.montage',
            'users_access.value as userAccessValue',
            'users_access.access as userAccess'
        )
        ->join('users', 'users.id', '=', 'users_favorit.favoritId')
        ->leftJoin('users_group', 'users_group.id', '=', 'users.groupId')
        ->leftJoin('users_access', function ($join) {
            $join->on('users_access.userId', '=', 'users.id')
            ->where('users_access.access', 'admin')
            ->orWhere('users_access.access', 'montage');
        })
        ->where([
            ['users.ban', 0],
            ['users_favorit.userId', $request->__user->id]
        ])
        ->orderBy('firstname')
        ->get();

    }

    /**
     * Поиск коллег монтажа
     */
    public static function searchCollegue($request) {

        return DB::table('users')
        ->select(
            'users.*',
            'users_group.admin',
            'users_group.montage',
            'users_access.value as userAccessValue',
            'users_access.access as userAccess',
            'users_favorit.id as favorit'
        )
        ->leftJoin('users_group', 'users_group.id', '=', 'users.groupId')
        ->leftJoin('users_access', function ($join) {
            $join->on('users_access.userId', '=', 'users.id')
            ->where('users_access.access', 'admin')
            ->orwhere('users_access.access', 'montage');
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
     * Запись загруженных файлов в БД
     */
    public static function storagedFilesData($file) {

        return DB::table('montage_files')->insertGetId([
            'montageId' => $file['montageId'],
            'type' => $file['type'],
            'name' => $file['name'],
            'oldName' => $file['oldName'],
            'size' => $file['size'],
            'mimeType' => $file['mimeType'],
            'user' => $file['user'],
            'ip' => $file['ip']
        ]);

    }

    /**
     * Список файлов монтажа
     */
    public static function getFilesList($id) {

        return DB::table('montage_files')->where('montageId', $id)->get();

    }

    /**
     * Данные одного файла
     */
    public static function getOneFileData($id) {

        $data = DB::table('montage_files')->where('id', $id)->get();
        return count($data) ? $data[0] : false;

    }

    /**
     * Удаление файла фотографии
     */
    public static function deleteFile($id, $name) {

        return DB::table('montage_files')->where('id', $id)->limit(1)
        ->update([
            'del' => 1,
            'name' => $name
        ]);

    }

    /**
     * Завершение монтажа
     */
    public static function doneMontage($id) {

        return DB::table('montage')->where('id', $id)->limit(1)
        ->update([
            'completed' => date("Y-m-d H:i:s")
        ]);

    }

    /**
     * Добавление сотрудников-помощников
     */
    public static function addUsersDone($users) {

        return DB::table('montage_users')->insert($users);

    }

    /**
     * Список сотрудников-помощников
     */
    public static function getUsersAddList($id) {

        return DB::table('montage_users')
        ->select('montage_users.*', 'users.firstname', 'users.lastname', 'users.fathername')
        ->join('users', 'montage_users.userId', '=', 'users.id')
        ->where('montage_users.montageId', $id)
        ->get();

    }

    /**
     * Добавление комментария
     */
    public static function addNewComment($data) {

        return DB::table('montage_comments')->insert($data);

    }

    /**
     * Комментарии
     */
    public static function getCommentsList($id) {

        return DB::table('montage_comments')
        ->select(
            'montage_files.oldName',
            'montage_files.name',
            'users.firstname',
            'users.lastname',
            'users.fathername',
            'montage_comments.*'
        )
        ->leftjoin('montage_files', 'montage_files.id', '=', 'montage_comments.file')
        ->leftjoin('users', 'users.id', '=', 'montage_comments.userId')
        ->where([
            ['montage_comments.montageId', $id],
        ])
        ->get();

    }

    /**
     * Список всего монтажа
     */
    public static function allMontagesList($request) {

        return DB::table('montage')
        ->select(
            'montage.*',
            'users.firstname',
            'users.lastname',
            'users.fathername',
            'montage_folders.name as place',
            'montage_folders.main as folderMain'
        )
        ->leftjoin('users', 'users.id', '=', 'montage.user')
        ->leftjoin('montage_folders', 'montage_folders.id', '=', 'montage.folder')
        ->orderBy('date', 'DESC')->paginate(50);

    }

    /**
     * Количество сотрудников
     */
    public static function countUsersFromMontage($ids) {

        return DB::table('montage_users')
        ->select(DB::raw('count(*) as count, montageId'))
        ->whereIn('montageId', $ids)
        ->groupBy('montageId')
        ->get();

    }

    public static function getFoldersListFromIds($ids) {

        return DB::table('montage_folders')->whereIn('id', $ids)->get();

    }

}
