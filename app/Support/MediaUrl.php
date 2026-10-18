<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaUrl
{
    /**
     * 把库里的相对路径或绝对 URL 统一成当前可访问的完整地址。
     */
    public static function absolute(?string $value, ?Request $request = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $base = self::publicBase($request);
        $relative = self::toRelative($value);

        if ($relative !== null) {
            return $base.'/storage/'.$relative;
        }

        // 非本站 storage 资源，原样返回
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        return $base.'/'.ltrim($value, '/');
    }

    /**
     * @param  array<int, string|null>  $values
     * @return array<int, string>
     */
    public static function absoluteList(array $values, ?Request $request = null): array
    {
        $out = [];
        foreach ($values as $value) {
            $url = self::absolute(is_string($value) ? $value : null, $request);
            if ($url) {
                $out[] = $url;
            }
        }

        return array_values($out);
    }

    /**
     * 提取 public disk 相对路径，如 uploads/enterprises/a.jpg
     */
    public static function toRelative(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace('\\', '/', $value));
        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            $path = parse_url($value, PHP_URL_PATH) ?: '';
            $value = $path;
        }

        $value = ltrim($value, '/');
        if (str_starts_with($value, 'storage/')) {
            $value = substr($value, strlen('storage/'));
        }

        if ($value === '' || str_contains($value, '..')) {
            return null;
        }

        // 仅接受上传目录，避免把无关 path 当本地文件
        if (! str_starts_with($value, 'uploads/')) {
            return null;
        }

        return $value;
    }

    public static function diskPathExists(string $diskRelativePath): bool
    {
        return Storage::disk('public')->exists($diskRelativePath);
    }

    private static function publicBase(?Request $request): string
    {
        if ($request) {
            $host = strtolower((string) $request->getHost());
            // 真机预览时应以实际请求 Host 为准；避免落到 localhost
            if ($host !== '' && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
                return self::preferHttps(rtrim($request->getSchemeAndHttpHost(), '/'), $request);
            }
        }

        $configured = rtrim((string) config('app.url'), '/');
        if ($configured !== '' && ! preg_match('#://(localhost|127\.0\.0\.1)(:|/|$)#i', $configured)) {
            return $configured;
        }

        if ($request) {
            return rtrim($request->getSchemeAndHttpHost(), '/');
        }

        return $configured;
    }

    /**
     * 宝塔里 PHP 经常只看到 http。小程序的图片组件不加载 http 图片，合法域名也只认 https。
     * 局域网 IP 仍保持 http，方便本机调试。
     */
    private static function preferHttps(string $origin, Request $request): string
    {
        if (! str_starts_with($origin, 'http://')) {
            return $origin;
        }

        $host = strtolower((string) $request->getHost());
        if ($host !== '' && filter_var($host, FILTER_VALIDATE_IP)) {
            return $origin;
        }

        return 'https://'.substr($origin, strlen('http://'));
    }
}
