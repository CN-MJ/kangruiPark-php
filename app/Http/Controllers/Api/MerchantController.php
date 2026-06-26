<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    use ApiResponse;

    /**
     * 获取园区商户列表（访客预约选公司）
     */
    public function index(): JsonResponse
    {
        $merchants = Merchant::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'location']);

        return $this->success($merchants);
    }
}
