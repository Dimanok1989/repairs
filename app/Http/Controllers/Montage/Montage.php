<?php

namespace App\Http\Controllers\Montage;

use Session;
use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\MontageModel;
use App\Models\MontageData;
use DB;

class Montage extends Main
{
    
    public static function getDataForStart(Request $request) {

        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Доступ к монтажу закрыт", 1000);

        // Поиск наименований папок филиалов
        $mains = MontageModel::getMainFolders();

        $subs = [];
        foreach ($mains as $row)
            $subs[] = $row->id;

        // Поиск подпапок филиалов
        $subfolders = MontageModel::getMainSubFolders($subs);

        // Список монтажа сотрудника
        $montages = MontageModel::getMontageListForUser($request);

        return parent::json([
            'mains' => $mains,
            'subfolders' => $subfolders,
            'montages' => $montages,
        ]);

    }

    /**
     * Создание монтажа
     */
    public static function start(Request $request) {

        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Доступ к монтажу закрыт", 1001);

        $inputs = [];

        if (!$request->filial OR $request->filial == "0")
            $inputs[] = 'filial';

        if (!$request->place OR $request->place == "0")
            $inputs[] = 'place';

        if ($request->place == "add" AND !$request->newplace)
            $inputs[] = 'newplace';

        if (!$request->bus OR $request->bus == "0")
            $inputs[] = 'bus';

        if ($inputs)
            return parent::error("Не заполнены обязательные поля", 1002, $inputs);

        // Создание в БД новой папки
        if ($request->place == "add") {

            $folder = MontageModel::getFolderDataFromName($request->newplace);

            if (!$folder)
                $request->place = MontageModel::createNewPlaceFolder($request);
            else
                $request->place = $folder->main;

        }

        // Запись нового монтажа
        $id = MontageModel::createNewMontage([
            'bus' => $request->bus,
            'user' => $request->__user->id,
            'folder' => $request->place,
        ]);

        return parent::json([
            'id' => parent::dec2link($id),
        ]);

    }

    /**
     * Данные страницы одного монтажа
     */
    public static function getDataOneMontage(Request $request) {

        // Основные данные монтажа
        if (!$data = MontageModel::getDataOneMontage($request->id))
            return false;

        $data->dateCompleted = $data->completed ? date("d.m.Y", strtotime($data->completed)) : "";

        // Заполненные данные
        $data->inputs = [];
        foreach (MontageModel::where('montageId', $data->id)->get() as $row) {

            // if ($row->name == "serialNum")
            //     $row->value = str_replace("WM19120177S", "", $row->value);

            $data->inputs[] = $row;

        }

        // Данные файлов фотографий
        $data->files = [];
        foreach (MontageModel::getFilesList($data->id) as $row) {
            
            $row->formatSize = parent::formatSize($row->size);

            $bus = (int) $data->bus;

            $file = "montages/{$data->folder}/{$bus}/{$row->name}";
            $row->link = Storage::disk('public')->url($file);
            
            if ($row->del == 0)
                $data->files[] = $row;
            
        }

        // Список сотрудников
        $data->users = [];
        foreach (MontageModel::getUsersAddList($data->id) as $row) {
            
            $row->fio = parent::getUserFioAll($row);
            $data->users[] = $row;

        }

        // Комментарии
        $data->comments = [];
        foreach (MontageModel::getCommentsList($data->id) as $row) {
            
            $row->fio = parent::getUserFioAll($row, 1);
            $row->dateAdd = parent::createDate($row->date);

            $row->link = false;

            if ($row->file)
                $row->link = "/storage/montages/" . $data->folder . "/" . $data->bus . "/" . $row->name;

            $data->comments[] = $row;

        }

        return $data;

    }

