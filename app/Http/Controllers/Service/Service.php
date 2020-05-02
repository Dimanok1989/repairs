<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

use App\Http\Controllers\Admin\Projects;
use App\Http\Controllers\Service\Application;
use App\Http\Controllers\Service\ServiceFiles;

use App\Models\ApplicationModel;
use App\Models\ServiceModel;
use App\Models\ProjectModel;
use App\Models\UserModel;
use App\ApplicationsServiceActs;


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

            $time = strtotime($row->date);

            $row->dateAdd = parent::createDate($row->date); // Дата создания
            $row->dateAddTime = date("d.m.Y", $time); // Дата создания

            $row->dates = [
                'd' => date("d", $time),
                'm' => date("m", $time),
                'Y' => date("Y", $time),
                'F' => parent::dateToMonth($time, 1),
            ];
            
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

            $row->act = parent::checkRight(['admin','application_act_edit']);
            $row->actDwn = parent::checkRight(['admin','application_act_download']);

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
            $row->users = [];

            if (isset($usersdata[$row->userId]))
                $row->users[] = $usersdata[$row->userId]->fio;

            foreach ($row->subUserId as $user)
                if (isset($usersdata[$user]))
                    $row->users[] = $usersdata[$user]->fio;

            $row->usersList = implode("; ", $row->users);

            // Добавление пунктов ремонта
            $repairs = [];

            foreach ($row->repairs as $point)
                if (isset($repairsdata[$point]))
                    $repairs[] = $repairsdata[$point]->name;

            foreach ($row->repairs as $key => $point)
                $row->repairs[$key] = (int) $point;

            foreach ($row->subrepairs as $point)
                if (isset($subrepairsdata[$point]))
                    $repairs[] = $subrepairsdata[$point]->name;

            foreach ($row->subrepairs as $key => $point)
                $row->subrepairs[$key] = (int) $point;

            $row->repairsList = implode("; ", $repairs);

            // Добавление информации о заявке
            $row->applicationData = $applicationsdata[$row->applicationId] ?? [];

            // Иконка проекта
            $row->projectIcon = Application::getIconProject($row->applicationData->clientId ?? false);

            // ФИО заявершившего заявку
            if (isset($usersdata[$row->userId])) {
                $row->userIdFio = parent::getUserFioAll($usersdata[$row->userId]);
                $row->userIdFioAbbr = parent::getUserFioAll($usersdata[$row->userId], 1);
            }
            else {
                $row->userIdFio = $row->userId;
                $row->userIdFioAbbr = $row->userId;
            }

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
     * Данные для модального окна редактирования акта
     */
    public static function actEditData(Request $request) {

        // Проверка прав доступа
        if (!parent::checkRight(['admin','application_act_edit'], $request->__user))
            return parent::error("Доступ ограничен", 2000);

        $request->create = true; // Для остановки формаирования файла акта

        // Данные, подготовленные для формирвоания файла акта
        $data = self::actDownload($request);

        $admins = []; // Массив администраторов

        // Поиск администраторов по группам
        foreach (UserModel::getAllAdmins() as $row) {
            
            if (!isset($admins[$row->id])) {
                $admins[$row->id] = [
                    'id' => $row->id,
                    'fio' => parent::getUserFioAll($row),
                    'admin' => 1,
                ];
            }

        }

        // Поиск администраторов по индивидуальным правам
        foreach (UserModel::getAllAdminsFromIndAccess() as $row) {
            
            // Добавление администратора
            if ($row->value == 1 AND !isset($admins[$row->id])) {
                $admins[$row->id] = [
                    'id' => $row->id,
                    'fio' => parent::getUserFioAll($row),
                    'admin' => 1,
                ];
            }
            // Удаление из списка, если отключены админские права
            elseif ($row->value == 0 AND isset($admins[$row->id]))
                unset($admins[$row->id]);

        }

        // Сортировка администраторов по алфавиту
        usort($admins, function($a, $b) {
            return strcmp($a['fio'], $b['fio']);
        });

        // Список групп сотрудников, кому открыт доступ к заказчику
        $groups = ProjectModel::getProjectsAccessList(false, 1, $data['service']->application->clientId);

        $groupsUser = []; // Идентификаторы групп
        foreach ($groups as $group)
            if ($group->typeAccess == 1)
                $groupsUser[] = $group->typeId;

        $users = []; // Пользователи которым открыт доступ к заказчику

        // Поиск пользователей
        foreach (UserModel::getUserFromGroup($groupsUser) as $row) {
            
            if (!isset($admins[$row->id]) AND !isset($users[$row->id])) {
                $users[$row->id] = [
                    'id' => $row->id,
                    'fio' => parent::getUserFioAll($row),
                ];
            }

        }

        // Сортировка пользователей по алфавиту
        usort($users, function($a, $b) {
            return strcmp($a['fio'], $b['fio']);
        });

        // Формирвоание массива со списком всех пользователей
        $data['users'] = [];

        foreach ($admins as $row)
            $data['users'][] = $row;

        foreach ($users as $row)
            $data['users'][] = $row;        

        return parent::json($data);

    }

    /**
     * Сохранение данных акта
     */
    public static function actSaveData(Request $request) {

        if (!parent::checkRight(['admin','application_act_download'], $request->__user))
            return parent::error("Доступ ограничен", 2010);

        if (!$request->id)
            return parent::error("Неправильный идентификатор", 2011);

        $data = ApplicationsServiceActs::find($request->id);

        if (!$data)
            $data = new ApplicationsServiceActs;

        $data->id = $request->id;
        $data->asdu = preg_replace('/\s+/', ' ', $request->asdu);
        $data->engineer = $request->engineer;
        $data->remark = $request->remark;

        $data->save();

        return parent::json($data);

    }

    /**
     * Создание акта word
     */
    public static function actDownload(Request $request) {

        if (!parent::checkRight(['admin','application_act_download'], $request->__user))
            return parent::error("Доступ ограничен", 2010);

        if (!$request->id)
            return parent::error("Неправильный идентификатор", 2011);

        // Данные сервиса
        $service = self::getServicesData([$request->id]);

        if (!count($service))
            return parent::error("Данные сервиса не обнаружены", 2012);

        $service = $service[0];

        // Данные заявки
        $applications = ApplicationModel::getApplicationData($service->applicationId);
        $service->application = Application::getApplicationsListEditRow([$applications])[0];

        unset($service->application->telegram);
        unset($service->application->bottoken);

        $time = strtotime($service->application->date);
        $service->application->dates = [
            'd' => date("d", $time),
            'm' => date("m", $time),
            'Y' => date("Y", $time),
            'F' => parent::dateToMonth($time, 1),
        ];

        // Остальные данные сервиса для заполнения акта
        $service->serials = ServiceModel::getSerialsDataService($service->id);

        // Сбор пунктов и подпунктов ремонта
        $service->repairsData = [];
        foreach (ServiceModel::getRepairList($service->repairs) as $row)
            $service->repairsData[] = $row;
        foreach (ServiceModel::getRepairList($service->subrepairs, true) as $row)
            $service->repairsData[] = $row;

        // Таблица с оборудованием
        $tables = [];
        
        $count = 1;
        foreach ($service->repairsData as $key => $row) {

            if ($row->changed == 1) {

                $add = [
                    'n' => $count,
                    't' => "",
                    's' => $row->device ?? "",
                ];
                
                $tables['t1'][$key] = $add;
                $tables['t2'][$key] = $add;

                foreach ($service->serials as $serial) {
                   
                    if (($serial->repairId AND $serial->repairId == $row->id) OR ($serial->subRepairId AND $serial->subRepairId == $row->id)) {

                        $tables['t1'][$key]['s'] = $serial->serialOld;
                        $tables['t2'][$key]['s'] = $serial->serialNew;

                    }

                }

                $count++;

            }

        }

        // Описание выполненных работ
        $remark = $service->repairsList;
        if ($service->comment)
            $remark .= "; " . $service->comment;

        // Данные заказчика
        $client = ProjectModel::getProjectsList($service->application->clientId);

        $num = $client->templateNum ? self::createNumberAct($client->templateNum, $service) : $service->application->ida;

        // Заполненные данные акта
        if ($service->actData = ApplicationsServiceActs::find($service->id)) {

            if ($service->actData->engineer) {
                $user = UserModel::getUsersList(0, $service->actData->engineer);
                $service->userIdFio = parent::getUserFioAll($user);
                $service->userIdFioAbbr = parent::getUserFioAll($user, 1);
            }

            $asdu = explode(" ", $service->actData->asdu);
            if (count($asdu) > 1) {
                $service->asdu = parent::getUserFio($asdu[0] ?? false, $asdu[1] ?? false, $asdu[2] ?? false);
                $service->asduAbbr = parent::getUserFio($asdu[0] ?? false, $asdu[1] ?? false, $asdu[2] ?? false, 1);
            }

            $remark = $service->actData->remark;

        }
        else
            $service->actData = [];

        // Данные для шаблона
        $value = [
            'num' => $num,
            'application' => $service->application->ida,
            'day' => $service->application->dates['d'],
            'month' => $service->application->dates['F'],
            'year' => $service->application->dates['Y'],
            'engineer' => $service->userIdFio,
            'engineerAbbr' => $service->userIdFioAbbr,
            'asdu' => $service->asdu ?? "",
            'asduAbbr' => $service->asduAbbr ?? "",
            'busNum' => $service->application->bus,
            'servDay' => $service->dates['d'],
            'servMonth' => $service->dates['F'],
            'servYear' => $service->dates['Y'],
            't1n' => "1",
            't1t' => "",
            't1s' => "",
            't2n' => "1",
            't2t' => "",
            't2s' => "",
            'remark' => $remark,
            'place' => $client->place ?? "",
        ];

        $data = [
            'service' => $service,
            'value' => $value,
            'tables' => $tables,
        ];

        // Вывод данных до формирования акта
        if ($request->create)
            return $data;

        // Создание акта из шаблона
        $data['link'] = ServiceFiles::createActService($value, $tables);

        return parent::json($data);

    }

    /**
     * Вывод номера из шаблона
     */
    public static function createNumberAct($template, $service) {

        $template = str_replace('${num}', $service->application->ida, $template);
        $template = str_replace('${y}', $service->application->dates['Y'], $template);

        return $template;

    }

}