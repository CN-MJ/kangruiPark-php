<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\BuildingHouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HouseController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = BuildingHouse::query()
            ->withCount('intents')
            ->orderByRaw('CAST(building_no AS UNSIGNED) ASC')
            ->orderBy('building_no')
            ->orderBy('floor')
            ->orderBy('room_no')
            ->orderByDesc('id');

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', (int) $request->input('status'));
        }

        $leaseStatus = $request->input('lease_sale_status');
        if ($leaseStatus && $leaseStatus !== 'all') {
            if (! in_array($leaseStatus, BuildingHouse::LEASE_SALE_STATUSES, true)) {
                return $this->error('租售状态无效', 422);
            }
            $query->where('lease_sale_status', $leaseStatus);
        }

        if ($request->filled('building_no')) {
            $query->where('building_no', trim((string) $request->input('building_no')));
        }

        $page = $query->paginate(20);
        $page->setCollection(
            $page->getCollection()->map(fn (BuildingHouse $h) => $this->format($h))
        );

        return $this->success($page);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateData($request);
        $house = BuildingHouse::create($validated);

        return $this->success($this->format($house->fresh()), '创建成功');
    }

    public function show(int $id): JsonResponse
    {
        $house = BuildingHouse::query()
            ->withCount('intents')
            ->find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }

        return $this->success($this->format($house));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $house = BuildingHouse::find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }

        $house->update($this->validateData($request));

        return $this->success($this->format($house->fresh()->loadCount('intents')), '更新成功');
    }

    public function destroy(int $id): JsonResponse
    {
        $house = BuildingHouse::find($id);
        if (! $house) {
            return $this->error('房源不存在', 404);
        }
        $house->delete();

        return $this->success(null, '删除成功');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'building_no' => 'required|string|max:20',
            'floor' => 'required|integer|min:-20|max:200',
            'floor_height' => 'nullable|numeric|min:0|max:99.99',
            'room_no' => 'required|string|max:30',
            'building_area' => 'required|numeric|min:0.01|max:999999.99',
            'lease_sale_status' => ['required', 'string', Rule::in(BuildingHouse::LEASE_SALE_STATUSES)],
            'status' => 'nullable|integer|in:0,1',
            'sort_order' => 'nullable|integer|min:0',
            // 兼容旧客户端，可选
            'price' => 'nullable|string|max:50',
            'house_type' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'images' => 'nullable|array',
            'title' => 'nullable|string|max:120',
            'area' => 'nullable|string|max:50',
        ]);

        $data['building_no'] = trim((string) $data['building_no']);
        $data['building_no'] = preg_replace('/栋$/u', '', $data['building_no']) ?: $data['building_no'];
        $data['room_no'] = trim((string) $data['room_no']);
        $data['status'] = (int) ($data['status'] ?? BuildingHouse::STATUS_ON_MARKET);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['price'] = (string) ($data['price'] ?? '');
        $data['house_type'] = (string) ($data['house_type'] ?? '');
        $data['description'] = (string) ($data['description'] ?? '');
        $data['images'] = $data['images'] ?? [];

        return BuildingHouse::syncDerivedFields($data);
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
            'area' => $h->area,
            'price' => $h->price,
            'house_type' => $h->house_type,
            'description' => $h->description,
            'images' => $h->images ?? [],
            'status' => $h->status,
            'sort_order' => $h->sort_order,
            'intents_count' => (int) ($h->intents_count ?? 0),
        ];
    }
}
