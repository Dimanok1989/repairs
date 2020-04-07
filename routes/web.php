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


/** Страница заявки */
Route::get('/id{link}', 'Pages@showApplication')->name('application');

/** Страница фильтра и поиска заявок */
Route::get('/applications', 'Pages@showApplicationsList')->name('applicatioslist');
Route::get('/applications{client}', 'Pages@showApplicationsList');

/** Части html страниц */
Route::group(['prefix' => 'parts'], function () {

    /** Страница завершения заявки */
    Route::get('/applicationdone', function() { return view('application.done'); });

});

/** Страница выбора заказчика для добавления заявки */
Route::get('/add', 'Pages@SelectForaddApplication')->name('SelectForaddApplication');

/** Страница подачи заявок */
Route::get('/{project}', 'Pages@addRequest')->name('addRequest');