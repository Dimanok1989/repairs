<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Session;

use App\Http\Controllers\Service\Application;
use App\Http\Controllers\Service\Service;

use App\Models\ApplicationModel;
use App\Models\ServiceModel;
use App\Models\ProjectModel;
use App\Models\SearchModel;


class Search extends Main
{


    public static function search(Request $request) {

        $request->text = $request->text ? urldecode($request->text) : "";

        // Поиск заявок
        $applications = self::searchApplications($request);

        return parent::json([
            'text' => $request->text,
            'applications' => $applications,
            'request' => $request->all(),
            'user' => $request->__user,
        ]);

    }


    /**
     * Поиск по заявкам
     */
    public static function searchApplications(Request $request) {

        $data = SearchModel::searchApplications($request);

        // Поиск задвоенных данных из-за leftjoin 
        $rows = $ids = [];
        foreach ($data as $row) {
            
            if (!in_array($row->id, $ids)) {
                $row->fulldata = $data->lastPage() == $data->currentPage() ? true : false;
                $ids[] = $row->id;
                $rows[] = $row;
            }

        }

        $applications = Application::getApplicationsListEditRow($rows);

        return $applications;

    }


}