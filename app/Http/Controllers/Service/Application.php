<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

use App\Http\Controllers\Admin\Projects;
use App\Http\Controllers\Service\Service;

use App\Models\ApplicationModel;
use App\Models\ProjectModel;


class Application extends Main
{

    /**
     * Метод вывода данных с подсчетом открытых заявок по каждому заказчику
     */
    public static function getAllApplicationsData() {

        // Проверка прав доступа
        if (!parent::checkRight(['admin','applications']))
            return [];

        // Список выводимых проектов
        $clients = Session::get('user')->clientsAccess;

        // Получение списка заказчиков, доступных пользователю
        $applications = ProjectModel::getProjectsListForUser($clients);

        // Счетчик открытых заявок по проектам
        $counts = ApplicationModel::getCountActiveApplication($clients);
        $countsclient = [];
        foreach ($counts as $row) {

            if (!isset($countsclient[$row->clientId]))
                $countsclient[$row->clientId] = [];

            $countsclient[$row->clientId][$row->project] = $row->count;

        }

        // Аккумулирование данных
        foreach ($applications as $key => $application) {

            $applications[$key]->projects = Projects::getProjectsName();

            $applications[$key]->applications = isset($countsclient[$application->id]) ? $countsclient[$application->id] : [];

            // Сумма всех заявок
            $applications[$key]->counts = 0;
            foreach ($applications[$key]->applications as $count)
                $applications[$key]->counts += $count;

        }

        // dump($applications);

        return $applications;

    }

    /**
     * Метод вывода заявок по запросу
     */
    public static function getApplicationsList(Request $request) {

        // Проверка прав доступа к заявкам
        if (!parent::checkRight(['admin','applications'], $request->token))
            return parent::error("Доступ ограничен", 1001);

        // Проверка прав доступа к заявкам заказчика
        if ($request->client AND !in_array($request->client, $request->__user->clientsAccess))
            $request->client = [0];
        else if ($request->client AND in_array($request->client, $request->__user->clientsAccess)) {
            $request->client = $request->client;
            $request->actual = true;
        }
        else
            $request->client = $request->__user->clientsAccess;

        return parent::json([
            'applications' => self::showApplicationsList($request),
        ]);

    }

    /**
     * Метод сбора данных для вывода списка заявок
     */
    public static function showApplicationsList(Request $request) {

        // Фильтрация заявок
        $applications = ApplicationModel::getApplicationsList($request);

        // Подготовка и дополнение данных
        $applications = self::getApplicationsListEditRow($applications);

        return $applications;

    }

    /** Сбор комментариев */
    static $commentsData = [];

