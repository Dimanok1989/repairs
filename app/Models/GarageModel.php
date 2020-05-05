<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class GarageModel extends Model
{

    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'bus';

    /**
     * Определяет необходимость отметок времени для модели.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Метод сохранения лога изменений
     */
    public static function logBusData($data) {

        return DB::table('bus_log')->insert($data);

    }
    
    /**
     * Поиск по гаражу
     */
    public static function getBusList($request) {

        $data = DB::table('bus')
        ->select('bus.*', 'projects.name as client')
        ->leftjoin('projects', 'projects.id', '=', 'bus.projectId');

        if ($request->search)
        $data = $data->where(function($query) use ($request) {
            $query->where('bus.garage', 'LIKE', "%{$request->search}%")
            ->orWhere(DB::raw("CONCAT(IFNULL(bus.mark,''),' ',IFNULL(bus.model,''))"), 'LIKE', "%{$request->search}%")
            ->orWhere('bus.vin', 'LIKE', "%{$request->search}%")
            // ->orWhere('modif', 'LIKE', "%{$request->search}%")
            ->orWhere('projects.name', 'LIKE', "%{$request->search}%")
            ->orWhere('bus.number', 'LIKE', "%{$request->search}%");
        });
        
        $data = $data->orderBy("bus." . $request->order, $request->orderBy)->paginate(50);

        return $data;

    }

    /**
     * Список машин по гаражному номеру
     */
    public static function getBusFromGarageNum($num = 0) {

        return DB::table('bus')
        ->select('bus.*', 'projects.name')
        ->leftjoin('projects', 'projects.id', '=', 'bus.projectId')
        ->where('bus.garage', (int) $num)->get();

    }

}