    /**
     * Вывод данных монтажа через апи запрос
     */
    public static function getOneMontage(Request $request) {

        if (!$request->id)
            return parent::error("Неправильный идентификатор", 2000);

        if (!$montage = self::getDataOneMontage($request))
            return parent::error("Данные монтажа не найдены", 2001);

        // Список пользователей в избранном
        $favs = MontageModel::getFavoritUsersList($request);
        $fav = self::updateUserRowData($favs, true);

        return parent::json([
            'montage' => $montage,
            'fav' => $fav,
            'bus' => self::getListBusName(),
        ]);

    }

    /**
     * Список моделей машин для монтажа
     */
    public static function getListBusName() {

        $bus = [
            'ЛиАЗ 4292',
            'ЛиАЗ 5292.20',
            'ЛиАЗ 5292.21',
            'ЛиАЗ 5292.21 учеб',
            'ЛиАЗ 5292.22',
            'ЛиАЗ 5292.22 учеб',
            'ЛиАЗ 5292.65',
            'ЛиАЗ 5292.65 учеб',
            'ЛиАЗ 6213.20',
            'ЛиАЗ 6213.21',
            'ЛиАЗ 6213.21 учеб',
            'ЛиАЗ 6213.22',
            'ЛиАЗ 6213.22 учеб',
            'ЛиАЗ 6213.65',
            'ЛиАЗ 6213.65 учеб',
            'ЛиАЗ 6274 электро',
            'ЛиАЗ 6274 электро учеб',
            '71-623',
            '71-623-02',
            'Татра Т3 МТТЧ',
            'Татра Т3 МТТД',
            'Татра Т3 МТТА',
            'Татра Т3 МТТМ'
        ];

        foreach (MontageModel::where('name', 'busName')->distinct()->get() as $row)
            if (!in_array($row->value, $bus) AND !in_array($row->value, ["Неизвестная модель","add","0"]))
                $bus[] = $row->value;

        sort($bus);

        return $bus;

    }

    /**
     * Обработка строк найденных коллег
     */
    public static function updateUserRowData($rows, $fav = false) {

        $temp = $data = [];

        foreach ($rows as $row) {

            unset($row->pass); // Удаление строки с паролем

            // Полное ФИО сотрудника
            $row->fio = parent::getUserFio($row->firstname, $row->lastname, $row->fathername, 0);

            // Пользователь в избранном
            $row->favorit = $row->favorit ?? false;
            if ($fav)
                $row->favorit = "none";

            // Добавление данных индивидуальных прав
            if (!isset($temp[$row->id])) {
                $row->isAdmin = false;
                $row->isMontage = false;
            }

            // Доступ коллеги к разделу монтаж
            $row->montageAccess = 0;

            // Запись индивидуального права доступа к разделу монтаж
            if ($row->userAccess == "montage")
                $row->isMontage = (int) $row->userAccessValue;

            // Запись индивидуального права роли администратора
            if ($row->userAccess == "admin" AND $row->userAccessValue == 1)
                $row->isAdmin = 1;

            // Запись/обновление эелемнта
            $temp[$row->id] = $row;
            
        }

        // Перепроверка на доступ к монтажу и доавбление строки для вывода
        foreach ($temp as $row) {

            // Основные права группы
            if ($row->montage == 1)
                $row->montageAccess = 1;

            // Првоерка индивидуального права доступа к монтажу
            if ($row->isMontage !== false)
                $row->montageAccess = $row->isMontage ? 1 : 0;

            // Перепроверка на администратора
            if (($row->isAdmin !== false AND $row->isAdmin == 1) OR $row->admin == 1)
                $row->montageAccess = 1;

            // Добавление коллеги на вывод с доступом к монтажу
            if ($row->montageAccess == 1)
                $data[] = $row;

        }

        return $data;

    }

    /**
     * Поиск коллег монтажа
     */
    public static function searchCollegue(Request $request) {

        $rows = MontageModel::searchCollegue($request);
        $users = self::updateUserRowData($rows);

        return parent::json([
            'users' => $users,
        ]);

    }