    /**
     * Метод преобразования данных каждой строки заявки
     */
    public static function getApplicationsListEditRow($applications) {

        $data = $temp = []; // Данные на вывод
        $breaks = []; // Идентификаторы пунктов неисправностей
        $imagelinks = []; // Идентификаторы файлов
        $ids = []; // Идентификаторы всех затронух зявок

        // Первоначальный проход всех заявок, обработка основных данных
        // и сбор идентификаторов для доступа дополнительных данных в БД
        foreach ($applications as $row) {

            // Кодирование идентификатора для ссылки
            $row->linkId = parent::dec2link($row->id);

            // Сбор идентификтаоров
            $ids[] = $row->id;

            // Сбор идентификаторов неисправностей
            $row->breaks = explode(",", $row->breaks);
            foreach ($row->breaks as $break)
                if (!in_array($break, $breaks))
                    $breaks[] = $break;

            // Определение иконки проекта
            switch ($row->project) {
                case '1':
                    $row->projectIcon = "fa-video";
                    break;

                case '2':
                    $row->projectIcon = "fa-compass";
                    break;

                case '3':
                    $row->projectIcon = "fa-tv";
                    break;
                
                default:
                    $row->projectIcon = "fa-ellipsis-h";
                    break;

            }

            $row->dateAdd = parent::createDate($row->date); // Дата создания
            $row->dateAddTime = date("d.m.Y", strtotime($row->date)); // Дата создания
            
            $images = []; // Идентификаторы файлов
            // Сбор данных по файлам
            foreach (explode(",", $row->images) as $image) {
                if ($image != "") {
                    $images[] = parent::link2dec($image);
                    $imagelinks[] = parent::link2dec($image);
                }
            }

            $row->images = $images; // Замена элемента идентификаторов изобравжений

            $row->combineCount = 0; // Количество присоединённых заявок
            $row->combineData = []; // Данные объекдинённых заявок
            $row->combineLinks = []; // Список идентификаторов заявок
            $row->combineLink = $row->combine ? env('APP_URL') . "/id" . parent::dec2link($row->combine) : null;

            // Временные неполные данные заявок
            $temp[] = $row;

        }


        // Подсчет данных объединенных заявок
        $combines = [];
        foreach (ApplicationModel::getDataCombinedApplication($ids) as $row) {

            // Сбор идентификаторов неисправностей
            $row->breaks = explode(",", $row->breaks);
            foreach ($row->breaks as $break)
                if (!in_array($break, $breaks))
                    $breaks[] = $break;

            $images = [];

            foreach (explode(",", $row->images) as $image) {
                if ($image != "") {
                    $images[] = parent::link2dec($image);
                    $imagelinks[] = parent::link2dec($image);
                }
            }

            $row->images = $images; // Замена элемента идентификаторов изобравжений

            $combines[$row->combine][] = $row; // Массив с объединёнными заявками

        }

        // Комментарии к заявкам
        $comments = [];
        foreach(ApplicationModel::getApplicationComments($ids) as $comment)
            $comments[$comment->applicationId][] = self::oneRowComment($comment);

        self::$commentsData = $comments;

        // Получение списка неисправностей
        $braksList = [];
        foreach(ProjectModel::getProjectBreakList(false, $breaks) as $break)
            $braksList[$break->id] = $break->name;

        // Получение списка изображений
        $imagesdata = [];
        foreach (ApplicationModel::getImagesData($imagelinks) as $image)
            $imagesdata[$image->id] = $image;


        // Дополнение данных в заявоки
        foreach ($temp as $key => $row) {

            // Добавление данных на изобравжения
            $row->imagesData = [];
            foreach ($row->images as $image)
                if (isset($imagesdata[$image]))
                    $row->imagesData[] = parent::getNormalDataImageRow($imagesdata[$image]);

            // Добавление данных о присоединённых заявок
            if (isset($combines[$row->id])) {

                $row->combineCount = count($combines[$row->id]);
                $row->combineData = $combines[$row->id];

                // Добавление информации из присоединённых заявок
                foreach ($combines[$row->id] as $combine) {

                    $row->combineLinks[$combine->id] = env('APP_URL') . "/id" . parent::dec2link($combine->id);
                    
                    // Добавление изобравжений
                    foreach ($combine->images as $image) {
                        if (isset($imagesdata[$image]))
                            $row->imagesData[] = parent::getNormalDataImageRow($imagesdata[$image]);
                    }

                }

            }

            // Добавление данных о комментариях
            $row->comments = isset($comments[$row->id]) ? count($comments[$row->id]) : 0;
            $row->commentsData = [];         

            // Добавление списка неисправностей
            $row->breaksList = [];
            foreach ($row->breaks as $break)
                $row->breaksList[$break] = $braksList[$break] ?? false;

            $row->breaksListText = implode("; ", $row->breaksList);

            $data[] = $row;

        }
        
        return $data;

    }


    /**
     * Преобразование строки комментария
     */
    public static function oneRowComment($row) {

        $row->fio = parent::getUserFio($row->firstname, $row->lastname, $row->fathername, 'sb');
        $row->date = parent::createDate($row->date);

        return $row;

    }


