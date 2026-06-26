<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\InvestmentIntentController;
use App\Http\Controllers\Api\MerchantAppointmentController;
use App\Http\Controllers\Api\MerchantAuthController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 康瑞园区微信小程序 API 路由，统一前缀 /api
|
*/

// 预约模块
Route::prefix('appointments')->group(function () {
    Route::get('/', [AppointmentController::class, 'index']);
    Route::post('/', [AppointmentController::class, 'store']);
    Route::patch('/{id}/cancel', [AppointmentController::class, 'cancel']);
});

// 园区商户（访客预约选公司）
Route::get('/merchants', [MerchantController::class, 'index']);
Route::post('/investment/intent', [InvestmentIntentController::class, 'store']);
Route::get('/houses', [HouseController::class, 'index']);
Route::get('/houses/{id}', [HouseController::class, 'show']);
Route::get('/policies', [PolicyController::class, 'index']);
Route::get('/policies/{id}', [PolicyController::class, 'show']);
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/activities/{id}', [ActivityController::class, 'show']);
Route::post('/activities/{id}/register', [ActivityController::class, 'register']);

// 停车模块
Route::prefix('parking')->group(function () {
    Route::get('/remaining', [ParkingController::class, 'remaining']);
    Route::get('/active', [ParkingController::class, 'active']);
    Route::post('/entry', [ParkingController::class, 'entry']);
    Route::post('/exit', [ParkingController::class, 'exitByPlate']);
    Route::patch('/{id}/exit', [ParkingController::class, 'exit']);
});

// 新闻模块
Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('/{id}', [NewsController::class, 'show']);
});

// 用户角色：访客 / 商户 / 园区
Route::post('/users/sync', [UserController::class, 'sync']);
Route::post('/users/checkin', [UserController::class, 'checkin']);
Route::post('/park/login', [UserController::class, 'parkLogin']);
Route::post('/merchant/login', [MerchantAuthController::class, 'login']);

Route::prefix('merchant')->middleware('merchant.auth')->group(function () {
    Route::get('/me', [MerchantAuthController::class, 'me']);
    Route::post('/logout', [MerchantAuthController::class, 'logout']);
    Route::get('/appointments', [MerchantAppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/confirm', [MerchantAppointmentController::class, 'confirm']);
    Route::patch('/appointments/{id}/reject', [MerchantAppointmentController::class, 'reject']);
});

// 园区工作台（需园区人员 Token）
Route::prefix('park')->middleware('park.auth')->group(function () {
    Route::post('/logout', [UserController::class, 'parkLogout']);
    Route::get('/me', [UserController::class, 'parkMe']);
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);

    Route::get('/appointments', [\App\Http\Controllers\Api\Admin\AppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/cancel', [\App\Http\Controllers\Api\Admin\AppointmentController::class, 'cancel']);
    Route::patch('/appointments/{id}/confirm', [\App\Http\Controllers\Api\Admin\AppointmentActionController::class, 'confirm']);
    Route::patch('/appointments/{id}/reject', [\App\Http\Controllers\Api\Admin\AppointmentActionController::class, 'reject']);
    Route::get('/houses', [\App\Http\Controllers\Api\Admin\HouseController::class, 'index']);
    Route::post('/houses', [\App\Http\Controllers\Api\Admin\HouseController::class, 'store']);
    Route::get('/houses/{id}', [\App\Http\Controllers\Api\Admin\HouseController::class, 'show']);
    Route::put('/houses/{id}', [\App\Http\Controllers\Api\Admin\HouseController::class, 'update']);
    Route::delete('/houses/{id}', [\App\Http\Controllers\Api\Admin\HouseController::class, 'destroy']);
    Route::get('/investments', [\App\Http\Controllers\Api\Admin\InvestmentController::class, 'index']);
    Route::get('/investments/{id}', [\App\Http\Controllers\Api\Admin\InvestmentController::class, 'show']);
    Route::patch('/investments/{id}/status', [\App\Http\Controllers\Api\Admin\InvestmentController::class, 'updateStatus']);
    Route::get('/policies', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'index']);
    Route::post('/policies', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'store']);
    Route::get('/policies/{id}', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'show']);
    Route::put('/policies/{id}', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'update']);
    Route::delete('/policies/{id}', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'destroy']);
    Route::get('/activities', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'index']);
    Route::post('/activities', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'store']);
    Route::get('/activities/{id}', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'show']);
    Route::put('/activities/{id}', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'update']);
    Route::delete('/activities/{id}', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'destroy']);

    Route::get('/parking/records', [\App\Http\Controllers\Api\Admin\ParkingController::class, 'index']);
    Route::patch('/parking/{id}/exit', [\App\Http\Controllers\Api\Admin\ParkingController::class, 'exit']);

    Route::get('/news', [\App\Http\Controllers\Api\Admin\NewsController::class, 'index']);
    Route::get('/news/{id}', [\App\Http\Controllers\Api\Admin\NewsController::class, 'show']);
    Route::post('/news', [\App\Http\Controllers\Api\Admin\NewsController::class, 'store']);
    Route::post('/upload', [\App\Http\Controllers\Api\Admin\UploadController::class, 'store']);
    Route::put('/news/{id}', [\App\Http\Controllers\Api\Admin\NewsController::class, 'update']);
    Route::delete('/news/{id}', [\App\Http\Controllers\Api\Admin\NewsController::class, 'destroy']);
    Route::patch('/news/{id}/top', [\App\Http\Controllers\Api\Admin\NewsController::class, 'toggleTop']);
});
