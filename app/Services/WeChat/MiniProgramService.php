<?php

namespace App\Services\WeChat;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 微信小程序：登录 code / 手机号授权 code 换取信息。
 * 本地无 AppSecret 时可开启 mock，便于开发联调。
 */
class MiniProgramService
{
    public function isMock(): bool
    {
        // 生产环境一律禁止 mock，避免误开导致假手机号登录
        if (app()->environment('production')) {
            return false;
        }

        if ((bool) config('wechat.mini.mock', false)) {
            return true;
        }

        $secret = (string) config('wechat.mini.secret', '');

        return $secret === '' && app()->environment('local');
    }

    /**
     * @return array{openid: string, session_key?: string, unionid?: string|null}
     */
    public function codeToSession(string $loginCode): array
    {
        if ($this->isMock()) {
            return [
                'openid' => 'mock_openid_'.substr(md5($loginCode ?: 'local'), 0, 16),
                'session_key' => 'mock_session',
                'unionid' => null,
            ];
        }

        $appId = (string) config('wechat.mini.app_id', '');
        $secret = (string) config('wechat.mini.secret', '');
        if ($appId === '' || $secret === '') {
            throw new RuntimeException('未配置微信小程序 AppID/Secret');
        }

        $response = Http::get('https://api.weixin.qq.com/sns/jscode2session', [
            'appid' => $appId,
            'secret' => $secret,
            'js_code' => $loginCode,
            'grant_type' => 'authorization_code',
        ]);

        $data = $response->json() ?: [];
        if (! empty($data['errcode'])) {
            Log::warning('wechat code2session failed', $data);
            throw new RuntimeException($data['errmsg'] ?? '微信登录失败');
        }

        if (empty($data['openid'])) {
            throw new RuntimeException('微信登录失败：未返回 openid');
        }

        return [
            'openid' => (string) $data['openid'],
            'session_key' => (string) ($data['session_key'] ?? ''),
            'unionid' => $data['unionid'] ?? null,
        ];
    }

    /**
     * 用 getPhoneNumber 返回的 code 换取手机号。
     * mock 模式下：phone_code 形如 MOCK:13900000001
     */
    public function phoneFromCode(string $phoneCode): string
    {
        if ($this->isMock()) {
            if (preg_match('/^MOCK:(\d{11})$/', trim($phoneCode), $m)) {
                return $m[1];
            }

            // 开发默认：未传具体手机号时使用演示访客号
            return (string) config('wechat.mini.mock_phone', '13900000001');
        }

        $accessToken = $this->getAccessToken();
        $response = Http::post(
            'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token='.$accessToken,
            ['code' => $phoneCode]
        );

        $data = $response->json() ?: [];
        if (! empty($data['errcode']) && (int) $data['errcode'] !== 0) {
            Log::warning('wechat getuserphonenumber failed', $data);
            throw new RuntimeException($data['errmsg'] ?? '获取手机号失败');
        }

        $phone = $data['phone_info']['purePhoneNumber']
            ?? $data['phone_info']['phoneNumber']
            ?? null;

        if (! $phone) {
            throw new RuntimeException('获取手机号失败');
        }

        return preg_replace('/\D+/', '', (string) $phone) ?: (string) $phone;
    }

    private function getAccessToken(): string
    {
        $appId = (string) config('wechat.mini.app_id', '');
        $secret = (string) config('wechat.mini.secret', '');
        if ($appId === '' || $secret === '') {
            throw new RuntimeException('未配置微信小程序 AppID/Secret');
        }

        $cacheKey = 'wechat_mini_access_token';
        $cached = cache()->get($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $response = Http::get('https://api.weixin.qq.com/cgi-bin/token', [
            'grant_type' => 'client_credential',
            'appid' => $appId,
            'secret' => $secret,
        ]);

        $data = $response->json() ?: [];
        if (empty($data['access_token'])) {
            Log::warning('wechat access_token failed', $data);
            throw new RuntimeException($data['errmsg'] ?? '获取微信 access_token 失败');
        }

        $ttl = max(60, ((int) ($data['expires_in'] ?? 7200)) - 200);
        cache()->put($cacheKey, $data['access_token'], $ttl);

        return (string) $data['access_token'];
    }
}
