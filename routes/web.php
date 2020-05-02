<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/busInsert', function() {
    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    dd($phpWord);
});


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
        Route::get('/', 'PagesAdmin@montage')->name('adminmontage');
    });

    /** Список оборудования */
    Route::group(['prefix' => 'device'], function () {        
        Route::get('/', 'PagesAdmin@device')->name('admindevice');
    });  
    
});

/** Страницы монтажа */
Route::group(['prefix' => 'montage'], function () {
    /** Главная страница админки монтажа */
    Route::get('/', 'Pages@montage')->name('montage');
    /** Вывод картинки акта с наложенным текстом данных */
    Route::get('/act{id}', 'Pages@createJpegAct');
    Route::get('/parce', 'Montage\Montage@ParceData');
});
Route::get('/montage{id}', 'Pages@montage')->name('montagePage');


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