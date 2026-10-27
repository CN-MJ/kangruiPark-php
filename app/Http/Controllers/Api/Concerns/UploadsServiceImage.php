<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Services\ImageCompressService;
use App\Support\MediaUrl;
use App\Support\PublicStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait UploadsServiceImage
{
    protected function storeServiceImage(Request $request, ImageCompressService $imageCompress): JsonResponse
    {
        $uploadMaxKb = 8192;
        $hardMaxBytes = 2_097_152; // 2MB
        $softBytes = 1_048_576;

        $request->validate([
            'file' => "required|image|mimes:jpeg,png,jpg,webp|max:{$uploadMaxKb}",
        ], [
            'file.required' => '请选择图片',
            'file.image' => '仅支持图片文件',
            'file.mimes' => '图片仅支持 jpg、png、webp',
            'file.max' => '图片不能超过 8MB（上传后会压缩到 2MB 内）',
        ]);

        $stored = $request->file('file')->store('uploads/enterprise-services', 'public');
        $absolute = Storage::disk('public')->path($stored);

        $result = $imageCompress->compressIfNeeded($absolute, $hardMaxBytes, $softBytes, 1920);

        if ($result['final_size'] > $hardMaxBytes) {
            foreach (array_unique([$result['path'], $absolute]) as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }

            return $this->error('图片过大，压缩后仍超过 2MB，请换一张更小的图', 422);
        }

        $relative = $this->serviceImageToPublicRelative($result['path']) ?: $stored;
        $relative = MediaUrl::toRelative($relative) ?: $relative;
        PublicStorage::ensureWebVisible($relative);
        $url = MediaUrl::absolute($relative, $request);

        return $this->success([
            'path' => $relative,
            'url' => $url,
            'compressed' => $result['compressed'],
            'final_size' => $result['final_size'],
        ], $result['compressed'] ? '上传成功（已自动压缩）' : '上传成功');
    }

    private function serviceImageToPublicRelative(string $absolutePath): ?string
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
