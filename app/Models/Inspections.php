<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class Inspections extends Model
{
    
    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'inspections';

    public static function getAllRowData($id) {

        $data = DB::table('inspections')
        ->select('inspections.*', 'projects.name as clientName')
        ->leftjoin('projects', 'projects.id', '=', 'inspections.client')
        ->where('inspections.id', $id)
        ->get();

        return count($data) ? $data[0] : [];

    }

    public static function getRowInspectionsForTable($date = false) {

        $data = DB::table('inspections')
        ->select(
            'inspections.*',
            'projects.name as clientName',
            'users.firstname',
            'users.lastname',
            'users.fathername'
        )
        ->leftjoin('projects', 'projects.id', '=', 'inspections.client')
        ->leftjoin('users', 'users.id', '=', 'inspections.userId')
        ->orderBy('inspections.id', 'DESC');

        if ($date)
            return $data->where('updated_at', '>=', $date)->get();
        
        return $data->paginate(50);

    }

}
