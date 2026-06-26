<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $path = $request->file('file')->store('uploads/news', 'public');
        $baseUrl = rtrim($request->getSchemeAndHttpHost(), '/');
        $url = $baseUrl.'/storage/'.$path;

        return $this->success(['url' => $url], '上传成功');
    }
}
