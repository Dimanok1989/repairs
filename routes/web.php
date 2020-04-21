<?php

use Illuminate\Support\Facades\Route;
use DB;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

/** Главная страница */
Route::get('/', 'Pages@main')->name('mainpage');
/** Выход */
Route::get('/logout', 'Pages@logout');

Route::get('/tlg', function() {

    $telegram = new \Telegram\Bot\Api('1136056493:AAFm7wi-YGn7tzuU7si4EhNMHEJqwfLnkDU');

    $text = "*Test*\n_test_\n`Test`";

    dd($telegram->sendMessage([
        'chat_id' => '424548477', 
        'text' => $text,
        'parse_mode' => 'Markdown',
    ]));

});

// Route::get('/busInsert', function() {

//     $rows = DB::table(DB::raw('`ttm.kolgaev.ru`.`ttm_bus`'))->get();

//     $data = [];
//     foreach ($rows as $row) {

//         $name = explode(" ", $row->name);

//         $mark = NULL;
//         $model = NULL;

//         if (isset($name[0])) {
//             if ($name[0] != "")
//                 $mark = $name[0];
//         }

//         if (isset($name[1])) {
//             if ($name[1] != "")
//                 $model = $name[1];
//         }

//         $add = [
//             'garage' => $row->bus,
//             'vin' => $row->vin != "" ? $row->vin : NULL,
//             'mark' => $mark,
//             'model' => $model,
//             'modif' => $row->modif,
//             'year' => (int) $row->year ? (int) $row->year : NULL,
//             'number' => $row->num != "" ? $row->num : NULL,
//         ];

//         $data[] = $add;

//     }

//     DB::table('bus')->insert($data);

//     dd($data);

// });


/** Админ панель */
Route::group(['prefix' => 'admin'], function () {

    /** Главная страница админки */
    Route::get('/', 'PagesAdmin@main')->name('admin');

    /** Список пользователей */
    Route::group(['prefix' => 'users'], function () {        
        /** Главная страницы администрирования сотрудников */
        Route::get('/', 'PagesAdmin@users')->name('adminusers');
        /** Страница групп пользователей */
        Route::get('/groups', 'PagesAdmin@usersgroups')->name('adminusersgroups');
    });

    /** Настройка проектов */
    Route::group(['prefix' => 'projects'], function () {        
        /** Главная страницы администрирования сотрудников */
        Route::get('/', 'PagesAdmin@projects')->name('adminprojects');
        /** Главная страницы администрирования сотрудников */
        Route::get('/{id}', 'PagesAdmin@project');
    });

    /** Работа с подвижным составом */
    Route::group(['prefix' => 'bus'], function () {        
        /** Главная страницы администрирования сотрудников */
        Route::get('/', 'PagesAdmin@bus')->name('adminbus');
        /** Главная страницы администрирования сотрудников */
        Route::get('/{id}', 'PagesAdmin@project');
    });    

    /** Общие страницы админки монтажа */
    Route::group(['prefix' => 'montage'], function () {
        /** Главная страница админки монтажа */
        Route::get('/', 'PagesAdmin@montage')->name('montage');
        /** Страница загрузки файла списка задач */
        Route::get('/create{id}', 'PagesAdmin@createTask');
        /** Страница списка задач по монтажу */
        Route::get('/tasks{id}', 'PagesAdmin@tasks');
    });

    /** Страницы админки одного раздела монтажа */
    Route::group([
        'prefix' => 'montage{id}',
        'where' => [
            'id' => '[0-9]+'
        ]
    ], function () {
        Route::get('/', 'PagesAdmin@montageId');
    });
    
});


/** Личный кабинет */
Route::group(['prefix' => 'user'], function () {

    /** Страница настроек */
    Route::get('/settings', 'Pages@userSettings')->name('usersettings');

});


/** Страница фильтра и поиска заявок */
Route::get('/applications', 'Pages@showApplicationsList')->name('applicatioslist');
Route::get('/applications{client}', 'Pages@showApplicationsList');

/** Страница заявки */
Route::get('/id{link}', 'Pages@showApplication')->name('application');

/** Страница ленты работ */
Route::get('/service', 'Pages@serviceWorkTape');

/** Страница выбора заказчика для добавления заявки */
Route::get('/add', 'Pages@SelectForaddApplication')->name('SelectForaddApplication');

/** Глобальный поиск */
Route::get('/search', 'Pages@search')->name('search');

/** Все комментарии */
Route::get('/comments', 'Pages@comments')->name('comments');

/** Страница подачи заявок */
Route::get('/{project}', 'Pages@addRequest')->name('addRequest');