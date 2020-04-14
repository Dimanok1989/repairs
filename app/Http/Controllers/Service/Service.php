<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

use App\Http\Controllers\Admin\Projects;

use App\Models\ApplicationModel;
use App\Models\ServiceModel;
use App\Models\ProjectModel;


class Service extends Main
{

    /**
     * Метод получения данных сервиса
     * 
     * @param Array $id Массив запрашиваемых идентификаторов
     * 
     * @return Array
     */
    public static function getServicesData($id) {

        $services = ServiceModel::getServicesData($id);

        return self::getFullServicesData($services);

    }

    /**
     * Метод сбора недостающих данных по сервису
     */
    public static function getFullServicesData($rows) {

        $services = $temp = []; // Данные строк сервиса

        $files = []; // Список файлов
        $users = []; // Список сотрудников
        $repairs = []; // Список пунктов выполненных работ
        $subrepairs = []; // Список подпунктов выполненных работ

        foreach ($rows as $row) {

            $row->dateAdd = parent::createDate($row->date); // Дата создания
            $row->dateAddTime = date("d.m.Y", strtotime($row->date)); // Дата создания
            
            // Сбор файлов
            $row->files = self::serachPhotoId($row->files);
            foreach ($row->files as $file)
                $files[] = $file;

            $row->imagesData = []; // Список ссылок фоток

            // Сбор пользователей
            if (!in_array($row->userId, $users))
                $users[] = $row->userId;

            $rowsubUserId = explode(",", $row->subUserId);
            $row->subUserId = [];
            foreach ($rowsubUserId as $user) {
                if (!in_array($user, $users) AND $user != "")
                    $users[] = $user;

                if ($user != "")
                    $row->subUserId[] = $user;
            }

            // Сбор пунктов выполненных работ
            $rowrepairs = explode(",", $row->repairs);
            $row->repairs = [];
            foreach ($rowrepairs as $repair) {
                if (!in_array($repair, $repairs) AND $repair != "")
                    $repairs[] = $repair;

                if ($repair != "")
                    $row->repairs[] = $repair;
            }

            // Сбор подпунктов выполненных работ
            $rowsubrepairs = explode(",", $row->subrepairs);
            $row->subrepairs = [];
            foreach ($rowsubrepairs as $subrepair) {
                if (!in_array($subrepair, $subrepairs) AND $subrepair != "")
                    $subrepairs[] = $subrepair;

                if ($subrepair != "")
                    $row->subrepairs[] = $subrepair;
            }

            $temp[] = $row;

        }


        // Получение списка изображений
        $imagesdata = [];
        foreach (ApplicationModel::getImagesData($files) as $image)
            $imagesdata[$image->id] = $image;

        // Список пользователей
        $usersdata = [];
        $userrows = \App\Models\UserModel::getUsersList(false, $users);

        if (count($users) == 1)
            $userrows = [$userrows];

        foreach ($userrows as $user) {
            $user->fio = parent::getUserFio($user->firstname, $user->lastname , $user->fathername, 1);
            $usersdata[$user->id] = $user;
        }

        // Список пунктов ремонта
        $repairsdata = [];
        foreach (ProjectModel::getProjectRepairsList($repairs) as $row)
            $repairsdata[$row->id] = $row;

        // Список подпунктов ремонта
        $subrepairsdata = [];
        foreach (ProjectModel::getProjectSubRepairsList($subrepairs) as $row)
            $subrepairsdata[$row->id] = $row;


        foreach ($temp as $row) {

            $row->update = $userrows;

            // Добавление фотографий
            foreach ($row->files as $file)
                if (isset($imagesdata[$file]))
                    $row->imagesData[] = parent::getNormalDataImageRow($imagesdata[$file]);

            // Добавление списка пользователей
            $users = [];

            if (isset($usersdata[$row->userId]))
                $users[] = $usersdata[$row->userId]->fio;

            foreach ($row->subUserId as $user)
                if (isset($usersdata[$user]))
                    $users[] = $usersdata[$user]->fio;

            $row->usersList = implode("; ", $users);

            // Добавление пунктов ремонта
            $repairs = [];

            foreach ($row->repairs as $point)
                if (isset($repairsdata[$point]))
                    $repairs[] = $repairsdata[$point]->name;

            foreach ($row->subrepairs as $point)
                if (isset($subrepairsdata[$point]))
                    $repairs[] = $subrepairsdata[$point]->name;

            $row->repairsList = implode("; ", $repairs);

            $services[] = $row;

        }

        return $services;

    }

    /**
     * Метод сбора идентификаторов фотографий
     * 
     * @param String $files Строка JSON
     * 
     * @return Array
     */
    public static function serachPhotoId($files) {

        $files = json_decode($files, true);

        if (!is_array($files))
            return [];

        $ids = [];
        
        foreach ($files as $key => $row) {
            
            if ($key == "change") {

                foreach ($row['new'] as $arr) {
                    foreach ($arr as $new) {
                        $ids[] = $new;
                    }
                }

                foreach ($row['old'] as $arr) {
                    foreach ($arr as $old) {
                        $ids[] = $old;
                    }
                }

            }
            else {

                foreach ($row as $id) {
                    $ids[] = $id;
                }

            }

        }

        return $ids;

    }

}