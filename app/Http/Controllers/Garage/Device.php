<?php

namespace App\Http\Controllers\Garage;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;

use App\Models\Devices;
use App\Models\DevicesGroup;

class Device extends Main
{
    
    public static function getDeviceList(Request $request) {

        if (!parent::checkRight(['admin'], $request->__user))
            return parent::error("Нет доступа к разделу обоудования", 1000);

        $devices = Devices::getDeviceList();

        return parent::json([
            'devices' => $devices,
        ]);

    }

    public static function getDeviceRow(Request $request) {

        if (!parent::checkRight(['admin'], $request->__user))
            return parent::error("Нет доступа к разделу обоудования", 1001);

        $device = Devices::find($request->id);
        $groups = DevicesGroup::orderBy('name')->get();

        return parent::json([
            'device' => $device ?? [],
            'groups' => $groups,
        ]);

    }

    public static function saveDevice(Request $request) {

        if (!parent::checkRight(['admin'], $request->__user))
            return parent::error("Нет доступа к разделу обоудования", 1002);

        if (!$request->name)
            return parent::error("Укажите наименование оборудования", 1003);
        
        $device = $request->id ? Devices::find($request->id) : new Devices;

        $device->name = $request->name;
        $device->groupId = $request->group;

        $device->save();

        return parent::json([
            'device' => $device,
        ]);

    }

}