    /**
     * Полные данные одной заявки
     */
    public static function getOneApplicationData(Request $request) {

        // Получение идентификатора заявки
        $id = parent::link2dec($request->link);

        // Проверка наличия заявки
        if (!$application = ApplicationModel::getApplicationData($id))
            return parent::error("Заявка не найдена", 2001);

        // Данные пользователя
        $user = \App\Http\Controllers\Auth\User::getUserDataFromToken($request->token);

        // Проверка доступа к удаленным заявкам
        if ($application->del AND !parent::checkRight(['admin','application_del'], $user))
            return parent::error("Заявка удалена", 2002);

        // Полные данные заявки
        $application = self::getApplicationsListEditRow([$application])[0];

        // Данные на вывод
        $service = $comments = $addcomments = false;

        if (parent::checkRight(['admin','applications'], $user))
            $comments = self::$commentsData[$application->id] ?? [];

        if (parent::checkRight(['admin','application_comment'], $user))
            $addcomments = true;
        
        // Кнопки на вывод
        $buttons = false;
        if ($user) {

            $buttons = [];

            // Кнопка перехода на мастер заявку
            if ($application->combine) {
                $buttons['combined'] = true;
            }
            else if (!$application->del) {

                if (parent::checkRight(['admin','applications_done'], $user)) {

                    // Кнопка завершения заявки
                    if (!$application->done)
                        $buttons['done'] = true;

                    // Кнопка подменного фонда
                    if ($application->changed AND !$application->changedId)
                        $buttons['changed'] = true;

                }

                if (!$application->done) {

                    // Кнопка объединения заявки
                    if (parent::checkRight(['admin','application_combine'], $user))
                        $buttons['combine'] = true;

                    // Кнопка удаления заявки
                    if (parent::checkRight(['admin','application_del'], $user))
                        $buttons['del'] = true;

                    // Кнопка пометки проблемной заявки
                    if (parent::checkRight(['admin','application_problem'], $user))
                        $buttons['problem'] = true;

                }

            }

        }

        $images = []; // Все изобравжения в заявке

        // Сбор изобравжений в заявке
        if (isset($application->imagesData))
            foreach ($application->imagesData as $image)
                $images[] = $image;


        // Получение списка сервисов по заявке
        $services = [];

        // Идентификатор сервиса завершения
        if ($application->done)
            $services[] = $application->done;

        // Идентификатор сервиса подменного фонда
        if ($application->changedId)
            $services[] = $application->changedId;

        if ($services)
            $service = Service::getServicesData($services);


        return parent::json([
            'images' => count($images) ? $images : false,
            'application' => $application,
            'service' => $service,
            'comments' => $comments,
            'addcomments' => $addcomments,
            'buttons' => $buttons,
        ]);

    }


    /**
     * Метод создания новой заявки
     */
    public static function addNewApplication(Request $request) {

        // Проверка данных
        $inputs = [];

        if (!$request->break)
            $inputs[] = "break[]";

        if (!$request->number)
            $inputs[] = "number";

        if ($inputs)
            return parent::error("Заполнены не все обязательные поля", 1001, $inputs);

        // Проверка скрытых параметров
        if (!$request->project OR !$request->client)
            return parent::error("Возникла внутренняя ошибка. Попробуйте обновить страницу, повторить запрос и, если ошибка снова возникнет, обратитесь к администрации сайта", 1002);

        // Проверка данных заказчика
        if (!$data = \App\Models\ProjectModel::getProjectsList($request->client))
            return parent::error("Возникла внутренняя ошибка. Попробуйте обновить страницу, повторить запрос и, если ошибка снова возникнет, обратитесь к администрации сайта", 1003);

        // Данные авторизированного пользователя
        $user = Session::get('user');

        // Сбор изобравжений
        if ($request->images) {

            $imgaes = [];
            foreach ($request->images as $image)
                $images[] = parent::dec2link($image);

            $request->images = implode(",", $images);

        }

        // Формирование данных для добавления заявки
        $data = [
            'clientId' => $request->client,
            'project' => $request->project,
            'bus' => $request->number,
            'breaks' => implode(",", $request->break),
            'priority' => $request->priority ? 1 : 0,
            'userId' => $user->id ?? null,
            'comment' => $request->comment,
            'images' => $request->images,
        ];

        // Добавление заявки в БД
        $id = ApplicationModel::createNewApplication($data);

        // Дополнение данных для вывода
        $data['id'] = $id;

        return parent::json([
            'link' => route('application', ['link' => parent::dec2link($id)]),
            'data' => $data,
        ]);

    }


