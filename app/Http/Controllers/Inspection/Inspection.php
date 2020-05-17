<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Admin\Projects;

use App\Models\Inspections;
use App\Models\InspectionDevices;
use App\Models\Devices;

class Inspection extends Main
{
    
    public static function startData(Request $request) {

        $clients = Projects::getClientsListForUser($request->__user->clientsAccess);

        return parent::json([
            'clients' => $clients,
        ]);

    }

    public static function start(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 1000);

        if (!$request->number)
            return parent::error("Не указан гаражный номер", 1001, ['number']);

        $inspection = new Inspections;

        $inspection->client = $request->client;
        $inspection->userId = $request->__user->id;
        $inspection->busGarage = $request->number;

        $inspection->save();

        $link = route('inspectionRow', [
            'inspection' => $inspection->id,
        ]);

        return parent::json([
            'link' => $link,
            'id' => $inspection->id,
        ]);  

    }

    /**
     * Метод сбора данных для страницы приёмки
     */
    public static function open(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 1002);

        $data = self::openData($request);
        $data['clients'] = Projects::getClientsListForUser($request->__user->clientsAccess);

        $data['check'] = parent::checkRight(['admin'], $request->__user);

        return parent::json($data);  

    }

    public static function openData(Request $request) {

        $inspection = Inspections::getAllRowData($request->id);

        // Данные с настрйоками кнопок по проектам
        $data = self::getButtonsProjectForInspection($request);

        $buttons = $data['buttons']; // Список кнопок устройств по каждому проекту
        $projects = $data['projects']; // Список проектов

        // Сортировка всех кнопок по текстовомым идентификаторам
        $allButtons = [];
        foreach ($buttons as $rows)
            foreach ($rows as $button)
                $allButtons[$button->type] = $button;

        // Поиск введенных данных устройств
        $devicesRows = [];
        foreach (InspectionDevices::getDevicesFullData($request) as $row)
            $devicesRows[] = self::getColorButtonDevice($row, $allButtons[$row->typeId] ?? false);

        // Массив с данными уствройств
        $devices = [];
        foreach ($devicesRows as $row)
            $devices[$row->typeId][] = $row;

        // Определение цвета кнопок на странице приёмки
        $colors = $colorsData = [];
        foreach ($devices as $key => $rows) {

            $color = "secondary"; // Цвет кнопки по умолчанию

            // Обработка одного устройства
            if (count($rows) == 1) {
                // $devices[$key] = $rows[0]; // Заменна массива устройств на объект одного устройства
                // $color = $devices[$key]->color; // Цвет кнопки устройства
                $color = $rows[0]->color; // Цвет кнопки устройства
            }
            // Обработка нескольких одинаковых устройств
            elseif (count($rows) > 1) {

                $green = true; // Зеленый увет
                // Првоерка цвета кнопки каждого устройства из группы
                foreach ($rows as $row)
                    if ($row->color != "success")
                        $green = false; // Цыет не зеленый

                $color = $green ? "success" : "danger"; // Определение цвета

            }

            foreach ($rows as $rowKey => $row)
                $devices[$key][$rowKey]->buttonSett = $allButtons[$row->typeId] ?? false;

            $colorsData[$key] = $color;

        }

        foreach ($colorsData as $key => $color) {
            $colors[] = [
                'type' => $key,
                'color' => $color,
            ];
        }

        return [
            'colors' => $colors,
            'inspection' => $inspection,
            'devices' =>  $devices,
            'projects' => $projects,
            'buttons' => $buttons,
        ];

    }

    /**
     * Возвращает массив с наименование кнопок
     */
    public static function getButtonsProjectForInspection(Request $request) {

        $projects = Projects::getProjectsName();

        $buttons = [];
        foreach (InspectionDevices::getButtonsDevices() as $button) {

            if (!isset($buttons[$button->project]))
                $buttons[$button->project] = [];
            
            $button = self::getButtonsProjectForInspectionUpdateData($button);

            $buttons[$button->project][] = $button;

        }

        return [
            'projects' => $projects,
            'buttons' => $buttons,
        ];

    }

    public static function getButtonsProjectForInspectionUpdateData($button) {

        $button->deviceList = json_decode($button->deviceList);
        $button->defaultList = json_decode($button->defaultList);

        return $button;

    }

    /**
     * Метод обработки данных устройства и определение цвета кнопки
     * 
     * @param Object $device Объект данных устройства
     * @param Object $button Объект с настройками кнопки
     * 
     * @return Object $device Обработанные данные устройства
     */
    public static function getColorButtonDevice($device, $button = false) {

        $device->color = "secondary"; // Цвет кнопки по умолчанию

        // Дешифровка пломб
        $stamps = json_decode($device->stamps);
        $device->stamps = $stamps ? $stamps : [];

        // Определение цвета кнопки происходит, если переданы настройки самой кнопки
        if (!$button)
            return $device;

        // Если происходит проверка, значит по устройству есть какие-то данные
        // изначально цвет кнопки становится зеленым, затем просходит проверка каждой
        // настройки кнопки и имеющихся данных устройства
        $green = true;

        // Наличие наименования устройства
        if ($button->device == 1 AND $device->name === null)
            $green = false;

        // Наличие серийного номера
        if ($button->serial == 1 AND $device->serial === null)
            $green = false;

        if ($button->stamp == 1 AND !count($device->stamps))
            $green = false;

        $device->color = $green ? "success" : "danger";

        return $device;

    }

    /**
     * Открытие формы ввода данных по устройству
     */
    public static function deviceForm(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 1003);

        if (!$request->id OR !$request->insp)
            return parent::error("Неправильные входящие данные", 1004);

        // Данные для окна ввода
        $buttonData = InspectionDevices::getButtonsDevices($request->id);
        $button = count($buttonData) ? self::getButtonsProjectForInspectionUpdateData($buttonData[0]) : false;

        if (!$button)
            return parent::error("Настройки ввода данных устройства не найдены", 1005);

        // Список устройств группы
        $devices = $button->devices ? Devices::where('groupId', $button->devices)->orderBy('name')->get() : [];

        $multiple = (int) $request->multiple;

        // Поиск наименования устройства из списка однотипного устройства
        if ($multiple > 0) {
            foreach ($button->deviceList as $row) {               
                if ($row->id == $request->multiple) {
                    $button->multipleTitle = $row->name;
                    break;
                }
            }
        }

        // Введенные данные устройства
        $device = InspectionDevices::where([
            'inspection' => $request->insp,
            'typeId' => $button->type,
        ])->get();

        foreach ($device as $key => $row)
            $device[$key] = self::getColorButtonDevice($row, $button);

        $allDevice = [];
        $deviceKey = false;

        if ($multiple > 0) {
            foreach ($device as $key => $row) {
                
                $allDevice[] = $row;

                if ($multiple == $row->multiple)
                    $deviceKey = $key;

            }
        }

        if (!$button->multiple AND count($device))
            $device = $device[0];
        elseif ($button->multiple AND $deviceKey !== false)
            $device = $device[$deviceKey];

        return parent::json([
            'button' => $button,
            'device' => $device,
            'allDevice' => $allDevice,
            'devices' => $devices,
        ]);

    }

    public static function save(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 2000);

        if (!$request->insp)
            return parent::error("Неправильные входящие данные", 2001);

        $inspection = Inspections::find($request->insp);
        if ($inspection->done AND !parent::checkRight(['admin'], $request->__user))
            return parent::error("Приёмка уже завершена, возможно это сделал Ваш коллега, попробуйте обновить страницу, чтобы обновить данные на странице", 2002);

        $device = null;
        
        if ($request->id)
            $device = InspectionDevices::find($request->id);

        if ($device === null)
            $device = new InspectionDevices;

        // Формирование имени
        if ($request->name == "add" AND $request->nameAdd) {

            // Поиск наименования в базе устройств
            $name = Devices::where('name', $request->nameAdd)->limit(1)->get();
            $name = count($name) ? $name[0] : false;

            // Если наименование найдено, то идентификатор устройства присваивается переменной имени
            if ($name !== false) {
                $request->name = $name->id;
            }
            // Сохранение нового устройства в базе устройств
            else {
                $devices = new Devices;
                $devices->name = $request->nameAdd;
                $devices->groupId = $request->devices;
                $devices->save();
                $request->name = $devices->id;
            }

        }

        // Если не было указано имя нового устройства, наименование обнуляется
        if ($request->name == "add")
            $request->name = null;

        // Сборка печатей
        $stamps = null;

        if ($request->stamp AND is_array($request->stamp)) {

            $stamp = [];
            foreach ($request->stamp as $row)
                if ($row !== null)
                    $stamp[] = $row;

            $stamps = count($stamp) ? json_encode($request->stamp) : null;

        }

        // Данные для обновления строки
        $device->name = $request->name; // Идентификатор имени устрйоства
        $device->inspection = $request->insp; // Идентификатор приёмки
        $device->multiple = $request->multiple; // Идентификатор приёмки
        $device->typeId = $request->typeId; // Идентификатор кнопки устрйоства
        $device->serial = $request->serial; // Серийный номер устройства
        $device->comment = $request->comment; // Примечание
        $device->count = $request->count; // Количество устройств
        $device->crash = $request->crash ? 1 : 0; // Неисправность устройства
        $device->noinstall = $request->install ? 1 : 0; // /Устройство не установлено
        $device->userId = $request->__user->id; // Идентификатор пользователя
        $device->stamps = $stamps;

        $device->save();

        return parent::json([
            'device' => $device,
            'button' => $request->button,
        ]);

    }

    public static function done(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 3000);

        $data = self::openData($request);

        // Проверка цвета
        $color = true;
        foreach ($data['colors'] as $row)
            if ($row['color'] != "success")
                $color = false;

        if (!count($data['colors']))
            $color = false;

        if (!$color)
            return parent::error("Заполнены не все данные! Убедитесь, чтобы все кнопки устройств на странице приёмки были окрашены в зелёный цвет", 3001);

        $inspection = Inspections::find($request->id);

        if ($inspection->done AND !parent::checkRight(['admin'], $request->__user))
            return parent::error("Приёмка уже завершена, возможно это сделал Ваш коллега, попробуйте обновить страницу, чтобы обновить данные на странице", 3002);

        $inputs = [];

        if (!$inspection->client)
            $inputs[] = 'client';
        if (!$inspection->busMark)
            $inputs[] = 'busMark';
        if (!$inspection->busModel)
            $inputs[] = 'busModel';
        if (!$inspection->busRegNum)
            $inputs[] = 'busRegNum';
        if (!$inspection->busVin)
            $inputs[] = 'busVin';
        
        if (count($inputs))
            return parent::error("Заполните все данные машины", 3002, $inputs);
        
        $inspection->done = date("Y-m-d H:i:s");
        $inspection->save();

        return parent::json($data);

    }

    public static function tape(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 3003);

        $data = (Object) []; // Объект на вывод

        $data->lastTime = time();

        $tape = Inspections::getRowInspectionsForTable($request);
        $data->inspections = self::inspectionRows($tape);

        $data->last = $tape->lastPage(); // Всего страниц
        $data->next = $tape->currentPage() + 1; // Следующая страница

        return parent::json($data);

    }

    public static function inspectionRows($rows) {

        $data = [];

        foreach ($rows as $row) {
            
            $row->dateAdd = parent::createDate($row->created_at);
            $row->dateDone = parent::createDate($row->done);

            $row->fio = parent::getUserFioAll($row, 1);

            $data[] = $row;

        }

        return $data;

    }

    public static function checkUpdateTable(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 3004);

        $request->lastTime = (int) $request->lastTime;

        if (!$request->lastTime)
            return parent::error("Не определено время проверки", 3005);

        if (!$date = date("Y-m-d H:i:s", $request->lastTime))
            return parent::error("Не определено время проверки", 3006);

        $request->date = $date;

        $data = (Object) []; // Объект на вывод
        $data->lastTime = time();

        $tape = Inspections::getRowInspectionsForTable($request);
        $data->inspections = self::inspectionRows($tape);

        return parent::json($data);

    }

    public static function changeBusData(Request $request) {

        if (!parent::checkRight(['admin','inspection'], $request->__user))
            return parent::error("Доступ к приёмке ограничен", 3007);

        $inspection = Inspections::find($request->id);

        if (!$inspection)
            return parent::error("Данныепо этой приёмке не найдены", 3008);

        if ($inspection->done AND !parent::checkRight(['admin'], $request->__user))
            return parent::error("Приёмка завершена, изменять данные уже нельзя", 3009);

        // if (!isset($inspection->{$request->name}))
        //     return parent::error("Отправлены недостоверные данные", 3010);
        
        $inspection->{$request->name} = $request->value;
        $inspection->save();

        return parent::json($inspection);

    }

}
