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

    public static function getRowInspectionsForTable($request) {

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
        ->orderBy('inspections.id', 'DESC')
        ->where(function ($query) use ($request) {
            $query->whereIn('inspections.client', $request->__user->clientsAccess)
            ->orWhere('inspections.client', NULL);
        });

        if ($request->date)
            return $data->where('updated_at', '>=', $request->date)->get();
        
        return $data->paginate(50);

    }

}
