<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\User;
use App\Support\WeChatBindState;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * 服务号 openid 绑定 + 关注状态查询（H5 / API 共用）。
 */
class WeChatOpenidBinder
{
    public function __construct(
        private readonly WeChatService $wechat,
    ) {}

    /**
     * @return array{ok: bool, msg: string, type?: string, is_subscribed?: bool, user_id?: int, merchant_id?: int, phone?: string|null}
     */
    public function bindByCode(string $code, string $state, int $userId = 0): array
    {
        $parsed = WeChatBindState::parse($state);
        $type = (string) ($parsed['type'] ?? '');
        if ($type === 'user') {
            $type = WeChatBindState::TYPE_VISITOR;
        }
        $phone = trim((string) ($parsed['phone'] ?? ''));
        $merchantId = (int) ($parsed['merchant_id'] ?? 0);

        if (! in_array($type, [WeChatBindState::TYPE_VISITOR, WeChatBindState::TYPE_MERCHANT], true)) {
            return ['ok' => false, 'msg' => '授权参数无效（缺少 visitor/merchant）'];
        }

        try {
            $oauth = $this->wechat->getOpenidByCode($code);
            $openid = $oauth['openid'];
        } catch (RuntimeException $e) {
            Log::warning('wechat.bind_by_code.oauth_failed', ['message' => $e->getMessage()]);

            return ['ok' => false, 'msg' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error('wechat.bind_by_code.exception', ['message' => $e->getMessage()]);

            return ['ok' => false, 'msg' => '换取微信身份失败，请稍后重试'];
        }

        if ($type === WeChatBindState::TYPE_VISITOR) {
            return $this->bindVisitor($openid, $phone, $userId);
        }

        return $this->bindMerchant($openid, $merchantId);
    }

    /**
     * @return array{ok: bool, msg: string, is_subscribed?: bool}
     */
    public function checkSubscribeByState(string $state): array
    {
        $parsed = WeChatBindState::parse($state);
        $type = (string) ($parsed['type'] ?? '');
        if ($type === 'user') {
            $type = WeChatBindState::TYPE_VISITOR;
        }
        $phone = trim((string) ($parsed['phone'] ?? ''));
        $merchantId = (int) ($parsed['merchant_id'] ?? 0);

        $openid = $this->resolveBoundOpenid($type, $phone, $merchantId);
        if ($openid === '') {
            return ['ok' => false, 'msg' => '尚未完成授权，请重新打开授权链接'];
        }

        return [
            'ok' => true,
            'msg' => 'ok',
            'is_subscribed' => $this->wechat->isSubscribed($openid),
        ];
    }

    /**
     * @return array{ok: bool, msg: string, type?: string, is_subscribed?: bool, user_id?: int, phone?: string|null}
     */
    private function bindVisitor(string $openid, string $phone, int $userId = 0): array
    {
        $user = null;
        if ($phone !== '') {
            $user = User::query()->where('phone', $phone)->first();
        }
        if (! $user && $userId > 0) {
            $user = User::query()->find($userId);
        }
        if (! $user) {
            return [
                'ok' => false,
                'msg' => $phone !== ''
                    ? '未找到该手机号对应用户，请先在小程序登录后再授权'
                    : '授权参数缺少手机号',
            ];
        }

        $user->update(['mp_openid' => $openid]);
        if ($user->phone) {
            Merchant::query()
                ->where('contact_phone', (string) $user->phone)
                ->update(['mp_openid' => $openid]);
        }

        $subscribed = $this->wechat->isSubscribed($openid);

        return [
            'ok' => true,
            'msg' => '授权成功',
            'type' => WeChatBindState::TYPE_VISITOR,
            'user_id' => $user->id,
            'phone' => $user->phone,
            'is_subscribed' => $subscribed,
        ];
    }

    /**
     * @return array{ok: bool, msg: string, type?: string, is_subscribed?: bool, merchant_id?: int}
     */
    private function bindMerchant(string $openid, int $merchantId): array
    {
        if ($merchantId <= 0) {
            return ['ok' => false, 'msg' => '授权参数缺少商户 ID'];
        }

        $merchant = Merchant::query()->find($merchantId);
        if (! $merchant) {
            return ['ok' => false, 'msg' => '商户账号不存在'];
        }

        $merchant->update(['mp_openid' => $openid]);
        if ($merchant->contact_phone) {
            User::query()
                ->where('phone', (string) $merchant->contact_phone)
                ->update(['mp_openid' => $openid]);
        }

        $subscribed = $this->wechat->isSubscribed($openid);

        return [
            'ok' => true,
            'msg' => '授权成功',
            'type' => WeChatBindState::TYPE_MERCHANT,
            'merchant_id' => $merchant->id,
            'is_subscribed' => $subscribed,
        ];
    }

    private function resolveBoundOpenid(string $type, string $phone, int $merchantId): string
    {
        if ($type === WeChatBindState::TYPE_MERCHANT || ($type === '' && $merchantId > 0)) {
            $merchant = Merchant::query()->find($merchantId);
            $openid = is_string($merchant?->mp_openid) ? trim($merchant->mp_openid) : '';
            if ($openid !== '') {
                return $openid;
            }
        }

        if ($phone !== '') {
            $user = User::query()->where('phone', $phone)->first();
            $openid = is_string($user?->mp_openid) ? trim($user->mp_openid) : '';
            if ($openid !== '') {
                return $openid;
            }
        }

        return '';
    }
}
