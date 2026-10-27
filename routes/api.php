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
use App\Http\Controllers\Api\MerchantServiceController;
use App\Http\Controllers\Api\EnterpriseServiceController;
use App\Http\Controllers\Api\UserShopController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\ParkingConfirmController;
use App\Http\Controllers\Api\ParkingController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\PolicyController;
use App\Http\Controllers\Api\ServiceTeamController;
use App\Http\Controllers\Api\SuperConsoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserVehicleController;
use App\Http\Controllers\Api\WeChatBindController;
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

// 微信服务号：H5 授权绑定 openid + 复查是否已关注
Route::post('/wechat/bind-openid', [WeChatBindController::class, 'bindOpenid']);
Route::post('/wechat/check-subscribe', [WeChatBindController::class, 'checkSubscribe']);

// 园区商户（访客预约选公司）
Route::get('/merchants', [MerchantController::class, 'index']);
Route::post('/investment/intent', [InvestmentIntentController::class, 'store']);
Route::get('/houses', [HouseController::class, 'index']);
Route::get('/houses/{id}', [HouseController::class, 'show']);
Route::get('/policies', [PolicyController::class, 'index']);
Route::get('/policies/{id}', [PolicyController::class, 'show']);
Route::get('/service-teams', [ServiceTeamController::class, 'index']);
Route::get('/service-teams/{id}', [ServiceTeamController::class, 'show']);
Route::get('/activities', [ActivityController::class, 'index']);
Route::get('/activities/{id}', [ActivityController::class, 'show']);
Route::post('/activities/{id}/register', [ActivityController::class, 'register']);

// 企业库（公开浏览与筛选）+ 企业服务（商品）
Route::get('/enterprise-categories', [EnterpriseCategoryController::class, 'index']);
Route::get('/enterprise-districts', [EnterpriseController::class, 'districts']);
Route::get('/enterprises', [EnterpriseController::class, 'index']);
Route::get('/enterprises/{id}', [EnterpriseController::class, 'show']);
Route::get('/enterprise-services', [EnterpriseServiceController::class, 'index']);
Route::get('/enterprise-services/{id}', [EnterpriseServiceController::class, 'show']);

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
Route::get('/users/login-options', [UserController::class, 'loginOptions']);
Route::post('/users/logout', [UserController::class, 'logout'])->middleware('user.auth');
Route::patch('/users/profile', [UserController::class, 'updateProfile'])->middleware('user.auth');
Route::post('/users/avatar', [UserController::class, 'uploadAvatar'])->middleware('user.auth');
Route::get('/users/avatar', [UserController::class, 'showAvatar'])->middleware('user.auth');
Route::post('/users/checkin', [UserController::class, 'checkin'])->middleware('user.auth');
Route::post('/users/staff-apply', [UserController::class, 'staffApply'])->middleware('user.auth');
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

    // 微信商户店铺：需已绑定企业
    Route::get('/shop/me', [UserShopController::class, 'me']);
    Route::post('/shop/upload', [UserShopController::class, 'uploadImage']);
    Route::get('/shop/services', [UserShopController::class, 'services']);
    Route::post('/shop/services', [UserShopController::class, 'storeService']);
    Route::put('/shop/services/{id}', [UserShopController::class, 'updateService']);
    Route::patch('/shop/services/{id}/status', [UserShopController::class, 'setServiceStatus']);
    Route::delete('/shop/services/{id}', [UserShopController::class, 'destroyService']);
    Route::get('/shop/appointments', [UserShopController::class, 'appointments']);
    Route::get('/shop/appointments/{id}', [UserShopController::class, 'showAppointment']);
    Route::patch('/shop/appointments/{id}/confirm', [UserShopController::class, 'confirmAppointment']);
    Route::patch('/shop/appointments/{id}/reject', [UserShopController::class, 'rejectAppointment']);
});

