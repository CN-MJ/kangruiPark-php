<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 微信服务号（公众号）：access_token、网页授权换 openid、模板消息。
 */
class WeChatService
{
    private const ACCESS_TOKEN_CACHE_KEY = 'wechat_mp.access_token';

    private const ACCESS_TOKEN_TTL = 7000;

    public function appId(): string
    {
        return (string) config('services.wechat_mp.app_id', '');
    }

    public function secret(): string
    {
        return (string) config('services.wechat_mp.secret', '');
    }

    /**
     * 获取服务号接口调用凭证（缓存 7000 秒）。
     */
    public function getAccessToken(): string
    {
        $cached = Cache::get(self::ACCESS_TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $appId = $this->appId();
        $secret = $this->secret();
        if ($appId === '' || $secret === '') {
            throw new RuntimeException('微信服务号 AppID/Secret 未配置');
        }

        try {
            $response = Http::timeout(10)->get('https://api.weixin.qq.com/cgi-bin/token', [
                'grant_type' => 'client_credential',
                'appid' => $appId,
                'secret' => $secret,
            ]);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('wechat_mp.getAccessToken.http_error', ['message' => $e->getMessage()]);
            throw new RuntimeException('获取服务号 access_token 失败：'.$e->getMessage(), 0, $e);
        }

        if (! empty($json['errcode'])) {
            Log::error('wechat_mp.getAccessToken.api_error', $json);
            throw new RuntimeException('获取服务号 access_token 失败：'.($json['errmsg'] ?? 'unknown'));
        }

        $token = (string) ($json['access_token'] ?? '');
        if ($token === '') {
            Log::error('wechat_mp.getAccessToken.empty', $json);
            throw new RuntimeException('获取服务号 access_token 为空');
        }

        Cache::put(self::ACCESS_TOKEN_CACHE_KEY, $token, self::ACCESS_TOKEN_TTL);

        return $token;
    }

    /**
     * 用网页授权 code 换取用户 openid（及网页授权 access_token）。
     *
     * @return array{openid: string, access_token?: string, refresh_token?: string, expires_in?: int, scope?: string}
     */
    public function getOpenidByCode(string $code): array
    {
        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException('授权 code 不能为空');
        }

        $appId = $this->appId();
        $secret = $this->secret();
        if ($appId === '' || $secret === '') {
            throw new RuntimeException('微信服务号 AppID/Secret 未配置');
        }

        try {
            $response = Http::timeout(10)->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
                'appid' => $appId,
                'secret' => $secret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('wechat_mp.getOpenidByCode.http_error', ['message' => $e->getMessage()]);
            throw new RuntimeException('换取 openid 失败：'.$e->getMessage(), 0, $e);
        }

        if (! empty($json['errcode'])) {
            Log::error('wechat_mp.getOpenidByCode.api_error', $json);
            throw new RuntimeException('换取 openid 失败：'.($json['errmsg'] ?? 'unknown'));
        }

        $openid = (string) ($json['openid'] ?? '');
        if ($openid === '') {
            Log::error('wechat_mp.getOpenidByCode.empty', $json);
            throw new RuntimeException('换取 openid 为空');
        }

        return [
            'openid' => $openid,
            'access_token' => $json['access_token'] ?? null,
            'refresh_token' => $json['refresh_token'] ?? null,
            'expires_in' => isset($json['expires_in']) ? (int) $json['expires_in'] : null,
            'scope' => $json['scope'] ?? null,
        ];
    }

    /**
     * 发送模板消息。微信返回错误时记日志，不抛给业务（由调用方决定是否吞掉）。
     *
     * @param  array<string, mixed>  $data  模板字段，如 ['thing1' => ['value' => 'xxx']]
     * @return array{ok: bool, errcode?: int, errmsg?: string, msgid?: mixed}
     */
    public function sendTemplateMessage(string $openid, string $templateId, array $data, string $url = ''): array
    {
        $openid = trim($openid);
        $templateId = trim($templateId);
        if ($openid === '' || $templateId === '') {
            Log::warning('wechat_mp.sendTemplateMessage.skip_empty', compact('openid', 'templateId'));

            return ['ok' => false, 'errcode' => -1, 'errmsg' => 'openid 或 template_id 为空'];
        }

        try {
            $token = $this->getAccessToken();
            $payload = [
                'touser' => $openid,
                'template_id' => $templateId,
                'data' => $data,
            ];
            if ($url !== '') {
                $payload['url'] = $url;
            }

            $response = Http::timeout(10)
                ->post('https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.urlencode($token), $payload);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::error('wechat_mp.sendTemplateMessage.http_error', [
                'openid' => $openid,
                'template_id' => $templateId,
                'message' => $e->getMessage(),
            ]);

            return ['ok' => false, 'errcode' => -1, 'errmsg' => $e->getMessage()];
        }

        $errcode = (int) ($json['errcode'] ?? 0);
        if ($errcode !== 0) {
            Log::error('wechat_mp.sendTemplateMessage.api_error', [
                'openid' => $openid,
                'template_id' => $templateId,
                'url' => $url,
                'data' => $data,
                'response' => $json,
            ]);

            // access_token 失效时清缓存，便于下次重试
            if (in_array($errcode, [40001, 40014, 42001], true)) {
                Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
            }

            return [
                'ok' => false,
                'errcode' => $errcode,
                'errmsg' => (string) ($json['errmsg'] ?? 'unknown'),
            ];
        }

        return [
            'ok' => true,
            'errcode' => 0,
            'msgid' => $json['msgid'] ?? null,
        ];
    }

    /**
     * 查询用户是否已关注服务号（cgi-bin/user/info）。
     * subscribe=1 已关注；0 未关注；接口失败时记日志并返回 false。
     */
    public function isSubscribed(string $openid): bool
    {
        $openid = trim($openid);
        if ($openid === '') {
            return false;
        }

        try {
            $token = $this->getAccessToken();
            $response = Http::timeout(10)->get('https://api.weixin.qq.com/cgi-bin/user/info', [
                'access_token' => $token,
                'openid' => $openid,
                'lang' => 'zh_CN',
            ]);
            $json = $response->json() ?: [];
        } catch (\Throwable $e) {
            Log::warning('wechat_mp.isSubscribed.http_error', [
                'openid' => $openid,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        if (! empty($json['errcode'])) {
            Log::warning('wechat_mp.isSubscribed.api_error', [
                'openid' => $openid,
                'response' => $json,
            ]);
            if (in_array((int) $json['errcode'], [40001, 40014, 42001], true)) {
                Cache::forget(self::ACCESS_TOKEN_CACHE_KEY);
            }

            return false;
        }

        return (int) ($json['subscribe'] ?? 0) === 1;
    }

    /**
     * 生成网页授权 URL（snsapi_base 静默授权）。
     */
    public function buildOauthAuthorizeUrl(string $redirectUri, string $state = 'STATE'): string
    {
        $appId = $this->appId();
        if ($appId === '') {
            throw new RuntimeException('微信服务号 AppID 未配置');
        }

        $query = http_build_query([
            'appid' => $appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'snsapi_base',
            'state' => $state !== '' ? $state : 'STATE',
        ], '', '&', PHP_QUERY_RFC3986);

        return 'https://open.weixin.qq.com/connect/oauth2/authorize?'.$query.'#wechat_redirect';
    }
}
