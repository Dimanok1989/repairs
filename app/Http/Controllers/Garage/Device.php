<?php

namespace App\Http\Controllers\Garage;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;

use App\Models\Devices;
use App\Models\DevicesGroup;

class Device extends Main
{
    
    public static function getDeviceList(Request $request) {

        $devices = Devices::getDeviceList();

        return parent::json([
            'devices' => $devices,
        ]);

    }

    public static function getDeviceRow(Request $request) {

        $device = Devices::find($request->id);
        $groups = DevicesGroup::orderBy('name')->get();

        return parent::json([
            'device' => $device ?? [],
            'groups' => $groups,
        ]);

    }

    public static function saveDevice(Request $request) {

        $device = $request->id ? Devices::find($request->id) : new Devices;

        $device->name = $request->name;
        $device->groupId = $request->group;

        $device->save();

        return parent::json([
            'device' => $device,
        ]);

    }

}