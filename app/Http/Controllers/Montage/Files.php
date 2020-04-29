<?php

namespace App\Http\Controllers\Montage;

use App\Http\Controllers\Main;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Montage\Montage;
use App\Models\MontageModel;

class Files extends Main
{

    /**
     * Наложение данных монтажа на картинку с шаблоном акта
     */
    public static function createJpegAct(Request $request) {

        // Каталог с шаблонами
        $dir = public_path("templates/montage");

        // Шаблон акта
        $img = imagecreatefromjpeg("{$dir}/antison-1.jpg");

        // Данные монтажа
        if (!$montage = Montage::getDataOneMontage($request))
            return self::doneCreateJpegAct($img);

        // Данные каталога
        if (!$place = MontageModel::getFolderData($montage->folder))
            return self::doneCreateJpegAct($img);

        // Данные филиала
        if (!$filial = MontageModel::getFolderData($place->main))
            return self::doneCreateJpegAct($img);

        // Данные для акта
        $data = [];
        foreach ($montage->inputs as $input)
            $data[$input->name] = $input->value;

        // Цвет текста
        $color = imagecolorallocate($img, 63, 72, 204);

        // Путь к шрифту
        $font = "{$dir}/CALIBRI.TTF";

        // $img - Изображение
        // 25 - размер шрифта
        // 0 - угол поворота
        // 812 - смещение по горизонтали
        // 226 - смещение по вертикали

        imagettftext($img, 25, 0, 812, 226, $color, $font, $montage->dateCompleted ?? date("d.m.Y"));
        imagettftext($img, 30, 0, 576, 783, $color, $font, "Андреянова Василия Владимировича");
        imagettftext($img, 30, 0, 437, 1408, $color, $font, $filial->name);
        imagettftext($img, 30, 0, 476, 1468, $color, $font, $place->name);

        // МАК-адрес
        $pos = 480;
        if (isset($data['macAddr'])) {
            for ($i = 0; $i <= mb_strlen($data['macAddr']); $i++) {
                imagettftext($img, 38, 0, $pos, 1553, $color, $font, mb_substr($data['macAddr'], $i, 1));
                $pos += 79;
            }
        }

        // Серийный номер
        $pos = 1283;
        if (isset($data['serialNum'])) {

            $data['serialNum'] = str_replace("WM19120177S", "", $data['serialNum']);

            for ($i = 0; $i <= mb_strlen($data['serialNum']); $i++) {
                imagettftext($img, 38, 0, $pos, 1673, $color, $font, mb_substr($data['serialNum'], $i, 1));
                $pos += 75;
            }

        }

        imagettftext($img, 30, 0, 400, 1792, $color, $font, $data['iccid'] ?? "");
        imagettftext($img, 30, 0, 1105, 1923, $color, $font, $montage->bus ?? "");
        imagettftext($img, 30, 0, 707, 1982, $color, $font, $data['busNum'] ?? "");

        return self::doneCreateJpegAct($img);

    }

    public static function doneCreateJpegAct($img) {

        header('Content-type: image/jpeg');
        imagejpeg($img);
        imagedestroy($img);

    }

    /**
     * Формирование отчета за период
     */
    public static function excel(Request $request) {

        $start = strtotime($request->start) + 43200;
		$end = strtotime($request->stop) + 129600;

		if ($end < $start OR $end == 129600 OR $start == 43200)
            return parent::error("Указан ошибочный период", 6001);
            
        if ($end > time())
            $end = time();

        $request->start = date("Y-m-d H:i:s", $start);
        $request->stop = date("Y-m-d H:i:s", $end);

        // Данные всего монтажа за период
        $catList = Montage::getAllCompletedMontagesFromPeriod($request);
            
        // Массив с названиями столбцов
		$columns = [
			'Дата',
			'Время',
			'Площадка',
			'Гаражный номер',			
			'Гос. номер',
			'VIN',
			's/n',
			'MAC',
			'ICC-ID',
			'Люди',
			'ФИО',
			'Ссылка'
        ];

        $name = "report_montage_".date("Ymd_His").".xls";

        $dir = "temp/".date("Y/m/d");
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        $link = Storage::disk('public')->url("{$dir}/{$name}");
        
        $document = new \PHPExcel();
        $sheet = $document->setActiveSheetIndex(0); // Выбираем первый лист в документе

		$columnPosition = 0; // Начальная координата x
        $startLine = 2; // Начальная координата y

        $datestart = date("d.m.Y H:i", $start);
        $datestop = date("d.m.Y H:i", $end);

        // Вставляем заголовок в "A2" 
		$sheet->setCellValueByColumnAndRow($columnPosition, $startLine, "Отчет Монтажа с $datestart по $datestop");
        
        // Выравниваем по центру
		$sheet->getStyleByColumnAndRow($columnPosition, $startLine)
		->getAlignment()
		->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

		$sheet->getStyleByColumnAndRow($columnPosition, $startLine)
		->getFont()
		->setBold(true)
		->setSize(14);

		// Объединяем ячейки "A2:C2"
		$document->getActiveSheet()
		->mergeCellsByColumnAndRow($columnPosition, $startLine, $columnPosition+(count($columns)-1), $startLine);

		$startLine++;

		// Вставляем подзаголовок в "A3"
		$sheet->setCellValueByColumnAndRow($columnPosition, $startLine, "Дата формирования: ".date("d.m.Y H:i:s"));

		// Выравниваем по центру
		$sheet->getStyleByColumnAndRow($columnPosition, $startLine)
		->getAlignment()
		->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);

