<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ServiceFiles extends Main
{
    
    /**
     * Заполнение шаблона акта сервиса
     * 
     * @param Array $data Массив с переменными для замены в шаблоне
     * @param Array $tables Массив с таблицами
     */
    public static function createActService($data, $tables = []) {

        $templates = storage_path("app/public/templates");
        $template = "service.docx";

        // Наименование файла
        $name = $data['application'] . "_" . $data['busNum'] . "_" . date("Ymd_His").".docx";

        $dir = "temp/".date("Y/m/d");
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        $link = Storage::disk('public')->url("{$dir}/{$name}");

        // $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $document = new \PhpOffice\PhpWord\TemplateProcessor($templates . "/" . $template);

        $cloneRow = 0;
        foreach ($tables as $key => $table) {

            $cloneRow = 0;
            foreach ($table as $rows) {

                $cloneRow++;
                foreach ($rows as $cell => $value) {
                    $var = $key . $cell . "#" . $cloneRow;
                    $data[$var] = $value;
                }

            }

        }

        // return $data;

        if ($cloneRow) {
            $document->cloneRow('t1n', $cloneRow);
            $document->cloneRow('t2n', $cloneRow);
        }

        foreach ($data as $key => $value)
            $document->setValue($key, $value);        

        $dir = storage_path("app/public") . "/" . $dir;
        $document->saveAs("{$dir}/{$name}");

        return $link;

    }

}