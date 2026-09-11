<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Log;

class LogSmsSender implements SmsSenderInterface
{
    public function sendVerificationCode(string $phone, string $code, string $purpose): bool
    {
        Log::info('SMS verification code (dev mock)', [
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
        ]);

        return true;
    }
}
