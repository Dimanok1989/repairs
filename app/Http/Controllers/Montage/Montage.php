<?php

namespace App\Http\Controllers\Montage;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Storage;

use App\Models\MontageModel;
use App\Models\MontageData;

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

            $file = "montages/{$data->folder}/{$data->bus}/{$row->name}";
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

        // Комментарии
        $montage->comments = [];
        foreach (MontageModel::getCommentsList($montage->id) as $row) {
            
            $row->fio = parent::getUserFioAll($row, 1);
            $row->dateAdd = parent::createDate($row->date);

            $row->link = false;

            if ($row->file)
                $row->link = "/storage/montages/" . $montage->folder . "/" . $montage->bus . "/" . $row->name;

            $montage->comments[] = $row;

        }
        

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
        $dir = "montages/" . $montage->folder . "/" . $montage->bus;

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
        $dir = "montages/" . $montage->folder . "/" . $montage->bus . "/";
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
        
        if (!$request->busNum)
            $inputs[] = "busNum";
        
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
        $file = "montages/" . $montage->folder . "/" . $montage->bus . "/" . $montage->bus . "_Акт_автоматический.jpg";
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
        $file = "montages/" . $montage->folder . "/" . $montage->bus . "/" . date("Y-m-d H:i:s") . ".txt";

        // Сохранение файла в папку
        Storage::disk('public')->put($file, $text);

    }

    /**
     * Наложение данных монтажа на картинку с шаблоном акта
     */
    public static function createJpegAct(Request $request) {

        // Каталог с шаблонами
        $dir = public_path("templates/montage");

        // Шаблон акта
        $img = imagecreatefromjpeg("{$dir}/antison-1.jpg");

        // Данные монтажа
        if (!$montage = self::getDataOneMontage($request))
            return self::doneCreateJpegAct($img);

        // Данные каталога
        if (!$place = MontageModel::getFolderData($montage->folder))
            return self::doneCreateJpegAct($img);

        // Данные филиала
        if (!$filial = MontageModel::getFolderData($place->main))
            return self::doneCreateJpegAct($img);

        // Данные для акта
        $data = [];
        foreach ($montage->inputs as $input)
            $data[$input->name] = $input->value;

        // Цвет текста
        $color = imagecolorallocate($img, 63, 72, 204);

        // Путь к шрифту
        $font = "{$dir}/CALIBRI.TTF";

        // $img - Изображение
        // 25 - размер шрифта
        // 0 - угол поворота
        // 812 - смещение по горизонтали
        // 226 - смещение по вертикали

        imagettftext($img, 25, 0, 812, 226, $color, $font, $montage->dateCompleted ?? date("d.m.Y"));
        imagettftext($img, 30, 0, 576, 783, $color, $font, "Андреянова Василия Владимировича");
        imagettftext($img, 30, 0, 437, 1408, $color, $font, $filial->name);
        imagettftext($img, 30, 0, 476, 1468, $color, $font, $place->name);

        // МАК-адрес
        $pos = 480;
        if (isset($data['macAddr'])) {
            for ($i = 0; $i <= mb_strlen($data['macAddr']); $i++) {
                imagettftext($img, 38, 0, $pos, 1553, $color, $font, mb_substr($data['macAddr'], $i, 1));
                $pos += 79;
            }
        }

        // Серийный номер
        $pos = 1283;
        if (isset($data['serialNum'])) {

            $data['serialNum'] = str_replace("WM19120177S", "", $data['serialNum']);

            for ($i = 0; $i <= mb_strlen($data['serialNum']); $i++) {
                imagettftext($img, 38, 0, $pos, 1673, $color, $font, mb_substr($data['serialNum'], $i, 1));
                $pos += 75;
            }

        }

        imagettftext($img, 30, 0, 400, 1792, $color, $font, $data['iccid'] ?? "");
        imagettftext($img, 30, 0, 1105, 1923, $color, $font, $montage->bus ?? "");
        imagettftext($img, 30, 0, 707, 1982, $color, $font, $data['busNum'] ?? "");

        return self::doneCreateJpegAct($img);

    }

    public static function doneCreateJpegAct($img) {

        header('Content-type: image/jpeg');
        imagejpeg($img);
        imagedestroy($img);

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

        $temp = $data = [];

        $ids = [];
        $foldersId = [];

        foreach ($rows as $row) {

            $ids[] = $row->id;

            if (!in_array($row->folderMain, $foldersId))
                $foldersId[] = $row->folderMain;

            $row->dateAdd = parent::createDate($row->date);
            $row->fio = parent::getUserFioAll($row, 1);
            
            $temp[] = $row;

        }

        $users = [];
        foreach (MontageModel::countUsersFromMontage($ids) as $row)
            $users[$row->montageId] = $row->count;

        $folders = [];
        foreach (MontageModel::getFoldersListFromIds($foldersId) as $row)
            $folders[$row->id] = $row->name;

        foreach ($temp as $row) {

            $row->countUsers = isset($users[$row->id]) ? $users[$row->id] - 1 : 0;
            $row->filial = $folders[$row->folderMain] ?? false;

            $data[] = $row;

        }

        return $data;

    }

}