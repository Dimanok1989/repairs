<?php

namespace App\Http\Controllers;

use Session;
use App\Http\Controllers\Auth\User;

class Main
{

    /**
     * Метод вывода JSON-строки
     * 
     * @param Array $arr
     * 
     * @return JSON
     */
    public static function json($arr) {

        $json = [
            'done' => 'success',
            'data' => $arr,
        ];

        return response()->json($json, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);

    }


    /**
     * Метод вывода JSON-строки с ошибкой
     * В качестве аргумента принимает как строку так и массив с данными ошибки
     * где первый элемент массива - это код ошибки для её идентификации
     * второй - текст ошибки для вывода
     * 
     * @param Array $err
     * 
     * @return JSON
     */
    public static function error($text = "Неизвестная ошибка", $code = false, $inputs = []) {

        $json = [
            'done' => 'error',
            'error' => $text,
            'code' => $code,
            'inputs' => $inputs,
        ];

        return response()->json($json, 200, ['Content-type' => 'application/json; charset=utf-8'], JSON_UNESCAPED_UNICODE);

    }


    /**
     * Метод преобразования данных из JSON в объект
     */
    public static function decode($resp) {

        return $resp->original;

    }


    /**
     * Метод определения текщей и следующей страниц для полученных
     * данных методом paginate()
     */
    public static function getPaginateData($paginate) {

        $data = (Object) [];

        $data->last = $paginate->lastPage(); // Всего страниц
        $data->next = $paginate->currentPage() + 1; // Следующая страница

        return $data;

    }


    /**
     * Метод проверки прав пользователя в текщей сессии
     * 
     * @param String|Array $access Наименование столбца права
     * @param String|Object $data Токен или объект данных пользователя
     * 
     * @return Bool
     */
    public static function checkRight($access = false, $data = false) {

        if (!$access)
            return false;

        if (is_object($data))
            $user = $data;
        elseif (is_array($data))
            $user = (Object) $data;
        else
            $user = Session::get('user');

        if (!$user AND !$data)
            return false;

        if (!$user AND $data)
            $user = User::getUserDataFromToken($data);

        if (!$user)
            return false;

        // Если $access = массив, будет проверен каждый его элемент на соответствие прав
        // Если будет найдено хотя бы одно совпадение с параметром 1, то функция вернет
        // положительный ответ
        if (is_array($access)) {

            $checked = false;
            foreach ($access as $row) {
                if (isset($user->access->$row))
                    if ($user->access->$row == 1)
                        $checked = true;
            }

            return $checked;

        }

        if (!isset($user->access->$access))
            return false;

        if (!$user->access->$access)
            return false;

        return true;

    }


    /**
     * Метод преобразования месяца в слово
     * 
     * @param Int $time Метка системного времени Unix
     * @param Int $type Требуемый формат месяца
     *          0 - январь
     *          1 - января
     *          2 - янв
     * 
     * @return String|Bool
     */
    public static function dateToMonth($time = flase, $type = 0) {

        $months = [
            ['январь','января','янв'],
            ['февраль','февраля','фев'],
            ['март','марта','мар'],
            ['апрель','апреля','апр'],
            ['май','мая','мая'],
            ['июнь','июня','июня'],
            ['июль','июля','июля'],
            ['август','августа','авг'],
            ['сентябрь','сентября','сен'],
            ['октябрь','октября','окт'],
            ['ноябрь','ноября','нояб'],
            ['декабрь','декабря','дек'],
        ];

        $m = date("n", $time) - 1;

        return $months[$m][$type] ?? false;

    }


    /**
     * Метод преобразования даты
     * 
     * @param Datetime $datetime Время преобразования
     * 
     * @return String|Bool
     */
    public static function createDate($datetime) {

        if (!$time = strtotime($datetime))
            return false;

        // Сверка даты
        $now = date('z');
        $before = date('z', $time);
    
        if ($now-$before == 0)
            return date("сегодня в H:i", $time);

        if ($now-$before == 1)
            return date("вчера в H:i", $time);

        $month = self::dateToMonth($time, 2);

        if (date("Y") != date("Y", $time))
            return date("d {$month} Y в H:i", $time);        

        return date("d {$month} в H:i", $time);

    }

    /**
     * Метод проверки номера телефона
     */
    public static function checkPhone($phone) {

        $phone = preg_replace("/[^0-9]/", '', $phone);

        // Определяется первый символ номера
        $first = substr($phone, "0", 1);

        if (($first == 8 OR $first == 7) AND preg_match("/^[0-9]{11,11}+$/", $phone))
            return "7" . substr($phone, -10);

        if ($first != 7 && preg_match("/^[0-9]{10,10}+$/", $phone))
            return "7" . $phone;

        return false;

    }

