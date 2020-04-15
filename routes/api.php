<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/** Авторизация пользователя */
Route::match(['get','post'], "/login", "Auth\User@login");
Route::match(['get','post'], "/logout", "Auth\User@logout");

/** Проверка токена */
Route::match(['get','post'], "/checkToken", "Auth\User@checkToken");

/** Подача новой заявки */
Route::match(['get','post'], '/addNewApplication', 'Service\Application@addNewApplication');
/** Загрузка фото в момент подачи заявки */
Route::match(['get','post','file'], '/uploadImagesAddApplication', 'Service\Application@uploadImagesAddApplication');
/** Полные данные одной заявки */
Route::match(['get','post'], '/getOneApplicationData', 'Service\Application@getOneApplicationData');
/** Удаление файла */
Route::match(['get','post'], '/deleteFile', 'Service\Application@deleteFile');


/** Запросы авторизированных пользователей */
Route::group([
    'prefix' => 'token{token}',
    'middleware' => 'CheckToken'
], function () {

    /** Работа с заявками */
    Route::group(['prefix' => 'service'], function () {

        /** Список заявок по фильтрам */
        Route::match(['get','post'], '/getApplicationsList', 'Service\Application@getApplicationsList');
        /** Отправка комментария по заявке */
        Route::match(['get','post'], '/sendApplicationComment', 'Service\Application@sendApplicationComment');
        /** Удаление заявки */
        Route::match(['get','post'], '/applicationDelete', 'Service\Application@applicationDelete');
        
        /** Список вариантов присоединения заявки */
        Route::match(['get','post'], '/applicationCombineOpen', 'Service\Application@applicationCombineOpen');
        /** Сохранение выбранной к присоединению мастер-заявки */
        Route::match(['get','post'], '/applicationCombine', 'Service\Application@applicationCombine');
        
        /** Начало процесса завешения заявки */
        Route::match(['get','post'], '/doneApplicationStart', 'Service\Application@doneApplicationStart');
        /** Загрузка файла при завершении заявки */
        Route::post('/uploadFileForDone', 'Service\Application@uploadFileForDone');

        /** Завршение заявки */
        Route::match(['get','post'], '/applicationDone', 'Service\Application@applicationDone');

        /** Поиск коллег */
        Route::match(['get','post'], '/searchCollegue', 'Service\Application@searchCollegue');
        /** Добавление/Удаление коллеги из избранного */
        Route::match(['get','post'], '/userFavorit', 'Admin\Users@userFavorit');

        /** Лента сервиса */
        Route::match(['get','post'], '/getWorkTape', 'Service\Service@getWorkTape');


    });


    /** Админ панель */
    Route::group(['prefix' => 'admin'], function () {

        /** Главная страница списка пользователей */
        Route::match(['get','post'], '/getUsersList', 'Admin\Users@getUsersList');
        /** Данные для нового пользователя */
        Route::match(['get','post'], '/getDataForUser', 'Admin\Users@getDataForUser');
        /** Сохранение данных пользователя */
        Route::match(['get','post'], '/saveUser', 'Admin\Users@saveUser');
        /** Блокировка/Разблокировка сотрудника */
        Route::match(['get','post'], '/userBan', 'Admin\Users@userBan');
        /** Список индивидуальных прав сотрудника */
        Route::match(['get','post'], '/userGetAccess', 'Admin\Users@userGetAccess');
        /** Сохранение индивидуальных прав сотрудника */
        Route::match(['get','post'], '/saveUserAccess', 'Admin\Users@saveUserAccess');

        /** Главная страница списка групп пользователей */
        Route::match(['get','post'], '/getUsersGroupsList', 'Admin\Users@getUsersGroupsList');
        /** Окно редактирвоания основных данных группы */
        Route::match(['get','post'], '/getDataForUsersGroups', 'Admin\Users@getDataForUsersGroups');
        /** Сохранение группы пользователей */
        Route::match(['get','post'], '/saveGroup', 'Admin\Users@saveGroup');
        /** Список данных доступа группы */
        Route::match(['get','post'], '/usersGroupGetAccess', 'Admin\Users@usersGroupGetAccess');
        /** Сохранение прав доступа по группе */
        Route::match(['get','post'], '/saveGroupAccess', 'Admin\Users@saveGroupAccess');
        
        /** Создание нового заказчика */
        Route::match(['get','post'], '/saveNewProject', 'Admin\Projects@saveNewProject');
        /** Страница настройки проектов */
        Route::match(['get','post'], '/getProjectsList', 'Admin\Projects@getProjectsList');
        /** Данные проекта */
        Route::match(['get','post'], '/getProjectsData', 'Admin\Projects@getProjectsData');
        /** Сохранение настроек данных заказчика */
        Route::match(['get','post'], '/saveSettingsProject', 'Admin\Projects@saveSettingsProject');
        /** Сохранение пункта неисправности */
        Route::match(['get','post'], '/savePointBreak', 'Admin\Projects@savePointBreak');
        /** Удаление возврат пункта неисправностей */
        Route::match(['get','post'], '/removeBreakPoint', 'Admin\Projects@removeBreakPoint');
        /** Сохранение пункта ремонта */
        Route::match(['get','post'], '/savePointRepair', 'Admin\Projects@savePointRepair');
        /** Удаление возврат пункта ремонта */
        Route::match(['get','post'], '/removeRepairPoint', 'Admin\Projects@removeRepairPoint');
        /** Удаление возврат подпункта ремонта */
        Route::match(['get','post'], '/subPointRepairShow', 'Admin\Projects@subPointRepairShow');










        

        /** Главная страница админки */
        Route::match(['get','post'], '/getStatistic', 'Admin\Admin@getStatistic');

        /** Обработка монтажа */
        Route::group(['prefix' => 'montage'], function () {

            Route::match(['get','post'], '/', 'Admin\Montage@main');
            /** Список разделов */
            Route::match(['get','post'], '/getList', 'Admin\Montage@getList');
            /** Новый раздел монтажа */
            Route::match(['get','post'], '/newMontage', 'Admin\Montage@newMontage');
            /** Список страниц завершения монтажа */
            Route::match(['get','post'], '/getPageListMontage', 'Admin\Montage@getPageListMontage');
            /** Создание окна завершения монтажа */
            Route::match(['get','post'], '/newPageMontageRazdel', 'Admin\Montage@newPageMontageRazdel');
            /** Открытие окна настройки модалки ввода данных */
            Route::match(['get','post'], '/openDataPage', 'Admin\Montage@openDataPage');
            /** Сохранение данных поля ввода */
            Route::match(['get','post'], '/saveInput', 'Admin\Montage@saveInput');
            /** Сортировка полей ввода */
            Route::match(['get','post'], '/sortInput', 'Admin\Montage@sortInput');
            /** Получение данных одного поля ввода */
            Route::match(['get','post'], '/getInput', 'Admin\Montage@getInput');
            /** Получение данных одного поля ввода */
            Route::match(['get','post'], '/deletePage', 'Admin\Montage@deletePage');

            /** Загрузка файла со списком задач */
            Route::post('/uploadeFileTask', 'Admin\MontageTask@uploadeFileTask');
            /** Добавление задач в БД */
            Route::match(['get','post'], '/addTasks', 'Admin\MontageTask@addTasks');

            /** Удаление единичной задачи */
            Route::match(['get','post'], '/deleteTask', 'Admin\MontageTask@deleteTask');
            /** Восстановление единичной задачи */
            Route::match(['get','post'], '/returnTask', 'Admin\MontageTask@returnTask');

            /** Открытие данных монтажа для редактирования */
            Route::match(['get','post'], '/editTaskData', 'Admin\MontageTask@editTaskData');
            /** Сохранение данных задачи монтажа после редактирвоания */
            Route::match(['get','post'], '/editTaskDataSave', 'Admin\MontageTask@editTaskDataSave');

        });
        
        
    });
    
});