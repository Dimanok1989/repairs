<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class InspectionDevices extends Model
{
    
    /**
     * Связанная с моделью таблица.
     *
     * @var string
     */
    protected $table = 'inspection_devices';

    public static function getButtonsDevices($id = false) {

        $data = DB::table('inspection_buttons');

        if ($id)
            $data = $data->where('id', $id);
        
        return $data->get();

    }

    public static function getDevicesFullData($request) {

        return DB::table('inspection_devices')
        ->select('inspection_devices.*', 'devices.name as deviceName')
        ->leftjoin('devices', 'devices.id', '=', 'inspection_devices.name')
        ->where('inspection_devices.inspection', $request->id)->get();

    }

}