    /**
     * ЗАпись данных форм
     */
    public static function changeInput(Request $request) {

        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Нет доступа к монтажу", 3000);

        if (!$request->id)
            return parent::error("Идентификатор не определен", 3001);

        // Првоерка на завершение монтажа, чтобы не перезаписть данные
        if (!$data = MontageModel::getDataOneMontage($request->id))
            return parent::error("Данные монтажа не найдены", 3002);

        if ($data->completed)
            return parent::error("Монтаж уже завршен, обновить данные нельзя", 3003);

        // Заменя инпута при ручном вводе наименования маки и модели машины
        $request->name = $request->name == "busNameEdit" ? "busName" : $request->name;

        // Поиск данных этого поля
        $find = MontageModel::where([
            ['montageId', $request->id],
            ['name', $request->name],
        ])->get();

        // Объект модели
        $input = count($find) ? MontageModel::find($find[0]->id) : new MontageModel;

        // Данные для обновления
        $input->userId = $request->__user->id;
        $input->montageId = $request->id;
        $input->name = $request->name;
        $input->value = self::validValueInputs($request);

        // Запись в БД
        $input->save();

        if ($input->name == "serialNum")
            $input->value = $request->value;

        // Вывод данных
        return parent::json([
            'message' => "Строка обновлена",
            'input' => $input,
        ]);

    }

    public static function validValueInputs($request) {

        $value = $request->value;

        if ($request->name == "serialNum")
            $value = "WM19120177S" . $value;

        if (in_array($request->name, ['macAddr','vinNum','busNum','serialNum']))
            $value = mb_convert_case($value, MB_CASE_UPPER, "UTF-8");

        if (in_array($request->name, ['macAddr','vinNum','busNum','serialNum']))
            $value = parent::transliterateGosNum($value);

        if (in_array($request->name, ['macAddr','vinNum','busNum','serialNum']))
            $value = preg_replace('/\s+/', '', $value);

        return $value;

    }

    public static function uploadFile(Request $request) {

        if (!$request->isMethod('post'))
            return parent::error("Неправильный метод обращения", 4000);
            
        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Нет доступа к монтажу", 4001);
        
        if (!count($request->file()))
            return parent::error("Не выбран файл для загрузки", 4002);

        // Данные монтажа, чтобы определить каталог
        if (!$montage = MontageModel::getDataOneMontage($request->id))
            return parent::error("Данные монтажа не найдены", 4003);

        // Проверка текущего монтажа на завершение
        // if ($montage->completed)
        //     return parent::error("Монтаж уже кто-то завершил, данные менять теперь нельзя", 4010);

        // Путь до каталога с файлами монтажа
        $bus = (int) $montage->bus;
        $dir = "montages/" . $montage->folder . "/" . $bus;

        // Првоерка и создание каталога
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        // Данные файлов
        $path = [];

        foreach ($request->file('images') as $key => $file) {

            $path[$key] = [
                'montageId' => $request->id,
                'type' => $request->fileForm,
                'name' => null,
                'oldName' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
				'mimeType' => $file->getClientMimeType(),
                'user' => $request->__user->id,
                'ip' => $request->ip(),
                'ext' => $file->getClientOriginalExtension(),
                'formatSize' => parent::formatSize($file->getSize()),
				'uploaded' => false,					
				'error' => false,
                'link' => false,
                'id' => false,
                'bus' => $montage->bus,
                'fio' => parent::getUserFioAll($request->__user, 1),
                'dateAdd' => parent::createDate(date("Y-m-d H:i:s")),
			];

			$name = self::getNewName($path[$key]);

            $count = 1;
			while (Storage::disk('public')->exists("{$dir}/{$name}")) {
                $name = self::getNewName($path[$key], $count);
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
                    $path[$key]['size'] = Storage::disk('public')->size("{$dir}/{$name}");
                    $path[$key]['formatSize'] = parent::formatSize($path[$key]['size']);
                }

			}

            $path[$key]['name'] = $name;

        }

