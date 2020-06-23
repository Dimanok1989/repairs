<?php

namespace App\Http\Controllers\Montage;

use Illuminate\Http\Request;
use App\Http\Controllers\Main;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Montage\Montage;
use App\Models\MontageModel;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\TemplateProcessor;

use DocxMerge\DocxMerge;

class MontageActs extends Main
{
    
    public static function start(Request $request) {

        $request->start = $request->start ? $request->start : date("Y-m-d");
        $request->stop = $request->stop ? $request->stop : date("Y-m-d");

        $list = MontageModel::getAllCompletedMontagesFromPeriod($request);
        $data = Montage::getOneRowInAllMontage($list);

        $createDocx = self::createDocx($data, $request);

        return parent::json($createDocx);

    }

    public static function createDocx($data, $request) {

        $news = [];
        foreach ($data as $act)
            $news[] = self::parceAct($act);

        if (!count($news))
            return false;

        $removed = $news; // Список файлов для удаления

        // Проверка наличия каталога
        $dir = "temp/".date("Y/m/d");
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        // Имя файла
        $name = $request->start . "_" . $request->stop . "_montages_acts.docx";

        $file = storage_path("app/public/$dir/$name");

        if (file_exists($file))
            unlink($file);

        copy($news[0], $file);
        unset($news[0]);

        if (count($news)) {

            $merge = [];
            foreach ($news as $new)
                $merge[] = $new;

            $dm = new DocxMerge();
            $dm->merge($merge, $file);

        }

        foreach ($removed as $file)
            unlink($file);

        return [
            'files' => $news,
            'link' => Storage::disk('public')->url("$dir/$name"),
        ];

    }

    public static function parceAct($act) {

        // Путь до шаблона акта монтажа
        $templates = storage_path("app/public/templates");
        $template = "montage.docx";

        $filial = MontageModel::getFilialName($act->folderMain);
        $filial = $filial[0]->name ?? "______________________________________";

        $data = [
            'date' => date("d.m.Y", strtotime($act->date)),
            'fio' => "Андреянова Василия Владимировича",
            'filial' => $filial,
            'place' => $act->place ? $act->place : "______________________________________",
            'number' => $act->bus ? $act->bus : "______________",
            'regnum' => $act->inputs['busNum'] ?? "______________",
            'iccid' => $act->inputs['iccid'] ?? "---",
        ];

        if (!isset($act->inputs['macAddr']))
            $act->inputs['macAddr'] = "------------";

        for ($i = 0; $i <= mb_strlen($act->inputs['macAddr']); $i++) {
            $key = "m" . ($i + 1);
            $data[$key] = mb_substr($act->inputs['macAddr'], $i, 1);
        }

        if (!isset($act->inputs['serialNum']))
            $act->inputs['serialNum'] = "WM19120177S----";

        for ($i = 0; $i <= mb_strlen($act->inputs['serialNum']); $i++) {
            $key = "s" . ($i + 1);
            $data[$key] = mb_substr($act->inputs['serialNum'], $i, 1);
        }

        $document = new TemplateProcessor($templates . "/" . $template);

        foreach ($data as $key => $value)
            $document->setValue($key, $value);

        // Проверка наличия каталога
        $dir = "temp/".date("Y/m/d");
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        $file = storage_path("app/public/$dir/") . md5($act->id) . ".docx";
        $document->saveAs($file);

        return $file;

    }

}