    /**
     * Метод загрузки файлов в момент создания новой заявки
     */
    public static function uploadImagesAddApplication(Request $request) {

        if (!$request->isMethod('post'))
        	return parent::error("Неправильный метод обращения", 1005);
        
        if (!count($request->file()))
        	return parent::error("Не выбран файл для загрузки", 1006);

        // Каталог хранения файлов
        $dir = "files/" . date("Y/m/d");
        
        // Проверка и создание каталога
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        $path = [];

        // Поиск авторизированного пользователя
        $user = \App\Http\Controllers\Auth\User::checkToken($request, true);

		// Обработка файлов
        foreach ($request->file('images') as $key => $file) {
				
			$path[$key] = [
                'name' => $file->getClientOriginalName(),
                'razdel' => $request->razdel ? $request->razdel : "appnew",
                'description' => $request->description ? $request->description : false,
                'size' => $file->getSize(),
				'ext' => $file->getClientOriginalExtension(),
				'formatSize' => parent::formatSize($file->getSize()),
                'path' => $dir,
				'mimeType' => $file->getClientMimeType(),
                'userId' => $user->id ?? null,
                'ip' => $request->ip(),
				'uploaded' => false,					
				'error' => false,
                'link' => false,
                'id' => false,
			];

			$name = md5($path[$key]['name']) . "." . $path[$key]['ext'];

            $count = 1;
			while (Storage::disk('public')->exists("{$dir}/{$name}")) {
                $name = md5($path[$key]['name'] . "_" . $count) . "." . $path[$key]['ext'];
                $count++;
			}

			if (!self::checkMimeType($path[$key]['mimeType']))
				$path[$key]['error'] = "Тип файла не поддерживается";
			elseif ($path[$key]['size'] > 26214400)
				$path[$key]['error'] = "Размер файла превышает 25 Мб";
			else {
                // Сохранение файла
                $file->storeAs($dir, $name, 'public');
                $path[$key]['uploaded'] = true;
                $path[$key]['link'] = Storage::disk('public')->url("{$dir}/{$name}");

                // Полный путь до файла
                if (in_array($path[$key]['mimeType'], ['image/jpeg','image/pjpeg','image/png'])) {
                    $img = storage_path("app/public/{$dir}/{$name}");
                    $path[$key]['resize'] = parent::resizeUploadedImg($img);
                }

			}

			$path[$key]['filename'] = $name;

        }

        // Запись загруженных файлов в БД
        foreach ($path as $key => $file) {
            if ($file['uploaded'] === true) {
                $path[$key]['id'] = ApplicationModel::storagedFilesData($file);
            }
        }
        
        return parent::json([
            'files' => $path,
        ]);

        // echo asset('storage/file.txt');

    }


