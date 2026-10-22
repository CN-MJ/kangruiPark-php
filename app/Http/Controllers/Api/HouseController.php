<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BuildingHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HouseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = BuildingHouse::query()
            ->where('status', BuildingHouse::STATUS_ON_MARKET)
            ->orderByRaw('CAST(building_no AS UNSIGNED) ASC')
            ->orderBy('building_no')
            ->orderBy('floor')
            ->orderBy('room_no')
            ->orderByDesc('id');

        $leaseStatus = $request->input('lease_sale_status', BuildingHouse::LEASE_AVAILABLE);
        if ($leaseStatus !== '' && $leaseStatus !== 'all') {
            if (! in_array($leaseStatus, BuildingHouse::LEASE_SALE_STATUSES, true)) {
                return $this->error('租售状态无效', 422);
            }
            $query->where('lease_sale_status', $leaseStatus);
        }

        if ($request->filled('building_no')) {
            $query->where('building_no', trim((string) $request->input('building_no')));
        }

        $list = $query->get()->map(fn (BuildingHouse $h) => $this->format($h))->values();

        $buildings = BuildingHouse::query()
            ->where('status', BuildingHouse::STATUS_ON_MARKET)
            ->when(
                $leaseStatus !== '' && $leaseStatus !== 'all'
                    && in_array($leaseStatus, BuildingHouse::LEASE_SALE_STATUSES, true),
                fn ($q) => $q->where('lease_sale_status', $leaseStatus)
            )
            ->whereNotNull('building_no')
            ->where('building_no', '!=', '')
            ->selectRaw('building_no, COUNT(*) as count')
            ->groupBy('building_no')
            ->orderByRaw('CAST(building_no AS UNSIGNED) ASC')
            ->orderBy('building_no')
            ->get()
            ->map(fn ($row) => [
                'building_no' => (string) $row->building_no,
                'label' => $row->building_no.'栋',
                'count' => (int) $row->count,
            ])
            ->values();

        return $this->success([
            'list' => $list,
            'buildings' => $buildings,
            'lease_sale_status' => $leaseStatus === '' ? 'all' : $leaseStatus,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $house = BuildingHouse::query()
            ->where('status', BuildingHouse::STATUS_ON_MARKET)
            ->find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }

        return $this->success($this->format($house));
    }

    private function format(BuildingHouse $h): array
    {
        $area = $h->building_area;
        $areaText = $area !== null
            ? rtrim(rtrim(number_format((float) $area, 2, '.', ''), '0'), '.').'㎡'
            : (string) ($h->area ?: '');

        return [
            'id' => $h->id,
            'title' => $h->title,
            'building_no' => $h->building_no,
            'building_label' => $h->building_no ? $h->building_no.'栋' : '',
            'floor' => $h->floor,
            'floor_label' => $h->floorLabel(),
            'floor_height' => $h->floor_height,
            'room_no' => $h->room_no,
            'building_area' => $h->building_area,
            'building_area_text' => $areaText,
            'lease_sale_status' => $h->lease_sale_status ?: BuildingHouse::LEASE_AVAILABLE,
            'lease_sale_text' => $h->leaseSaleText(),
            'lease_sale_tone' => $h->leaseSaleTone(),
            'house_type' => $h->house_type,
            'status' => $h->status,
        ];
    }
}
