<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Models\Appointment;
use App\Models\BuildingHouse;
use App\Models\Enterprise;
use App\Models\InvestmentIntent;
use App\Models\News;
use App\Models\ParkingRecord;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $totalSpots = (int) config('parking.total_spots', 100);
        $occupied = Schema::hasTable('parking_records')
            ? ParkingRecord::where('status', ParkingRecord::STATUS_IN_PARK)->count()
            : 0;

        $enterpriseStats = $this->enterpriseStats();

        return $this->success([
            'appointments_total' => $this->safeCount(Appointment::class),
            'appointments_pending' => $this->safeCount(Appointment::class, fn ($q) => $q->where('status', Appointment::STATUS_PENDING)),
            'appointments_confirmed' => $this->safeCount(Appointment::class, fn ($q) => $q->where('status', Appointment::STATUS_CONFIRMED)),
            'appointments_active' => $this->safeCount(Appointment::class, fn ($q) => $q->where('status', Appointment::STATUS_PENDING)),
            'appointments_today' => $this->safeCount(
                Appointment::class,
                fn ($q) => $q->where('status', Appointment::STATUS_PENDING)->whereDate('appointment_time', today())
            ),
            'parking_occupied' => $occupied,
            'parking_total' => $totalSpots,
            'parking_remaining' => max(0, $totalSpots - $occupied),
            'news_total' => $this->safeCount(News::class),
            'merchants_count' => $enterpriseStats['settled'],
            'enterprises_total' => $enterpriseStats['total'],
            'visitors_count' => $this->safeCount(User::class, fn ($q) => $q->where('role', User::ROLE_VISITOR)),
            'today_checkins' => $enterpriseStats['today'],
            'houses_total' => $this->safeCount(BuildingHouse::class),
            'houses_on_market' => $this->safeCount(BuildingHouse::class, fn ($q) => $q->where('status', BuildingHouse::STATUS_ON_MARKET)),
            'policies_total' => $this->safeCount(Policy::class),
            'policies_published' => $this->safeCount(Policy::class, fn ($q) => $q->where('is_show', true)),
            'activities_open' => $this->safeCount(Activity::class, fn ($q) => $q->where('status', Activity::STATUS_OPEN)),
            'activities_total' => $this->safeCount(Activity::class),
            'investments_pending' => $this->safeCount(InvestmentIntent::class, fn ($q) => $q->where('status', InvestmentIntent::STATUS_PENDING)),
            'investments_total' => $this->safeCount(InvestmentIntent::class),
        ]);
    }

    private function enterpriseStats(): array
    {
        if (! Schema::hasTable('enterprises')) {
            return ['total' => 0, 'settled' => 0, 'today' => 0];
        }

        try {
            return [
                'total' => Enterprise::count(),
                'settled' => Enterprise::query()
                    ->where('settle_in_park', true)
                    ->where('status', Enterprise::STATUS_ON)
                    ->count(),
                'today' => Enterprise::query()->whereDate('created_at', today())->count(),
            ];
        } catch (\Throwable $e) {
            return ['total' => 0, 'settled' => 0, 'today' => 0];
        }
    }

    private function safeCount(string $modelClass, ?callable $scope = null): int
    {
        try {
            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = new $modelClass;
            if (! Schema::hasTable($model->getTable())) {
                return 0;
            }
            $query = $modelClass::query();
            if ($scope) {
                $scope($query);
            }

            return (int) $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
