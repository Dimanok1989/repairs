<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

use App\Http\Controllers\Admin\Projects;
use App\Http\Controllers\Service\Application;

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
    public static function getFullServicesData($rows, $appInfo = false) {

        $services = $temp = []; // Данные строк сервиса

        $files = []; // Список файлов
        $users = []; // Список сотрудников
        $repairs = []; // Список пунктов выполненных работ
        $subrepairs = []; // Список подпунктов выполненных работ
        $appids = []; // Идентификаторы заявок

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

            // Ссылка на заявку
            $row->applicationLink = route('application', ['link' => parent::dec2link($row->applicationId)]);

            // Идентификаторы заявок
            if (!in_array($row->applicationId, $appids))
                $appids[] = $row->applicationId;

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

        // Получение данных заявки
        $applicationsdata = [];
        if ($appInfo) {

            foreach (ApplicationModel::getApplicationData($appids) as $row) {

                unset($row->telegram);
                unset($row->bottoken);

                $applicationsdata[$row->id] = $row;

            }

        }


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

            // Добавление информации о заявке
            $row->applicationData = $applicationsdata[$row->applicationId] ?? [];

            // Иконка проекта
            $row->projectIcon = Application::getIconProject($row->applicationData->clientId ?? false);

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


    /**
     * Метод вывода строк сервиса для ленты работ
     */
    public static function getWorkTape(Request $request) {

        $tape = self::getWorkTapeData($request);

        return parent::json($tape);

    }

    /**
     * Получение данных для ленты работ
     */
    public static function getWorkTapeData(Request $request) {

        $data = (Object) [];

        $tape = ServiceModel::getWorkTapeData($request);
        $data->service = self::getFullServicesData($tape, true);
   
        $data->last = $tape->lastPage(); // Всего страниц
        $data->next = $tape->currentPage() + 1; // Следующая страница

        return $data;

    }


    /**
     * Метод вывода всех комментариев
     */
    public static function getComments(Request $request) {

        $tape = self::getCommentsData($request);

        return parent::json($tape);

    }

    /**
     * Получение данных для ленты комментариев
     */
    public static function getCommentsData(Request $request) {

        $data = (Object) [];

        $tape = ServiceModel::getCommentsTapeData($request);

        $data->comments = [];
        foreach ($tape as $row) {

            $row->projectIcon = Application::getIconProject($row->project);
            $row->link = "/id" . parent::dec2link($row->applicationId);
            $row->dateAdd = parent::createDate($row->date);
            $row->fio = parent::getUserFio($row->firstname, $row->lastname, $row->fathername, 2);

            $data->comments[] = $row;

        }

        $data->last = $tape->lastPage(); // Всего страниц
        $data->next = $tape->currentPage() + 1; // Следующая страница

        return $data;

    }

    /**
     * Создание акта word
     */
    public static function createWordFile(Request $request) {

        $phpWord = new \PhpOffice\PhpWord\PhpWord();

    }

}