    /**
     * Отправка комментария по заявке
     */
    public static function sendApplicationComment(Request $request) {

        if ($request->problem AND !parent::checkRight(['admin','application_problem'], $request->__user))
            return parent::error("Нет прав для отправки проблемных комментариев", 3000);
        elseif (!parent::checkRight(['admin','application_comment'], $request->__user))
            return parent::error("Нет прав для отправки комментариев", 3001);

        if (!$request->id OR !$request->comment)
            return parent::error("Нет входящих данных", 3002);

        $data = [
            'applicationId' => $request->id,
            'userId' => $request->__user->id,
            'comment' => $request->comment,
        ];

        $application = false;

        // Проблемный комментарий
        if ($request->problem) {

            $data['problem'] = 1;

            // Обновление проблемного идентификатора в БД заявки
            ApplicationModel::setApplicationProblem($request->id);

            if ($application = ApplicationModel::getApplicationData($request->id))
                $application = self::getApplicationsListEditRow([$application])[0];

        }
      
        // Запись нового комментария в БД
        if (!$id = ApplicationModel::writeNewComment($data))
            return parent::error("Комментарий не записан", 3003);

        $comment = ApplicationModel::getApplicationComments($id);

        return parent::json([
            'comment' => self::oneRowComment($comment[0]),
            'application' => $application,
        ]);

    }

    /**
     * Удаление заявки
     */
    public static function applicationDelete(Request $request) {

        if (!parent::checkRight(['admin','application_del'], $request->__user))
            return parent::error("Нет прав для удаления заявки", 3004);

        if (!$request->id)
            return parent::error("Нет входящих данных", 3005);

        ApplicationModel::deleteApplication($request);

        if ($application = ApplicationModel::getApplicationData($request->id))
            $application = self::getApplicationsListEditRow([$application])[0];

        return parent::json([
            'application' => $application,
        ]);

    }

    
    /**
     * Список вариантов присоединения заявки
     */
    public static function applicationCombineOpen(Request $request) {

        // Проверка прав присоединения заявок
        if (!parent::checkRight(['admin','application_combine'], $request->__user))
            return parent::error("Нет прав для объединения заявок", 3006);

        if (!$request->bus)
            return parent::error("Нет входящих данных", 3007);

        $request->combinelist = true;
        $applications = ApplicationModel::getApplicationsList($request);
        $applications = self::getApplicationsListEditRow($applications);

        return parent::json([
            'applications' => $applications,
        ]);

    }

    /**
     * Сохранение выбранной к присоединению мастер-заявки
     */
    public static function applicationCombine(Request $request) {

        // Проверка прав присоединения заявок
        if (!parent::checkRight(['admin','application_combine'], $request->__user))
            return parent::error("Нет прав для объединения заявок", 3008);

        if (!$request->combine OR !$request->id)
            return parent::error("Нет входящих данных", 3009);

        ApplicationModel::combineApplication($request);

        return parent::json([
            'link' => route('application', ['link' => parent::dec2link($request->combine)]),
        ]);

    }

    /**
     * Начало процесса завешения заявки
     */
    public static function doneApplicationStart(Request $request) {

        // Проверка прав присоединения заявок
        if (!parent::checkRight(['admin','applications_done'], $request->__user))
            return parent::error("Нет прав для завершения заявок", 3010);

        $applications = ApplicationModel::getApplicationData($request->id);
        $application = self::getApplicationsListEditRow([$applications])[0];

        
        $repairsdata = []; // Получение списка пунктов ремонта
        $subpoints = []; // Идентификаторы основных пунктов

        $data = ProjectModel::getProjectRepairList($application->clientId, false, $application->project);
        foreach ($data as $row) {
            $subpoints[] = $row->id;
            $repairsdata[] = $row;
        }

        // Получение списка подпунктов ремонта
        $subrepair = [];
        $data = ProjectModel::getProjectSubRepairList($application->clientId, false, $subpoints);
        foreach ($data as $row)
            $subrepair[$row->repairId][] = $row;

        $repairs = []; // Список вариантов ремонта
        foreach ($repairsdata as $row) {
            $row->subpoints = $subrepair[$row->id] ?? [];
            $repairs[] = $row;
        }

        // Идентификатор заказчика
        $request->projectId = $application->clientId;

        // Избранные коллеги для сотрудника
        $favorites = \App\Http\Controllers\Admin\Users::getFavoritUsersList($request);

        return parent::json([
            'application' => $application,
            'repairs' => $repairs,
            'favorites' => $favorites,
        ]);

    }


