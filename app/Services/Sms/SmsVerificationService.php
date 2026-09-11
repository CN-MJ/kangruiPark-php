<?php

namespace App\Services\Sms;

use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;

class SmsVerificationService
{
    public const PURPOSE_BIND_PHONE = 'bind_phone';

    public const PURPOSE_PARK_LOGIN = 'park_login';

    public const PURPOSE_MERCHANT_LOGIN = 'merchant_login';

    public const PURPOSE_PASSWORD_RESET_PARK = 'password_reset_park';

    public const PURPOSE_PASSWORD_RESET_MERCHANT = 'password_reset_merchant';

    public const PURPOSE_SUPER_ESCALATE = 'super_escalate';

    public function __construct(
        private readonly SmsSenderInterface $sender,
    ) {}

    public static function allowedPurposes(): array
    {
        return [
            self::PURPOSE_BIND_PHONE,
            self::PURPOSE_PARK_LOGIN,
            self::PURPOSE_MERCHANT_LOGIN,
            self::PURPOSE_PASSWORD_RESET_PARK,
            self::PURPOSE_PASSWORD_RESET_MERCHANT,
            self::PURPOSE_SUPER_ESCALATE,
        ];
    }

    /**
     * @throws \RuntimeException
     */
    public function send(string $phone, string $purpose, ?string $ip = null): void
    {
        $this->assertPurpose($purpose);

        $smsEnabled = (bool) config('sms.enabled', false);
        if (! $smsEnabled && ! app()->environment('local')) {
            throw new \RuntimeException('短信服务未启用，请在 .env 设置 SMS_ENABLED=true 并配置厂商密钥');
        }

        $interval = (int) config('sms.resend_interval_seconds', 60);
        $recent = VerificationCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->where('created_at', '>=', now()->subSeconds($interval))
            ->exists();

        if ($recent) {
            throw new \RuntimeException("发送过于频繁，请 {$interval} 秒后再试");
        }

        $dailyLimit = (int) config('sms.daily_limit_per_phone', 10);
        $todayCount = VerificationCode::query()
            ->where('phone', $phone)
            ->whereDate('created_at', today())
            ->count();

        if ($todayCount >= $dailyLimit) {
            throw new \RuntimeException('今日验证码发送次数已达上限');
        }

        $length = max(4, (int) config('sms.code_length', 6));
        $code = str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
        $ttl = (int) config('sms.code_ttl_minutes', 5);

        VerificationCode::create([
            'phone' => $phone,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'ip' => $ip,
            'expires_at' => now()->addMinutes($ttl),
        ]);

        $this->sender->sendVerificationCode($phone, $code, $purpose);
    }

    /**
     * 校验验证码（校验通过后标记已使用）
     */
    public function verify(string $phone, string $code, string $purpose): bool
    {
        $this->assertPurpose($purpose);

        $record = VerificationCode::query()
            ->where('phone', $phone)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (! $record || ! Hash::check($code, $record->code_hash)) {
            return false;
        }

        $record->update(['used_at' => now()]);

        return true;
    }

    /**
     * 开发环境：SMS 未启用时可用固定码联调（仅 local）
     */
    public function verifyOrDevBypass(string $phone, string $code, string $purpose): bool
    {
        if (config('sms.enabled', false)) {
            return $this->verify($phone, $code, $purpose);
        }

        if (app()->environment('local') && $code === '000000') {
            return true;
        }

        return false;
    }

    private function assertPurpose(string $purpose): void
    {
        if (! in_array($purpose, self::allowedPurposes(), true)) {
            throw new \InvalidArgumentException('无效的验证码用途');
        }
    }
}
