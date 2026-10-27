<?php

use App\Http\Controllers\Web\Admin\AuditLogController;
use App\Http\Controllers\Web\Admin\AuthController;
use App\Http\Controllers\Web\Admin\DashboardController;
use App\Http\Controllers\Web\Admin\UserController;
use App\Http\Controllers\Web\WeChatAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// 微信服务号 H5 静默授权（须配置公众平台网页授权域名）
Route::get('/wechat/auth', [WeChatAuthController::class, 'index'])->name('wechat.auth');
Route::get('/wechat/recheck', [WeChatAuthController::class, 'recheck'])->name('wechat.recheck');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');

    Route::middleware('park.web')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::put('users/{id}', [UserController::class, 'update'])->name('users.update')->whereNumber('id');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    });
});
