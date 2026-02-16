<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use Webman\Route;

// Новый роут для тестового метода
Route::get('/test', [app\controller\IndexController::class, 'test']);
Route::get('/', [app\controller\IndexController::class, 'index']);
Route::get('/view', [app\controller\IndexController::class, 'view']);
Route::get('/json', [app\controller\IndexController::class, 'json']);

// Роуты для блога
Route::get('/blog', [app\controller\BlogController::class, 'index']);
Route::get('/blog/create', [app\controller\BlogController::class, 'create']);
Route::post('/blog', [app\controller\BlogController::class, 'store']);
Route::get('/blog/{id}', [app\controller\BlogController::class, 'show']);

// Роуты для HRM системы
Route::get('/hrm', [app\controller\HRMController::class, 'index']);

// Управление сотрудниками
Route::get('/hrm/employees', [app\controller\HRMController::class, 'employees']);
Route::get('/hrm/employees/create', [app\controller\HRMController::class, 'createEmployee']);
Route::post('/hrm/employees', [app\controller\HRMController::class, 'storeEmployee']);
Route::get('/hrm/employees/{id}', [app\controller\HRMController::class, 'showEmployee']);

// Управление договорами
Route::get('/hrm/contracts', [app\controller\HRMController::class, 'contracts']);
Route::get('/hrm/contracts/create', [app\controller\HRMController::class, 'createContract']);
Route::post('/hrm/contracts', [app\controller\HRMController::class, 'storeContract']);

// Управление отпусками
Route::get('/hrm/leaves', [app\controller\HRMController::class, 'leaves']);
Route::get('/hrm/leaves/create', [app\controller\HRMController::class, 'createLeave']);
Route::post('/hrm/leaves', [app\controller\HRMController::class, 'storeLeave']);
Route::post('/hrm/leaves/{id}/status', [app\controller\HRMController::class, 'updateLeaveStatus']);

// Расчет зарплаты
Route::get('/hrm/payroll', [app\controller\HRMController::class, 'payroll']);

// Учет времени
Route::get('/hrm/time-tracking', [app\controller\HRMController::class, 'timeTracking']);

// Отчеты
Route::get('/hrm/reports', [app\controller\HRMController::class, 'reports']);