    /**
     * Метод загрузки файлов при завершении заявки
     */
    public static function uploadFileForDone(Request $request) {

        return self::uploadImagesAddApplication($request);

    }


    /**
     * Удаение файла 
     */
    public static function deleteFile(Request $request) {

        return parent::json([
            'id' => $request->id,
            'data' => $request->input(),
        ]);

    }

    /**
     * Завршение заявки
     * 
     * @return JSON
     */
    public static function applicationDone(Request $request) {

        // Проверка прав
        if (!parent::checkRight(['admin','applications_done'], $request->__user))
            return parent::error("Нет прав для завершения заявок", 4000);

        $id = (int) $request->id;

        // Проверка наличия идентификаторв
        if (!$id)
            return parent::error("Неверный идентификатор", 4001);

        // Проверка наличия загруженных фотографий
        if (!$request->photo_bus)
            return parent::error("Не загружено фото передка машины", 4002);
        if (!$request->photo_device)
            return parent::error("Не загружено фото исправного устройства", 4003);
        if (!$request->photo_screen)
            return parent::error("Не загружено фото дисплея работающего устройства", 4004);

        // Првоерка добавленных фото
        if (is_array($request->required))
            foreach ($request->required as $required)
                if (!$request->$required)
                    return parent::error("Не загружено одно из фото замены оборудования", 4004);

        // Проверка выбранных пунктов ремонта
        if (!$request->repairs AND !$request->subrepairs)
            return parent::error("Не выбрано ниодного пункта выполненных работ", 4005);

        // Сбор данных по фотографиям
        $photo = self::collectPhotoData($request);

        // Данные для записи завершения заявки
        $data = [
            'applicationId' => $id,
            'userId' => $request->__user->id,
            'subUserId' => $request->useradd ? implode(",", $request->useradd) : null,
            'repairs' => $request->repairs ? implode(",", $request->repairs) : null,
            'subrepairs' => $request->subrepairs ? implode(",", $request->subrepairs) : null,
            'files' => json_encode($photo),
            'comment' => $request->comment,
            'changefond' => $request->thisfonddone ? 1 : 0,
        ];

        if (!$service = ApplicationModel::createService($data))
            return parent::error("Невозможно записать данные", 4006);

        // Данные для обновления строки заявки
        if ($request->thisfonddone) {
            $update = [
                'changedId' => $service,
            ];
        }
        else {
            $update = [
                'done' => $service,
                'changed' => 1,
            ];
        }

        if (!ApplicationModel::updateApplicationRowForDone($id, $update))
            return parent::error("Неполучилось обновсить данные заявки", 4007);

        return parent::json([
            'service' => $data,
            'update' => $update,
            'photo' => $photo,
        ]);

    }


    /**
     * Сбор данных загруженных фотографий
     */
    public static function collectPhotoData($request) {

        $photo = [];

        // Фото передка машины
        if ($request->photo_bus)
            $photo['photo_bus'] = $request->photo_bus;

        // Фото устройства
        if ($request->photo_device)
            $photo['photo_device'] = $request->photo_device;

        // Фото экрана
        if ($request->photo_screen)
            $photo['photo_screen'] = $request->photo_screen;

        // Проверка дополнительных фотографий
        if (is_array($request->required)) {

            $photo['change'] = [
                'new' => [],
                'old' => [],
            ];

            foreach ($request->required as $required) {

                $arr = explode("_", $required);

                if ($arr[0] == "new")
                    $photo['change']['new'][$arr[3]] = $request->$required;
                else
                    $photo['change']['old'][$arr[3]] = $request->$required;

            }

        }

        return $photo;

    }


    /**
     * Поиск колллег
     */
    public static function searchCollegue(Request $request) {

        $request->search = str_replace(" ", "", $request->search);

        $users = \App\Http\Controllers\Admin\Users::searchCollegue($request);

        return parent::json([
            'users' => $users,
        ]);

    }


}