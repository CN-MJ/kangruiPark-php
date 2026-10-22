<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\ImageCompressService;
use App\Support\MediaUrl;
use App\Support\PublicStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ImageCompressService $imageCompress,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $purpose = $request->input('purpose', 'news');
        $isEnterprise = $purpose === 'enterprise';
        $isServiceTeam = $purpose === 'service_team';

        // 允许上传稍大，服务端再压缩到硬上限
        $uploadMaxKb = 8192;
        $hardMaxBytes = $isEnterprise ? 2_097_152 : 4_194_304;
        $softBytes = $isEnterprise ? 1_048_576 : 1_572_864;
        $folder = $isEnterprise
            ? 'uploads/enterprises'
            : ($isServiceTeam ? 'uploads/service-teams' : 'uploads/news');

        $request->validate([
            'file' => "required|image|mimes:jpeg,png,jpg,webp|max:{$uploadMaxKb}",
            'purpose' => 'nullable|string|in:news,enterprise,service_team',
        ]);

        $stored = $request->file('file')->store($folder, 'public');
        $absolute = Storage::disk('public')->path($stored);

        $result = $this->imageCompress->compressIfNeeded(
            $absolute,
            $hardMaxBytes,
            $softBytes,
            $isEnterprise ? 1920 : 2000,
        );

        if ($result['final_size'] > $hardMaxBytes) {
            foreach (array_unique([$result['path'], $absolute]) as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            return $this->error('图片过大，压缩后仍超过限制，请换一张更小的图', 422);
        }

        $relative = $this->toPublicRelative($result['path']) ?: $stored;
        $relative = MediaUrl::toRelative($relative) ?: $relative;
        // public/storage 若是普通目录（非软链），把文件同步到网站可读路径
        PublicStorage::ensureWebVisible($relative);
        $url = MediaUrl::absolute($relative, $request);

        return $this->success([
            'path' => $relative,
            'url' => $url,
            'compressed' => $result['compressed'],
            'original_size' => $result['original_size'],
            'final_size' => $result['final_size'],
        ], $result['compressed'] ? '上传成功（已自动压缩）' : '上传成功');
    }

    private function toPublicRelative(string $absolutePath): ?string
    {
        $root = str_replace('\\', '/', Storage::disk('public')->path(''));
        $root = rtrim($root, '/').'/';
        $abs = str_replace('\\', '/', $absolutePath);
        if (! str_starts_with($abs, $root)) {
            return null;
        }

        return ltrim(substr($abs, strlen($root)), '/');
    }
}
