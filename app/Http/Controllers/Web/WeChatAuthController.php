<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\WeChatOpenidBinder;
use App\Services\WeChatService;
use App\Support\WeChatBindState;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

/**
 * 服务号 H5 授权：服务端完成 code→openid 绑定，避免微信内置浏览器 fetch 失败。
 */
class WeChatAuthController extends Controller
{
    public function __construct(
        private readonly WeChatService $wechat,
        private readonly WeChatOpenidBinder $binder,
    ) {}

    public function index(Request $request): View
    {
        $code = (string) $request->query('code', '');
        $stateFromWechat = (string) $request->query('state', '');
        $recheck = $request->boolean('recheck');

        $state = $stateFromWechat !== ''
            ? $stateFromWechat
            : $this->buildStateFromQuery($request);

        $parsed = WeChatBindState::parse($state);
        $plainState = $this->toPlainState($parsed);

        $panel = 'loading';
        $errorTitle = '';
        $errorMsg = '';
        $bindHint = $this->hintText($parsed);

        // 复查关注（未关注页点「我已关注」）
        if ($recheck && $plainState !== '') {
            $check = $this->binder->checkSubscribeByState($plainState);
            if (! $check['ok']) {
                $panel = 'error';
                $errorTitle = '确认失败';
                $errorMsg = $check['msg'];
            } elseif (! empty($check['is_subscribed'])) {
                $panel = 'ok';
            } else {
                $panel = 'unsub';
            }

            return $this->render($request, [
                'panel' => $panel,
                'state' => $plainState,
                'oauthUrl' => '',
                'oauthError' => '',
                'bindHint' => $bindHint,
                'errorTitle' => $errorTitle,
                'errorMsg' => $errorMsg,
            ]);
        }

        // 微信回调：服务端直接绑定
        if ($code !== '') {
            $result = $this->binder->bindByCode($code, $state);
            if (! $result['ok']) {
                $panel = 'error';
                $errorTitle = '授权失败';
                $errorMsg = $result['msg'];
            } elseif (! empty($result['is_subscribed'])) {
                $panel = 'ok';
            } else {
                $panel = 'unsub';
            }

            return $this->render($request, [
                'panel' => $panel,
                'state' => $plainState !== '' ? $plainState : $state,
                'oauthUrl' => '',
                'oauthError' => '',
                'bindHint' => $bindHint,
                'errorTitle' => $errorTitle,
                'errorMsg' => $errorMsg,
            ]);
        }

        // 首次进入：跳转微信 OAuth
        $oauthUrl = '';
        $oauthError = '';
        if ($plainState === '') {
            $oauthError = '缺少绑定参数：访客请传 phone，商户请传 id';
            $panel = 'error';
            $errorTitle = '无法发起授权';
            $errorMsg = $oauthError;
        } else {
            try {
                $configured = trim((string) config('services.wechat_mp.auth_redirect_uri', ''));
                $callbackUri = $configured !== '' ? $configured : $request->url();
                $oauthState = WeChatBindState::forOauth($plainState);
                $oauthUrl = $this->wechat->buildOauthAuthorizeUrl($callbackUri, $oauthState);
                $panel = 'redirect';
            } catch (RuntimeException $e) {
                $oauthError = $e->getMessage();
                $panel = 'error';
                $errorTitle = '无法发起授权';
                $errorMsg = $oauthError;
            }
        }

        return $this->render($request, [
            'panel' => $panel,
            'state' => $plainState,
            'oauthUrl' => $oauthUrl,
            'oauthError' => $oauthError,
            'bindHint' => $bindHint,
            'errorTitle' => $errorTitle,
            'errorMsg' => $errorMsg,
        ]);
    }

    public function recheck(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');

        return redirect()->route('wechat.auth', [
            'recheck' => 1,
            'state' => $state,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function render(Request $request, array $data): View
    {
        return view('wechat.auth', array_merge([
            'appId' => $this->wechat->appId(),
            'qrcodeUrl' => asset('images/mp-qrcode.jpg'),
            'recheckUrl' => route('wechat.recheck', ['state' => $data['state'] ?? '']),
        ], $data));
    }

    private function buildStateFromQuery(Request $request): string
    {
        $type = (string) $request->query('type', '');
        if ($type === 'user') {
            $type = WeChatBindState::TYPE_VISITOR;
        }

        if ($type === WeChatBindState::TYPE_VISITOR || $type === '') {
            $phone = trim((string) (
                $request->query('phone')
                ?: $request->query('user_phone')
                ?: ''
            ));
            if ($phone !== '') {
                return WeChatBindState::forVisitor($phone);
            }
        }

        if ($type === WeChatBindState::TYPE_MERCHANT) {
            $id = (int) ($request->query('id') ?: $request->query('merchant_id') ?: 0);
            if ($id > 0) {
                return WeChatBindState::forMerchant($id);
            }
        }

        return '';
    }

    /**
     * @param  array{type: string, phone?: string, merchant_id?: int}  $parsed
     */
    private function toPlainState(array $parsed): string
    {
        $type = (string) ($parsed['type'] ?? '');
        if ($type === WeChatBindState::TYPE_VISITOR && ! empty($parsed['phone'])) {
            return WeChatBindState::forVisitor((string) $parsed['phone']);
        }
        if ($type === WeChatBindState::TYPE_MERCHANT && ! empty($parsed['merchant_id'])) {
            return WeChatBindState::forMerchant((int) $parsed['merchant_id']);
        }

        return '';
    }

    /**
     * @param  array{type: string, phone?: string, merchant_id?: int}  $parsed
     */
    private function hintText(array $parsed): string
    {
        if (($parsed['type'] ?? '') === WeChatBindState::TYPE_VISITOR) {
            return '访客手机号：'.($parsed['phone'] ?? '-');
        }
        if (($parsed['type'] ?? '') === WeChatBindState::TYPE_MERCHANT) {
            return '商户 ID：'.($parsed['merchant_id'] ?? '-');
        }

        return '';
    }
}