        // Запись загруженных файлов в БД
        foreach ($path as $key => $file) {

            if ($file['uploaded'] === true) {

                $path[$key]['id'] = MontageModel::storagedFilesData($file);

                if ($file['type'] == "comment") {
                    MontageModel::addNewComment([
                        'montageId' => $file['montageId'],
                        'userId' => $file['user'],
                        'file' => $path[$key]['id'],
                    ]);
                }

            }

        }
            
        return parent::json([
            'files' => $path,
        ]);

    }

    public static function getNewName($data, $count = false) {

        $name = $data['bus'];

        switch ($data['type']) {
            case 'act':
                $name .= "_Акт";
                break;

            case 'sim':
                $name .= "_Сим-карта";
                break;

            case 'bus':
                $name .= "_Передок_машины";
                break;

            case 'serialn':
                $name .= "_Серийный_номер_и_мак-адрес";
                break;

            case 'vin':
                $name .= "_Табличка_VIN";
                break;

            case 'cam':
                $name .= "_Камера_и_монитор";
                break;

            case 'electr':
                $name .= "_Электрощиток";
                break;

            case 'indic':
                $name .= "_Индикация_устройств";
                break;
            
            default:
                $name .= "_Прочее";
                break;
        }

        if ($count)
            $name .= "_" . $count;

        $name .= "." . $data['ext'];

        return $name;

    }

    public static function deleteFile(Request $request) {

        if (!$request->montage OR !$request->id)
            return parent::error("Неправильный запрос", 4007);
            
        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Нет доступа к монтажу", 4005);

        if (!$file = MontageModel::getOneFileData($request->id))
            return parent::error("Данные файла не найдены", 4008);

        // Данные монтажа, чтобы определить каталог
        if (!$montage = MontageModel::getDataOneMontage($request->montage))
            return parent::error("Данные монтажа не найдены", 4006);

        // Проверка текущего монтажа на завершение
        if ($montage->completed)
            return parent::error("Монтаж уже кто-то завершил, данные менять теперь нельзя", 4009);

        // Путь до каталога с файлами монтажа
        $bus = (int) $montage->bus;
        $dir = "montages/" . $montage->folder . "/" . $bus . "/";
        Storage::disk('public')->copy($dir . $file->name, $dir . "DELETED_" . $file->name);

        $del = MontageModel::deleteFile($request->id, "DELETED_" . $file->name);

        return parent::json([
            'del' => $del,
        ]);

    }

    public static function sendComment(Request $request) {

        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Нет доступа к монтажу", 5004);

        if (!$request->id)
            return parent::error("Неправильный запрос", 5005);

        if (!$request->text)
            return parent::error("Введите текст комментария", 5006);

        $data = [
            'montageId' => $request->id,
            'userId' => $request->__user->id,
            'comment' => $request->text,
        ];

        $add = MontageModel::addNewComment($data);

        $data['fio'] = parent::getUserFioAll($request->__user, 1);
        $data['dateAdd'] = parent::createDate(date("Y-m-d H:i:s"));

        return parent::json([
            'comment' => [$data],
            'add' => $add,
        ]);

    }

    /**
     * Завершение монтажа
     */
    public static function doneMontage(Request $request) {

        if (!parent::checkRight(['admin','montage'], $request->__user))
            return parent::error("Нет доступа к монтажу", 5000);

        if (!$request->id)
            return parent::error("Неправильный запрос", 5001);

        // Проверка текущего монтажа на завершение
        $data = self::getDataOneMontage($request);

        if ($data->completed) {
            return parent::json([
                'montage' => $data,
            ]);
        }

        // Проверка введенных данных
        $inputs = [];

        if (!$request->iccid)
            $inputs[] = "iccid";

        if (!$request->busName)
            $inputs[] = "busName";

        if ($request->busName == "add" AND !$request->busNameEdit)
            $inputs[] = "busNameEdit";
        
        // if (!$request->busNum)
        //     $inputs[] = "busNum";
        
        if (!$request->serialNum)
            $inputs[] = "serialNum";
        
        if (!$request->macAddr)
            $inputs[] = "macAddr";
        
        if (!$request->vinNum)
            $inputs[] = "vinNum";

        if ($inputs)
            return parent::error("Заполнены не все поля. При отсутствии каких-либо данных, просто укажите <b>нет</b> в соответсвующей графе", 5002, $inputs);

        if (!$request->photos)
            $request->photos = [];

        // Проверка фотографий
        if (!in_array("sim", $request->photos))
            $inputs[] = "sim";

        if (!in_array("bus", $request->photos))
            $inputs[] = "bus";

        if (!in_array("serialn", $request->photos))
            $inputs[] = "serialn";

        if (!in_array("vin", $request->photos))
            $inputs[] = "vin";

        if (!in_array("cam", $request->photos))
            $inputs[] = "cam";

        if (!in_array("electr", $request->photos))
            $inputs[] = "electr";

        if (!in_array("indic", $request->photos))
            $inputs[] = "indic";

        if ($inputs)
            return parent::error("Не загружено какое-то из обязательных фото", 5003, $inputs);

        $users[] = [
            'montageId' => $request->id,
            'userId' => $request->__user->id,
            'userAdd' => null,
        ];

        // Добавление коллег
        if ($request->useradd) {

            foreach ($request->useradd as $user) {
                $users[] = [
                    'montageId' => $request->id,
                    'userId' => $user,
                    'userAdd' => $request->__user->id,
                ];
            }

        }

        // Добавление сотрудников
        MontageModel::addUsersDone($users);

        // Обновление времени щавершения монтажа
        MontageModel::doneMontage($request->id);

        // Данные монтажа
        $montage = self::getDataOneMontage($request);

        // Генерация текстового файла с информацией
        self::createTxtInfoDone($montage);

        // Копирование автоматического акта
        $bus = (int) $montage->bus;
        $file = "montages/" . $montage->folder . "/" . $montage->bus . "/" . $bus . "_Акт_автоматический.jpg";
        $link = env('APP_URL') . "/montage/act" . $montage->id;
        $contents = file_get_contents($link);

        // Сохранение файла в папку
        Storage::disk('public')->put($file, $contents);

        return parent::json([
            'montage' => $montage,
        ]);

    }

    /**
     * Создание текстового файла в каталог
     */
    public static function createTxtInfoDone($montage) {

        $text = "Дата завршения монтажа: " . date("d.m.Y H:i:s") . "\n";
		$text .= "IP: {$_SERVER['REMOTE_ADDR']}\n";
        $text .= "User Agent: {$_SERVER['HTTP_USER_AGENT']}\n\n";

        // Сотрудники
        $text .= "Выполняли:\n";
        foreach ($montage->users as $key => $user) {
            $count = $key+1;
            $text .= "{$count}. {$user->fio}\n";
        }

        $text .= "\n";

        // Заполненные данные
		$text .= "Данные по машине:\n";
        foreach ($montage->inputs as $key => $input)
            $text .= "{$input->name}: {$input->value}\n";

        // Каталог с файлами монтажа
        $montage->bus = (int) $montage->bus;
        $file = "montages/" . $montage->folder . "/" . $montage->bus . "/" . date("Y-m-d H:i:s") . ".txt";

        // Сохранение файла в папку
        Storage::disk('public')->put($file, $text);

    }

    /**
     * Список всего монтажа
     */
    public static function allMontagesDataList(Request $request) {

        $data = (Object) [];

        $paginate = MontageModel::allMontagesList($request);
        $data->rows = self::getOneRowInAllMontage($paginate);

        $data->last = $paginate->lastPage(); // Всего страниц
        $data->next = $paginate->currentPage() + 1; // Следующая страница

        return $data;
        
    }

    public static function allMontagesList(Request $request) {

        $data = self::allMontagesDataList($request);

        return parent::json($data);

    }

    public static function getOneRowInAllMontage($rows) {

        $temp = $data = []; // Данные на вывод

        $ids = []; // Список идентификакторов найденных строк монтажа
        $foldersId = []; // Список идентификаторов папок

        // Первичная сборка данных
        foreach ($rows as $row) {

            $ids[] = $row->id; // Сбор идентификаторов монтажа

            // Сбор мдентификаторов каталогов
            if (!in_array($row->folderMain, $foldersId))
                $foldersId[] = $row->folderMain;

            $row->dateAdd = parent::createDate($row->date); // Дата создания
            $row->dateCompeted = parent::createDate($row->completed); // Дата завершения

            $row->fio = parent::getUserFioAll($row, 1); // ФИО завершившего онтаж
            
            $temp[] = $row; // Первичные данные

        }

        // Список всех сотрудников в монтаже
        $users = [];
        foreach (MontageModel::getUsersAddList($ids) as $row) {
            $row->fio = parent::getUserFioAll($row, 1);
            $users[$row->montageId][] = $row;
        }

        // Данные каталогов
        $folders = [];
        foreach (MontageModel::getFoldersListFromIds($foldersId) as $row)
            $folders[$row->id] = $row->name;

        // Заполненные данные
        $inputs = [];
        foreach (MontageModel::whereIn('montageId', $ids)->get() as $row)
            $inputs[$row->montageId][] = $row;

        $comments = [];
        foreach (MontageModel::getCountComments($ids) as $row)
            $comments[$row->montageId] = $row->count;

        // Окончательая обработка
        foreach ($temp as $row) {

            // Список всех сотрудников в монтаже
            $row->users = $users[$row->id] ?? [];
            // Количество сотрудников в монтаже
            $row->countUsers = isset($users[$row->id]) ? count($users[$row->id]) - 1 : 0;

            // Количество сотрудников в монтаже
            $row->comments = $comments[$row->id] ?? 0;

            // Наименование филиала
            $row->filial = $folders[$row->folderMain] ?? "";

            // Добавление данных
            $row->inputs = [];

            if (isset($inputs[$row->id]))
                foreach ($inputs[$row->id] as $input)
                    $row->inputs[$input->name] = $input->value;

            $row->catItems = self::getCatItems($row);

            $data[] = $row;

        }

        return $data;

    }

    /**
     * Метод формирует массив для заполнения ячеек экселя
     */
    public static function getCatItems($row) {

        $time = strtotime($row->completed);

        $fios = [];
        foreach ($row->users as $user)
            $fios[] = $user->fio;

        return [
            date("d.m.Y", $time),
            date("H:i", $time),
            $row->place,
            $row->bus,
            $row->inputs['busNum'] ?? "",
            $row->inputs['vinNum'] ?? "",
            $row->inputs['serialNum'] ?? "",
            $row->inputs['macAddr'] ?? "",
            $row->inputs['iccid'] ?? "",
            count($row->users),
            implode("; ", $fios),
            env("APP_URL") . "/montage" . $row->id,
        ];

    }

    /**
     * Вывод данных завершенного монтажа для эксель отчетчета
     */
    public static function getAllCompletedMontagesFromPeriod(Request $request) {

        $data = MontageModel::getAllCompletedMontagesFromPeriod($request);

        return self::getOneRowInAllMontage($data);

    }

    public static function ParceData(Request $request) {

        $data = [];

        $users = [
            'Самойленко' => 2,
            'Андреянов' => 13,
            'Маслов' => 16,
            'Найданов' => 17,
            'Шрамков' => 20,
            'Колгаев Дмитрий Петрович' => 1,
            'Xtk' => 2,
            'Шохонов' => 18,
            'Иванов' => 22,
            'Морковин' => 19,
            'Lvbnhbq' => 2,
            'Колгаев' => 1,
            'Найданов В.В.' => 17,
            'Гейдер А.Р.' => 21,
        ];

        $indic = [
            'fileact' => 'act',
            'filesim' => 'sim',
            'filebus' => 'bus',
            'filesn' => 'serialn',
            'filevin' => 'vin',
            'filecam' => 'cam',
            'fileelectr' => 'electr',
            'fileindic' => 'indic',
            'comment' => 'comment',
        ];

        // $montages = DB::table('montage_temp')
        // ->select('montage_temp.*', 'montage_folders.id as folder')
        // ->leftjoin('montage_folders', 'montage_folders.name', '=', 'montage_temp.place')
        // ->get();

        // foreach ($montages as $row) {
        //     $data[] = [
        //         'id' => $row->id,
        //         'bus' => $row->bus,
        //         'folder' => $row->folder,
        //         'date' => $row->create_at,
        //         'completed' => $row->completed,
        //     ];
        // }

        // DB::table('montage')->insert($data);

        //--------------------------------------------------------

        // foreach (DB::table('montage_temp_data')->where('type', NULL)->get() as $row) {

        //     $value = $row->value;

        //     if (in_array($row->name, ['macAddr','vinNum','busNum','serialNum']))
        //         $value = mb_convert_case($value, MB_CASE_UPPER, "UTF-8");

        //     if (in_array($row->name, ['macAddr','vinNum','busNum','serialNum']))
        //         $value = parent::transliterateGosNum($value);

        //     if (in_array($row->name, ['macAddr','vinNum','busNum','serialNum']))
        //         $value = preg_replace('/\s+/', '', $value);

        //     $data[] = [
        //         'montageId' => $row->idMontage,
        //         'name' => $row->name,
        //         'value' => $value,
        //     ];

        // }

        // DB::table('montage_data')->insert($data);

        //--------------------------------------------------------

        // foreach (DB::table('montage_temp_data')->where([
        //     ['type', 'file'],
        //     ['del', NULL]
        // ])->get() as $row) {

        //     $data[] = [
        //         'type' => $indic[$row->name] ?? null,
        //         'name' => $row->value,
        //         'oldName' => $row->value,
        //         'mimeType' => 'image/jpeg',
        //         'montageId' => $row->idMontage,
        //     ];

        // }

        // DB::table('montage_files')->insert($data);

        //--------------------------------------------------------

        // $added = [];

        // foreach (DB::table('montage_temp_users')->get() as $row) {

        //     $user = $users[$row->fio] ?? null;

        //     if (!isset($added[$row->idMontage]))
        //         $added[$row->idMontage] = $user;

        //     $data[] = [
        //         'montageId' => $row->idMontage,
        //         'userId' => $user,
        //         'userAdd' => $added[$row->idMontage] != $user ? $added[$row->idMontage] : null,
        //     ];

        // }

        // DB::table('montage_users')->insert($data);

        //--------------------------------------------------------

        // foreach (DB::table('montage_comment')->get() as $row) {

        //     if ($row->type == 1) {

        //         $file = [
        //             'type' => 'comment',
        //             'name' => $row->comment,
        //             'oldName' => $row->comment,
        //             'mimeType' => 'image/jpeg',
        //             'montageId' => $row->idMontage,
        //             'user' => $users[$row->name] ?? null,
        //             'date' => $row->create_at,
        //         ];

        //         $fileid = DB::table('montage_files')->insertGetId($file);
        //         $comment = NULL;

        //     }
        //     else {

        //         $file = [];
        //         $fileid = NULL;
        //         $comment = $row->comment;

        //     }

        //     $commentRow = [
        //         'montageId' => $row->idMontage,
        //         'userId' => $users[$row->name] ?? null,
        //         'comment' => $comment,
        //         'file' => $fileid,
        //         'date' => $row->create_at,
        //     ];
        //     DB::table('montage_comments')->insertGetId($commentRow);

        //     $data[] = [
        //         'file' => $file,
        //         'comment' => $commentRow,
        //     ];

        // }

        // DB::table('montage_users')->insert($data);

        echo json_encode($data);
        return;

        // dd($montages);

    }

}