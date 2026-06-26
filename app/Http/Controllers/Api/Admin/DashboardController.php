<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Appointment;
use App\Models\News;
use App\Models\ParkingRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $totalSpots = (int) config('app.total_parking_spots', 100);
        $occupied = ParkingRecord::where('status', ParkingRecord::STATUS_IN_PARK)->count();

        return $this->success([
            'appointments_total' => Appointment::count(),
            'appointments_pending' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            'appointments_confirmed' => Appointment::where('status', Appointment::STATUS_CONFIRMED)->count(),
            'appointments_active' => Appointment::where('status', Appointment::STATUS_PENDING)->count(),
            'appointments_today' => Appointment::query()
                ->where('status', Appointment::STATUS_PENDING)
                ->whereDate('appointment_time', today())
                ->count(),
            'parking_occupied' => $occupied,
            'parking_total' => $totalSpots,
            'parking_remaining' => max(0, $totalSpots - $occupied),
            'news_total' => News::count(),
            'merchants_count' => User::where('role', User::ROLE_MERCHANT)->count(),
            'visitors_count' => User::where('role', User::ROLE_VISITOR)->count(),
            'today_checkins' => User::query()
                ->where('role', User::ROLE_MERCHANT)
                ->whereDate('checked_in_at', today())
                ->count(),
        ]);
    }
}
