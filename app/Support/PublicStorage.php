<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * 宝塔上 public/storage 经常是普通目录而不是软链。
 * Laravel 写入 storage/app/public 后，需再落到网站可读的 public/storage。
 */
class PublicStorage
{
    /**
     * 确保相对路径（如 uploads/enterprises/a.jpg）在网站目录可访问。
     */
    public static function ensureWebVisible(string $relative): void
    {
        $relative = ltrim(str_replace('\\', '/', $relative), '/');
        if ($relative === '' || str_contains($relative, '..')) {
            return;
        }

        $diskPath = Storage::disk('public')->path($relative);
        if (! is_file($diskPath)) {
            return;
        }

        $webPath = public_path('storage/'.$relative);
        $diskReal = realpath($diskPath) ?: $diskPath;
        $webReal = is_file($webPath) ? (realpath($webPath) ?: $webPath) : null;

        // 已是同一文件（软链/junction），无需复制
        if ($webReal && $webReal === $diskReal) {
            return;
        }

        $webDir = dirname($webPath);
        if (! is_dir($webDir) && ! @mkdir($webDir, 0755, true) && ! is_dir($webDir)) {
            return;
        }

        if (is_file($webPath) && filesize($webPath) === filesize($diskPath)) {
            return;
        }

        @copy($diskPath, $webPath);
    }

    /**
     * 把 storage/app/public 里缺失到网站目录的文件补齐。
     *
     * @return array{copied: int, skipped: int, missing_source: int}
     */
    public static function syncDiskToWeb(?string $subdir = null): array
    {
        $root = Storage::disk('public')->path($subdir ? trim($subdir, '/') : '');
        $stats = ['copied' => 0, 'skipped' => 0, 'missing_source' => 0];

        if (! is_dir($root)) {
            return $stats;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $rootNorm = rtrim(str_replace('\\', '/', $root), '/').'/';

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $abs = str_replace('\\', '/', $file->getPathname());
            if (! str_starts_with($abs, $rootNorm)) {
                continue;
            }
            $relative = ($subdir ? trim($subdir, '/').'/' : '').ltrim(substr($abs, strlen($rootNorm)), '/');
            $before = is_file(public_path('storage/'.$relative));
            self::ensureWebVisible($relative);
            $after = is_file(public_path('storage/'.$relative));
            if (! $before && $after) {
                $stats['copied']++;
            } elseif ($after) {
                $stats['skipped']++;
            } else {
                $stats['missing_source']++;
            }
        }

        return $stats;
    }
}
