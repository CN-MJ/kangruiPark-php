<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\CaptchaController;
use App\Http\Controllers\Api\EnterpriseCategoryController;
use App\Http\Controllers\Api\EnterpriseController;
use App\Http\Controllers\Api\HouseController;
use App\Http\Controllers\Api\InvestmentIntentController;
use App\Http\Controllers\Api\MerchantAppointmentController;
use App\Http\Controllers\Api\MerchantAuthController;
use App\Http\Controllers\Api\MerchantController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\ParkingConfirmController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\SuperConsoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserVehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| 赣南健康城微信小程序 API 路由，统一前缀 /api
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

// 企业库（公开浏览与筛选）
Route::get('/enterprise-categories', [EnterpriseCategoryController::class, 'index']);
Route::get('/enterprises', [EnterpriseController::class, 'index']);
Route::get('/enterprises/{id}', [EnterpriseController::class, 'show']);

// 停车模块
Route::prefix('parking')->group(function () {
    Route::middleware('user.auth')->group(function () {
        Route::get('/pending-events', [ParkingConfirmController::class, 'pendingEvents']);
        Route::post('/confirm-entry', [ParkingConfirmController::class, 'confirmEntry']);
        Route::post('/confirm-exit', [ParkingConfirmController::class, 'confirmExit']);
    });

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

// 用户角色：访客走微信授权；园区/商户走账号密码+图形验证码
Route::post('/users/sync', [UserController::class, 'sync']);
Route::post('/users/wechat-phone-login', [UserController::class, 'wechatPhoneLogin']);
Route::post('/users/checkin', [UserController::class, 'checkin'])->middleware('user.auth');
Route::post('/park/login', [UserController::class, 'parkLogin']);
Route::post('/merchant/login', [MerchantAuthController::class, 'login']);
Route::get('/auth/captcha', [CaptchaController::class, 'issue']);

// 开发者升权：需先以访客身份登录（user.auth），再二次验证
Route::prefix('super')->middleware('user.auth')->group(function () {
    Route::get('/probe', [SuperConsoleController::class, 'probe']);
    Route::post('/escalate', [SuperConsoleController::class, 'escalate']);
});

Route::prefix('super')->middleware('super.auth')->group(function () {
    Route::get('/me', [SuperConsoleController::class, 'me']);
    Route::post('/logout', [SuperConsoleController::class, 'logout']);
    Route::get('/dashboard', [SuperConsoleController::class, 'dashboard']);
    Route::get('/users', [SuperConsoleController::class, 'users']);
    Route::patch('/users/{id}', [SuperConsoleController::class, 'updateUser']);
    Route::get('/audit-logs', [SuperConsoleController::class, 'auditLogs']);
});

Route::prefix('user')->middleware('user.auth')->group(function () {
    Route::get('/vehicles', [UserVehicleController::class, 'index']);
    Route::post('/vehicles', [UserVehicleController::class, 'store']);
    Route::delete('/vehicles/{id}', [UserVehicleController::class, 'destroy']);
    Route::patch('/vehicles/{id}/default', [UserVehicleController::class, 'setDefault']);
});

Route::prefix('merchant')->middleware('merchant.auth')->group(function () {
    Route::get('/me', [MerchantAuthController::class, 'me']);
    Route::post('/logout', [MerchantAuthController::class, 'logout']);
    Route::post('/change-password', [PasswordController::class, 'changeMerchantPassword']);
    Route::get('/appointments', [MerchantAppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/confirm', [MerchantAppointmentController::class, 'confirm']);
    Route::patch('/appointments/{id}/reject', [MerchantAppointmentController::class, 'reject']);
});

// 园区工作台（园区 Token 或超管升权 Token）
Route::prefix('park')->middleware(['park.auth', 'park.audit'])->group(function () {
    Route::post('/logout', [UserController::class, 'parkLogout']);
    Route::get('/me', [UserController::class, 'parkMe']);
    Route::post('/change-password', [PasswordController::class, 'changeParkPassword']);
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index']);

    Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
    Route::post('/users/{id}/approve-merchant', [\App\Http\Controllers\Api\Admin\UserController::class, 'approveMerchant']);
    Route::post('/users/{id}/reject-merchant', [\App\Http\Controllers\Api\Admin\UserController::class, 'rejectMerchant']);

    Route::get('/enterprise-categories', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'index']);
    Route::post('/enterprise-categories/sync-defaults', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'syncDefaults']);
    Route::post('/enterprise-categories', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'store']);
    Route::put('/enterprise-categories/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'update']);
    Route::delete('/enterprise-categories/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'destroy']);

    Route::get('/enterprises', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'index']);
    Route::post('/enterprises', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'store']);
    Route::get('/enterprises/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'show']);
    Route::put('/enterprises/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'update']);
    Route::delete('/enterprises/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'destroy']);

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
    Route::get('/activities/{id}/registrations', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'registrations']);
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