Route::prefix('merchant')->middleware('merchant.auth')->group(function () {
    Route::get('/me', [MerchantAuthController::class, 'me']);
    Route::post('/logout', [MerchantAuthController::class, 'logout']);
    Route::post('/change-password', [PasswordController::class, 'changeMerchantPassword']);
    Route::get('/appointments', [MerchantAppointmentController::class, 'index']);
    Route::patch('/appointments/{id}/confirm', [MerchantAppointmentController::class, 'confirm']);
    Route::patch('/appointments/{id}/reject', [MerchantAppointmentController::class, 'reject']);
    Route::post('/upload', [MerchantServiceController::class, 'uploadImage']);
    Route::get('/services', [MerchantServiceController::class, 'index']);
    Route::post('/services', [MerchantServiceController::class, 'store']);
    Route::put('/services/{id}', [MerchantServiceController::class, 'update']);
    Route::patch('/services/{id}/status', [MerchantServiceController::class, 'setStatus']);
    Route::delete('/services/{id}', [MerchantServiceController::class, 'destroy']);
});

// 园区工作台（园区 Token 或超管升权 Token）+ 轻量 RBAC
Route::prefix('park')->middleware(['park.auth', 'park.audit'])->group(function () {
    Route::post('/logout', [UserController::class, 'parkLogout']);
    Route::get('/me', [UserController::class, 'parkMe']);
    Route::post('/change-password', [PasswordController::class, 'changeParkPassword']);
    Route::get('/dashboard', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'index'])
        ->middleware('park.can:dashboard');

    Route::middleware('park.can:users')->group(function () {
        Route::get('/users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
        Route::patch('/users/{id}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
        Route::post('/users/{id}/review-staff', [\App\Http\Controllers\Api\Admin\UserController::class, 'reviewStaff']);
        Route::post('/users/{id}/approve-merchant', [\App\Http\Controllers\Api\Admin\UserController::class, 'approveMerchant']);
        Route::post('/users/{id}/reject-merchant', [\App\Http\Controllers\Api\Admin\UserController::class, 'rejectMerchant']);
    });

    Route::middleware('park.can:roles')->group(function () {
        Route::get('/roles', [\App\Http\Controllers\Api\Admin\UserController::class, 'roles']);
        Route::post('/roles', [\App\Http\Controllers\Api\Admin\UserController::class, 'storeRole']);
        Route::put('/roles/{key}', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateRole']);
        Route::delete('/roles/{key}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroyRole']);
    });

    Route::get('/enterprise-categories', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'index'])
        ->middleware('park.can:categories_read,categories');
    Route::middleware('park.can:categories')->group(function () {
        Route::post('/enterprise-categories/sync-defaults', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'syncDefaults']);
        Route::post('/enterprise-categories', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'store']);
        Route::put('/enterprise-categories/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'update']);
        Route::delete('/enterprise-categories/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseCategoryController::class, 'destroy']);
    });

    Route::middleware('park.can:enterprises')->group(function () {
        Route::get('/enterprises', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'index']);
        Route::post('/enterprises', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'store']);
        Route::get('/enterprises/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'show']);
        Route::put('/enterprises/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'update']);
        Route::delete('/enterprises/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseController::class, 'destroy']);
        Route::get('/enterprise-services', [\App\Http\Controllers\Api\Admin\EnterpriseServiceController::class, 'index']);
        Route::post('/enterprise-services', [\App\Http\Controllers\Api\Admin\EnterpriseServiceController::class, 'store']);
        Route::put('/enterprise-services/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseServiceController::class, 'update']);
        Route::delete('/enterprise-services/{id}', [\App\Http\Controllers\Api\Admin\EnterpriseServiceController::class, 'destroy']);
        Route::get('/merchant-accounts', [\App\Http\Controllers\Api\Admin\MerchantAccountController::class, 'index']);
        Route::post('/merchant-accounts', [\App\Http\Controllers\Api\Admin\MerchantAccountController::class, 'upsert']);
        Route::put('/merchant-accounts/{id}', [\App\Http\Controllers\Api\Admin\MerchantAccountController::class, 'update']);
    });

    Route::middleware('park.can:appointments')->group(function () {
        Route::get('/appointments', [\App\Http\Controllers\Api\Admin\AppointmentController::class, 'index']);
        Route::patch('/appointments/{id}/cancel', [\App\Http\Controllers\Api\Admin\AppointmentController::class, 'cancel']);
        Route::patch('/appointments/{id}/confirm', [\App\Http\Controllers\Api\Admin\AppointmentActionController::class, 'confirm']);
        Route::patch('/appointments/{id}/reject', [\App\Http\Controllers\Api\Admin\AppointmentActionController::class, 'reject']);
    });

    Route::middleware('park.can:houses')->group(function () {
        Route::get('/houses', [\App\Http\Controllers\Api\Admin\HouseController::class, 'index']);
        Route::post('/houses', [\App\Http\Controllers\Api\Admin\HouseController::class, 'store']);
        Route::get('/houses/{id}', [\App\Http\Controllers\Api\Admin\HouseController::class, 'show']);
        Route::put('/houses/{id}', [\App\Http\Controllers\Api\Admin\HouseController::class, 'update']);
        Route::delete('/houses/{id}', [\App\Http\Controllers\Api\Admin\HouseController::class, 'destroy']);
    });

    Route::middleware('park.can:investments')->group(function () {
        Route::get('/investments', [\App\Http\Controllers\Api\Admin\InvestmentController::class, 'index']);
        Route::get('/investments/{id}', [\App\Http\Controllers\Api\Admin\InvestmentController::class, 'show']);
        Route::patch('/investments/{id}/status', [\App\Http\Controllers\Api\Admin\InvestmentController::class, 'updateStatus']);
    });

    Route::middleware('park.can:policies')->group(function () {
        Route::get('/policies', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'index']);
        Route::post('/policies', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'store']);
        Route::get('/policies/{id}', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'show']);
        Route::put('/policies/{id}', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'update']);
        Route::delete('/policies/{id}', [\App\Http\Controllers\Api\Admin\PolicyController::class, 'destroy']);
    });

    Route::middleware('park.can:service_teams')->group(function () {
        Route::get('/service-teams', [\App\Http\Controllers\Api\Admin\ServiceTeamController::class, 'index']);
        Route::post('/service-teams', [\App\Http\Controllers\Api\Admin\ServiceTeamController::class, 'store']);
        Route::get('/service-teams/{id}', [\App\Http\Controllers\Api\Admin\ServiceTeamController::class, 'show']);
        Route::put('/service-teams/{id}', [\App\Http\Controllers\Api\Admin\ServiceTeamController::class, 'update']);
        Route::delete('/service-teams/{id}', [\App\Http\Controllers\Api\Admin\ServiceTeamController::class, 'destroy']);
    });

    Route::middleware('park.can:activities')->group(function () {
        Route::get('/activities/{id}/registrations', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'registrations']);
        Route::get('/activities', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'index']);
        Route::post('/activities', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'store']);
        Route::get('/activities/{id}', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'show']);
        Route::put('/activities/{id}', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'update']);
        Route::delete('/activities/{id}', [\App\Http\Controllers\Api\Admin\ActivityController::class, 'destroy']);
    });

    Route::middleware('park.can:parking')->group(function () {
        Route::get('/parking/records', [\App\Http\Controllers\Api\Admin\ParkingController::class, 'index']);
        Route::patch('/parking/{id}/exit', [\App\Http\Controllers\Api\Admin\ParkingController::class, 'exit']);
    });

    Route::middleware('park.can:news')->group(function () {
        Route::get('/news', [\App\Http\Controllers\Api\Admin\NewsController::class, 'index']);
        Route::get('/news/{id}', [\App\Http\Controllers\Api\Admin\NewsController::class, 'show']);
        Route::post('/news', [\App\Http\Controllers\Api\Admin\NewsController::class, 'store']);
        Route::put('/news/{id}', [\App\Http\Controllers\Api\Admin\NewsController::class, 'update']);
        Route::delete('/news/{id}', [\App\Http\Controllers\Api\Admin\NewsController::class, 'destroy']);
        Route::patch('/news/{id}/top', [\App\Http\Controllers\Api\Admin\NewsController::class, 'toggleTop']);
    });

    Route::post('/upload', [\App\Http\Controllers\Api\Admin\UploadController::class, 'store'])
        ->middleware('park.can:upload');
});
