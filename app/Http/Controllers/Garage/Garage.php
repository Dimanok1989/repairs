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

}