    /**
     * Метод вывода "красивого" номера телефона
     */
    public static function printPhone($phone) {
        return "+".$phone[0]." (".$phone[1].$phone[2].$phone[3].") ".$phone[4].$phone[5].$phone[6]."-".$phone[7].$phone[8].$phone[9].$phone[10];
    }

    /**
     * Метод формирования ФИО пользователя
     * 
     * @param String $firstname Фамилия
     * @param String $lastname Имя
     * @param String $fathername Отчество
     * @param Int|String $type Тип сокращения
     *      0 - Иванов Иван Иванович
     *      1 - Иванов И.И.
     *      2|sb - Иван Иванович И.
     *      3 - Иван И.
     */
    public static function getUserFio($firstname = false, $lastname = false, $fathername = false, $type = 0) {

        $fio = "";

        if (!$firstname AND !$lastname AND !$fathername)
            return $fio;

        switch ($type) {
            case 0:
                $fio = $firstname;
                $fio .= $lastname ? " " . $lastname : "";
                $fio .= $fathername ? " " . $fathername : "";
                break;

            case 1:
                $fio = $firstname;
                $fio .= $lastname ? " " . mb_substr($lastname, 0, 1) . "." : "";
                $fio .= $fathername ? "" . mb_substr($fathername, 0, 1) . "." : "";
                break;

            case 2:
            case 'sb':
                $fio = $lastname ? $lastname : "";
                $fio .= $fathername ? " " . $fathername : "";
                $fio .= $firstname ? " " . mb_substr($firstname, 0, 1) . "." : "";
                break;

            case 3:
                $fio = $lastname ? $lastname : "";
                $fio .= $firstname ? " " . mb_substr($firstname, 0, 1) . "." : "";
                break;
                
            default:
                $fio = $firstname;
                $fio .= $lastname ? " " . $lastname : "";
                $fio .= $fathername ? " " . $fathername : "";
                break;

        }

        return $fio;

    }

    /**
     * Метод преобразования короткой ссылки в идентификатор
     */
    public static function dec2link($id) {

        $digits = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $link = '';
    
        do {
            $dig = $id%62;
            $link = $digits[$dig].$link;
            $id = floor($id/62);
        } while ($id != 0);
        
        return $link;
        
    }
    
    /**
     * Метод преобразования идентификатора в короткую ссылку
     */
    public static function link2dec($link) {

        // dd(preg_match("/[A-Za-z0-9]/i", $link));

        // if ($link === false)
        //     return 0;

        $digits = [
            '0' => 0,
            '1' => 1,
            '2' => 2,
            '3' => 3,
            '4' => 4,
            '5' => 5,
            '6' => 6,
            '7' => 7,
            '8' => 8,
            '9' => 9,
            'a' => 10,
            'b' => 11,
            'c' => 12,
            'd' => 13,
            'e' => 14,
            'f' => 15,
            'g' => 16,
            'h' => 17,
            'i' => 18,
            'j' => 19,
            'k' => 20,
            'l' => 21,
            'm' => 22,
            'n' => 23,
            'o' => 24,
            'p' => 25,
            'q' => 26,
            'r' => 27,
            's' => 28,
            't' => 29,
            'u' => 30,
            'v' => 31,
            'w' => 32,
            'x' => 33,
            'y' => 34,
            'z' => 35,
            'A' => 36,
            'B' => 37,
            'C' => 38,
            'D' => 39,
            'E' => 40,
            'F' => 41,
            'G' => 42,
            'H' => 43,
            'I' => 44,
            'J' => 45,
            'K' => 46,
            'L' => 47,
            'M' => 48,
            'N' => 49,
            'O' => 50,
            'P' => 51,
            'Q' => 52,
            'R' => 53,
            'S' => 54,
            'T' => 55,
            'U' => 56,
            'V' => 57,
            'W' => 58,
            'X' => 59,
            'Y' => 60,
            'Z' => 61
        ];
        
        $id = 0;
    
        for ($i = 0; $i < mb_strlen($link); $i++)
            $id += $digits[$link[(mb_strlen($link)-$i-1)]] * pow(62, $i);
            
        return $id;
        
    }
    
