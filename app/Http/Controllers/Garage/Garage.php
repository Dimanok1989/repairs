<?php

namespace App\Http\Controllers\Garage;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;

// use App\Http\Controllers\Admin\Projects;
// use App\Http\Controllers\Service\Service;

// use App\Models\ApplicationModel;
// use App\Models\ProjectModel;
use App\Models\GarageModel;

class Garage extends Main
{
    
    /**
     * Список строк с подвижным составом
     */
    public static function getBusList(Request $request) {

        $data = self::getBusListData($request);

        return parent::json($data);
        
    }

    /**
     * Данные для списка подвижного состава
     */
    public static function getBusListData(Request $request) {

        // Првоерка параметров сортировки
        $request->order = "id";
        $request->orderBy = "DESC";
        if ($request->sort) {

            $sort = explode("-", $request->sort);

            if (in_array($request->sort, ["date-asc","date-desc","garage-asc","garage-desc"])) {
                $request->order = $sort[0];
                $request->orderBy = $sort[1];
            }

        }

        // Поисковая фраза
        if ($request->search)
            $request->search = urldecode($request->search);

        $paginate = GarageModel::getBusList($request); // Запрос в БД

        $data = parent::getPaginateData($paginate); // Данные нумирации страниц

        $data->rows = self::getBusRowsData($paginate); // Строки на вывод
        $data->search = $request->search ? urldecode($request->search) : "";

        return $data;

    }

    /**
     * Метод обработки каждой строки данных машины
     */
    public static function getBusRowsData($rows) {

        $data = []; // Данные на вывод

        foreach ($rows as $row) {

            foreach ($row as $key => $val)
                if (!$val AND !is_array($val))
                    $row->$key = "";

            $data[] = $row;

        }

        return $data;

    }

    /**
     * Создание новой машины
     */
    public static function addNewBus(Request $request) {

        if (!parent::checkRight(['admin'], $request->__user))
            return parent::error("Нет доступа к созданию новой машины", 2000);

        $bus = $request->id ? GarageModel::find($request->id) : new GarageModel;

        $bus->projectId = $request->client;
        $bus->garage = $request->garage;
        $bus->vin = $request->vin;
        $bus->mark = $request->mark;
        $bus->model = $request->model;
        $bus->modif = $request->modif;
        $bus->year = $request->year;
        $bus->number = $request->number;

        $bus->save();

        // История изменений
        $log = [
            'idBus' => $bus->id,
            'projectId' => $bus->garage,
            'vin' => $bus->vin,
            'mark' => $bus->mark,
            'model' => $bus->model,
            'modif' => $bus->modif,
            'year' => $bus->year,
            'number' => $bus->number,
            'userId' => $request->__user->id,
        ];

        GarageModel::logBusData($log);

        return parent::json([
            'bus' => $bus,
            'log' => $log,
        ]);

    }

    /**
     * Страница машины со всеми данными из всех разделов
     */
    public static function getOneBusAllData(Request $request) {

        // Данные машины
        $bus = GarageModel::find($request->id);

        return self::serializeDataBus($bus);

    }

    public static function serializeDataBus($bus) {

        // Все фотографии машины
        $images = [];

        // Приёмка машины
        $inspections = [];

        // Поиск заявок
        $applications = [];

        // Поиск сервиса по машине
        $service = [];

        // Поиск монтажа
        $montages = [];

        return [
            'bus' => $bus,
            'applications' => $applications,
            'service' => $service,
            'images' => $images,
            'montages' => $montages,
            'inspections' => $inspections,
        ];

    }

}