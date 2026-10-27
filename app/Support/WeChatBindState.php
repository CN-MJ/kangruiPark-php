<?php

namespace App\Support;

/**
 * 服务号 OAuth state 透传：
 * 明文：type:visitor,phone:138... / type:merchant,id:1
 * 发给微信时用 url-safe base64，避免特殊字符被截断。
 */
class WeChatBindState
{
    public const TYPE_VISITOR = 'visitor';

    public const TYPE_MERCHANT = 'merchant';

    /**
     * @return array{type: string, phone?: string, merchant_id?: int}
     */
    public static function parse(?string $state): array
    {
        $state = trim((string) $state);
        $out = ['type' => ''];

        if ($state === '') {
            return $out;
        }

        // url-safe base64（发给微信的格式）
        if (! str_contains($state, ':')) {
            $padded = strtr($state, '-_', '+/');
            $padLen = strlen($padded) % 4;
            if ($padLen > 0) {
                $padded .= str_repeat('=', 4 - $padLen);
            }
            $decoded = base64_decode($padded, true);
            if (is_string($decoded) && $decoded !== '') {
                $state = $decoded;
            }
        }

        // 兼容旧版 base64 JSON
        if (! str_contains($state, 'type:') || str_starts_with($state, '{') || str_starts_with($state, 'ey')) {
            $json = json_decode($state, true);
            if (! is_array($json)) {
                $json = json_decode((string) base64_decode($state, true), true);
            }
            if (is_array($json)) {
                $type = (string) ($json['type'] ?? '');
                if ($type === 'user') {
                    $type = self::TYPE_VISITOR;
                }
                $out['type'] = $type;
                if (! empty($json['phone'])) {
                    $out['phone'] = trim((string) $json['phone']);
                }
                if (! empty($json['merchant_id'])) {
                    $out['merchant_id'] = (int) $json['merchant_id'];
                }
                if (! empty($json['id']) && empty($out['merchant_id'])) {
                    $out['merchant_id'] = (int) $json['id'];
                }

                return $out;
            }
        }

        foreach (explode(',', $state) as $segment) {
            $segment = trim($segment);
            if ($segment === '' || ! str_contains($segment, ':')) {
                continue;
            }
            [$key, $value] = explode(':', $segment, 2);
            $key = strtolower(trim($key));
            $value = trim($value);

            if ($key === 'type') {
                $out['type'] = $value === 'user' ? self::TYPE_VISITOR : $value;
            } elseif ($key === 'phone') {
                $out['phone'] = $value;
            } elseif ($key === 'id' || $key === 'merchant_id') {
                $out['merchant_id'] = (int) $value;
            }
        }

        return $out;
    }

    public static function forVisitor(string $phone): string
    {
        $phone = trim($phone);

        return 'type:'.self::TYPE_VISITOR.',phone:'.$phone;
    }

    public static function forMerchant(int $merchantId): string
    {
        return 'type:'.self::TYPE_MERCHANT.',id:'.$merchantId;
    }

    /** 写入微信 OAuth 的 state（url-safe base64） */
    public static function forOauth(string $plainState): string
    {
        return rtrim(strtr(base64_encode($plainState), '+/', '-_'), '=');
    }
}