    /**
	 * Метод проверяет тип файла по его MIME-типу
     * 
	 * @param String $mimeType
     * 
	 * @return Bool
	 */
	public static function checkMimeType($mimeType) {

		if (in_array($mimeType, self::$mimeTypes))
			return true;

		return false;

	}

	/**
	 * Разрешенные типы файлов к загрузке
	 */
	public static $mimeTypes = [
		'image/gif', // GIF(RFC 2045 и RFC 2046)
		'image/jpeg', // JPEG (RFC 2045 и RFC 2046)
		'image/pjpeg', // JPEG
		'image/png', // Portable Network Graphics(RFC 2083)
		// 'application/octet-stream', // png
		'image/tiff', // TIFF(RFC 3302)
		'video/mpeg', // MPEG-1 (RFC 2045 и RFC 2046)
		'video/mp4', // MP4 (RFC 4337)
		'video/ogg', // Ogg Theora или другое видео (RFC 5334)
		'video/quicktime', // QuickTime
		'video/webm', // WebM
		'video/x-ms-wmv', // Windows Media Video
		'video/x-flv', // FLV
		'video/3gpp', // 3gpp .3gp
		'video/3gpp2', // .3gpp2 .3g2
		'application/vnd.ms-excel', // Microsoft Excel файлы
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // Microsoft Excel 2007 файлы
		'application/vnd.ms-powerpoint', // Microsoft Powerpoint файлы
		'application/vnd.openxmlformats-officedocument.presentationml.presentation', // Microsoft Powerpoint 2007 файлы
		'application/msword', // Microsoft Word файлы
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // Microsoft Word 2007 файлы
		'application/ogg', // Ogg (RFC 5334)
		'application/pdf', // Portable Document Format, PDF (RFC 3778)
		'application/zip', // ZIP
		'application/x-rar-compressed', // RAR
		'application/gzip', // Gzip
		'application/x-tex', // TeX
		'application/msword', // DOC
		'audio/basic', // mulaw аудио, 8 кГц, 1 канал (RFC 2046)
		'audio/L24', // 24bit Linear PCM аудио, 8-48 кГц, 1-N каналов (RFC 3190)
		'audio/mp4', // MP4
		'audio/aac', // AAC
		'audio/mpeg', // MP3 или др. MPEG (RFC 3003)
		'audio/ogg', // Ogg Vorbis, Speex, Flac или др. аудио (RFC 5334)
		'audio/vorbis', // Vorbis (RFC 5215)
		'audio/x-ms-wma', // Windows Media Audio
		'audio/x-ms-wax', // Windows Media Audio перенаправление
		'audio/vnd.rn-realaudio', // RealAudio
		'audio/vnd.wave', // WAV(RFC 2361)
		'audio/webm', // WebM
		'application/octet-stream', // PDF
		'text/plain', // txt-блокнот
    ];
    
    /**
	 * Метод перевода размера файла из байтов в Кб, Мб и тд
     * 
	 * @param Int $size
     * 
	 * @return String
	 */
	public static function formatSize($size) {

		$metrics = [
			0 => 'байт',
			1 => 'Кб',
			2 => 'Мб',
			3 => 'Гб',
			4 => 'Тб',
		];

		$metric = 0;  

		while(floor($size / 1024) > 0){
			$metric ++;
			$size /= 1024;
		}     

		return round($size, 1) . " " . (isset($metrics[$metric]) ? $metrics[$metric] : '');

    }

    /**
     * Метод формирования объекта данных файла
     */
    public static function getNormalDataImageRow($row) {

        $row->formatSize = self::formatSize($row->size);

        $row->link = \Illuminate\Support\Facades\Storage::disk('public')->url("{$row->path}/{$row->filename}");

        $row->dateAdd = self::createDate($row->date);

        return $row;

    }
    
    /**
     * Метод сжатия и поворота изобравжения
     */
    public static function resizeUploadedImg($img) {

        $file = \Intervention\Image\Facades\Image::make($img);

        // Определение ширины и высоты
        $w = $file->width();
        $h = $file->height();
        $exif = $file->exif();

        $width = $w > $h ? 1600 : null;
        $height = $w > $h ? null : 1600;

        $file->resize($width, $height, function($c) {
            $c->aspectRatio();
        });

        // Поворот изобравжения
        if (isset($exif['Orientation'])) {

            if ($exif['Orientation'] == 3)
                $file->rotate(180);
            elseif ($exif['Orientation'] == 6)
                $file->rotate(-90);
            elseif ($exif['Orientation'] == 8)
                $file->rotate(90);

        }
        
        $file->save($img);

        return true;

    }

}