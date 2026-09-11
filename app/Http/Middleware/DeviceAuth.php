<?php

namespace App\Http\Middleware;

use App\Models\ParkingDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('parking.hardware.enabled', true)) {
            return response()->json([
                'code' => 403,
                'msg' => '硬件对接功能未启用',
                'data' => null,
            ], 403);
        }

        $headerName = config('parking.hardware.api_key_header', 'X-API-Key');
        $apiKey = $request->header($headerName);

        if (! $apiKey) {
            return response()->json([
                'code' => 401,
                'msg' => '缺少设备鉴权密钥',
                'data' => null,
            ], 401);
        }

        $device = ParkingDevice::query()
            ->where('api_key', $apiKey)
            ->where('status', ParkingDevice::STATUS_ACTIVE)
            ->first();

        if (! $device) {
            return response()->json([
                'code' => 401,
                'msg' => '设备鉴权失败',
                'data' => null,
            ], 401);
        }

        $device->update(['last_heartbeat_at' => now()]);
        $request->attributes->set('parking_device', $device);

        return $next($request);
    }
}
