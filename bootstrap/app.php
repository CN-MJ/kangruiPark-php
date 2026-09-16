<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/hardware.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'park.auth' => \App\Http\Middleware\ParkAuth::class,
            'park.can' => \App\Http\Middleware\ParkCan::class,
            'merchant.auth' => \App\Http\Middleware\MerchantAuth::class,
            'user.auth' => \App\Http\Middleware\UserAuth::class,
            'device.auth' => \App\Http\Middleware\DeviceAuth::class,
            'super.auth' => \App\Http\Middleware\SuperAuth::class,
            'park.audit' => \App\Http\Middleware\AuditSuperParkMutation::class,
            'park.web' => \App\Http\Middleware\EnsureParkWebAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'code' => 422,
                    'msg' => $e->validator->errors()->first(),
                    'data' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || str_starts_with($request->path(), 'api/')) {
                return response()->json([
                    'code' => 404,
                    'msg' => '接口不存在',
                    'data' => null,
                ], 404);
            }
        });
    })->create();
