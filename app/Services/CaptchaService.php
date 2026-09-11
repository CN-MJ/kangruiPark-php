<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * 图形验证码（园区 / 商户 / 超管二次验证）
 * 优先 GD；无 GD 时回退 SVG，避免环境缺扩展导致登录不可用。
 */
class CaptchaService
{
    public function issue(): array
    {
        $key = Str::random(32);
        $code = $this->randomCode(4);
        $ttl = max(60, (int) config('auth_security.captcha.ttl_seconds', 300));

        Cache::put($this->cacheKey($key), strtolower($code), $ttl);

        return [
            'captcha_key' => $key,
            'captcha_image' => function_exists('imagecreatetruecolor')
                ? $this->renderBase64Png($code)
                : $this->renderBase64Svg($code),
            'expires_in' => $ttl,
        ];
    }

    public function verify(?string $key, ?string $code, bool $consume = true): bool
    {
        if (! $key || ! $code) {
            return false;
        }

        $cacheKey = $this->cacheKey($key);
        $expected = Cache::get($cacheKey);
        if (! is_string($expected) || $expected === '') {
            return false;
        }

        $ok = hash_equals($expected, strtolower(trim($code)));
        if ($consume || $ok) {
            Cache::forget($cacheKey);
        }

        return $ok;
    }

    private function cacheKey(string $key): string
    {
        return 'auth_captcha:'.$key;
    }

    private function randomCode(int $length): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $out;
    }

    private function renderBase64Png(string $code): string
    {
        $width = 140;
        $height = 48;
        $image = imagecreatetruecolor($width, $height);
        $bg = imagecolorallocate($image, 243, 246, 252);
        $fg = imagecolorallocate($image, 0, 55, 145);
        $noise = imagecolorallocate($image, 168, 192, 239);
        imagefilledrectangle($image, 0, 0, $width, $height, $bg);

        for ($i = 0; $i < 28; $i++) {
            imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $noise);
        }
        for ($i = 0; $i < 4; $i++) {
            imageline(
                $image,
                random_int(0, (int) ($width / 2)),
                random_int(0, $height - 1),
                random_int((int) ($width / 2), $width - 1),
                random_int(0, $height - 1),
                $noise
            );
        }

        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $x = 18 + $i * 28 + random_int(-2, 2);
            $y = random_int(14, 22);
            imagestring($image, 5, $x, $y, $code[$i], $fg);
        }

        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($png ?: '');
    }

    private function renderBase64Svg(string $code): string
    {
        $chars = str_split($code);
        $texts = '';
        foreach ($chars as $i => $ch) {
            $x = 22 + $i * 28;
            $y = 30 + (($i % 2) * 2);
            $texts .= '<text x="'.$x.'" y="'.$y.'" font-size="22" font-family="monospace" fill="#003791">'.htmlspecialchars($ch).'</text>';
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="140" height="48">'
            .'<rect width="100%" height="100%" fill="#f3f6fc"/>'
            .'<line x1="10" y1="12" x2="130" y2="36" stroke="#a8c0ef" stroke-width="2"/>'
            .'<line x1="20" y1="40" x2="120" y2="8" stroke="#a8c0ef" stroke-width="2"/>'
            .$texts
            .'</svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