		$sheet->getStyleByColumnAndRow($columnPosition, $startLine)
		->getFont()
		->setSize(10);

		// Объединяем ячейки "A2:C2"
		$document->getActiveSheet()
		->mergeCellsByColumnAndRow($columnPosition, $startLine, $columnPosition+(count($columns)-1), $startLine);

		// Перекидываем указатель на следующую строку
		$startLine += 2;

		// Указатель на первый столбец
		$currentColumn = $columnPosition;

		// Массив со стилем бордера
		$style = [
			'style' => \PHPExcel_Style_Border::BORDER_DOTTED,
			'color' => [
				'rgb' => '808080'
			]
        ];
        
		$border = [
			'bottom' => $style,
			'top' => $style,
			'right' => $style,
			'left' => $style,
        ];
        
        // Формируем шапку
		foreach ($columns as $column) {

			$sheet->getStyleByColumnAndRow($currentColumn, $startLine)
			->getBorders()
			->applyFromArray($border);

			$sheet->getStyleByColumnAndRow($currentColumn, $startLine)
			->getAlignment()
			->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
			->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
			->setWrapText(true);

			$sheet->getStyleByColumnAndRow($currentColumn, $startLine)
			->getFont()
			->setBold(true);

			$sheet->getColumnDimensionByColumn($currentColumn)->setAutoSize(true);

		    $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $column);

		    // Смещаемся вправо
            $currentColumn++;
            
		}

        $sheet->getRowDimension(5)->setRowHeight(60);

        // Формируем список
		$count = 1;
		foreach ($catList as $key => $row) {

			// Перекидываем указатель на следующую строку
			$startLine++;

			//$sheet->getRowDimension($startLine)->setRowHeight(40);
		    
		    // Указатель на первый столбец
		    $currentColumn = $columnPosition;

			$sheet->getStyleByColumnAndRow($currentColumn, $startLine)
			->getBorders()
			->applyFromArray($border);

			$sheet->getStyleByColumnAndRow($currentColumn, $startLine)
			->getAlignment()
			->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
			->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
			->setWrapText(true);
		    
		    // Вставляем порядковый номер
		    // $sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $count);

		    // Ставляем информацию об имени и цвете
		    foreach ($row->catItems as $value) {		        

		        $sheet->getStyleByColumnAndRow($currentColumn, $startLine)
				->getBorders()
				->applyFromArray($border);

				if ($currentColumn != count($columns)) {
					$sheet->getStyleByColumnAndRow($currentColumn, $startLine)
					->getAlignment()
					->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER)
					->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER)
					->setWrapText(true);
				}

		    	$sheet->setCellValueByColumnAndRow($currentColumn, $startLine, $value);

		    	$currentColumn++;
		    }

		    $count++;
		}

        $objWriter = \PHPExcel_IOFactory::createWriter($document, 'Excel5');
        
        $file = storage_path("app/public/" . $dir);
        $objWriter->save($file . "/" . $name);

        return parent::json([
            'link' => $link,
        ]);

    }

    /**
     * Формирование архива с файлами монтажа
     */
    public static function zip(Request $request) {

        if (!parent::checkRight(['admin'], $request->__user))
            return parent::error("Нет доступа к монтажу", 7000);

        if (!$request->id)
            return parent::error("Неправильный запрос", 7001);

        // Данные монтажа
        if (!$montage = Montage::getDataOneMontage($request))
            return parent::error("Данные монтажа не найдены", 7002);

        // Наименование файла архива
        $name = $montage->bus . "_montage_" . $montage->id . date("_YmdHis", strtotime($montage->date)) . date("_YmdHis") . ".zip";

        // Каталог временных файлов
        $dir = "temp/".date("Y/m/d");
        if (!Storage::disk('public')->exists($dir))
            Storage::disk('public')->makeDirectory($dir);

        // Ссылка на скачивание файла
        $link = Storage::disk('public')->url("{$dir}/{$name}");

        // Формирование архива
        $zip = new \ZipArchive();

        $fileDir = storage_path("app/public/" . $dir);
        $fileZip = $fileDir . "/" . $name;

        // Список файлов монтажа
        $montage->bus = (int) $montage->bus;
        $files = Storage::disk('public')->files("montages/{$montage->folder}/{$montage->bus}");

        if ($zip->open($fileZip, \ZipArchive::CREATE) !== true)
            return parent::error("Ошибка при создании архива", 7003);

        foreach ($files as $file) {
            $add = storage_path("app/public/") . $file;
            $name = basename($file);
            $zip->addFile($add, $name);
        }

        $zip->close();

        return parent::json([
            'link' => $link,
        ]);

    }

}