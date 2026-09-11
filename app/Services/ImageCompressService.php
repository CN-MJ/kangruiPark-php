<?php

namespace App\Services;

/**
 * 接近体积上限时降低画质 / 缩小边长，控制存储占用。
 */
class ImageCompressService
{
    /**
     * @param  string  $absolutePath  已保存的本地绝对路径
     * @param  int  $maxBytes  硬上限（字节）
     * @param  int  $softBytes  超过该值开始压缩
     * @param  int  $maxEdge  最长边像素上限
     * @return array{path: string, compressed: bool, original_size: int, final_size: int}
     */
    public function compressIfNeeded(
        string $absolutePath,
        int $maxBytes = 2_097_152,
        int $softBytes = 1_048_576,
        int $maxEdge = 1920,
    ): array {
        if (! is_file($absolutePath) || ! function_exists('imagecreatefromstring')) {
            $size = is_file($absolutePath) ? (int) filesize($absolutePath) : 0;

            return [
                'path' => $absolutePath,
                'compressed' => false,
                'original_size' => $size,
                'final_size' => $size,
            ];
        }

        $originalSize = (int) filesize($absolutePath);
        if ($originalSize <= $softBytes) {
            return [
                'path' => $absolutePath,
                'compressed' => false,
                'original_size' => $originalSize,
                'final_size' => $originalSize,
            ];
        }

        $binary = @file_get_contents($absolutePath);
        if ($binary === false) {
            return [
                'path' => $absolutePath,
                'compressed' => false,
                'original_size' => $originalSize,
                'final_size' => $originalSize,
            ];
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return [
                'path' => $absolutePath,
                'compressed' => false,
                'original_size' => $originalSize,
                'final_size' => $originalSize,
            ];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $edge = max($width, $height);

        // 边长过大先等比缩小
        if ($edge > $maxEdge) {
            $ratio = $maxEdge / $edge;
            $newW = max(1, (int) round($width * $ratio));
            $newH = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($newW, $newH);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
            $width = $newW;
            $height = $newH;
        }

        $targetPath = preg_replace('/\.[^.]+$/', '', $absolutePath).'.jpg';
        $qualities = [82, 72, 62, 52, 42, 32];
        $finalSize = $originalSize;
        $ok = false;

        foreach ($qualities as $quality) {
            ob_start();
            imagejpeg($image, null, $quality);
            $jpeg = ob_get_clean();
            if ($jpeg === false || $jpeg === '') {
                continue;
            }
            $finalSize = strlen($jpeg);
            if ($finalSize <= $maxBytes || $quality === end($qualities)) {
                // 写到 jpg；若原文件不是 jpg，删掉原文件
                file_put_contents($targetPath, $jpeg);
                if (realpath($targetPath) !== realpath($absolutePath) && is_file($absolutePath)) {
                    @unlink($absolutePath);
                }
                $ok = true;
                if ($finalSize <= $maxBytes) {
                    break;
                }
            }
        }

        imagedestroy($image);

        if (! $ok) {
            return [
                'path' => $absolutePath,
                'compressed' => false,
                'original_size' => $originalSize,
                'final_size' => $originalSize,
            ];
        }

        // 仍超硬上限则再缩边长一轮
        if ($finalSize > $maxBytes && is_file($targetPath)) {
            $again = $this->shrinkByEdge($targetPath, $maxBytes, (int) max(800, $maxEdge * 0.7));
            $targetPath = $again['path'];
            $finalSize = $again['final_size'];
        }

        return [
            'path' => $targetPath,
            'compressed' => true,
            'original_size' => $originalSize,
            'final_size' => is_file($targetPath) ? (int) filesize($targetPath) : $finalSize,
        ];
    }

    private function shrinkByEdge(string $absolutePath, int $maxBytes, int $maxEdge): array
    {
        $binary = @file_get_contents($absolutePath);
        $image = $binary ? @imagecreatefromstring($binary) : false;
        if ($image === false) {
            return ['path' => $absolutePath, 'final_size' => (int) filesize($absolutePath)];
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $edge = max($width, $height);
        if ($edge > $maxEdge) {
            $ratio = $maxEdge / $edge;
            $newW = max(1, (int) round($width * $ratio));
            $newH = max(1, (int) round($height * $ratio));
            $resized = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        foreach ([60, 45, 35, 25] as $quality) {
            ob_start();
            imagejpeg($image, null, $quality);
            $jpeg = ob_get_clean();
            if ($jpeg === false) {
                continue;
            }
            file_put_contents($absolutePath, $jpeg);
            if (strlen($jpeg) <= $maxBytes) {
                break;
            }
        }

        imagedestroy($image);

        return [
            'path' => $absolutePath,
            'final_size' => (int) filesize($absolutePath),
        ];
    }
}
