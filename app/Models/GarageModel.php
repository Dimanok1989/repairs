<?php

namespace App\Models;

use DB;

class GarageModel
{
    
    public static function getBusList($request) {

        $data = DB::table('bus');

        if ($request->search)
        $data = $data->where(function($query) use ($request) {
            $query->where('garage', 'LIKE', "%{$request->search}%")
            ->orWhere(DB::raw("CONCAT(IFNULL(mark,''),' ',IFNULL(model,''))"), 'LIKE', "%{$request->search}%")
            ->orWhere('vin', 'LIKE', "%{$request->search}%")
            // ->orWhere('modif', 'LIKE', "%{$request->search}%")
            ->orWhere('number', 'LIKE', "%{$request->search}%");
        });
        
        $data = $data->orderBy($request->order, $request->orderBy)->paginate(50);

        return $data;

    }

}
