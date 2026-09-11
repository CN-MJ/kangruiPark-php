<?php

namespace App\Services\Sms;

interface SmsSenderInterface
{
    /**
     * @return bool 是否发送成功
     */
    public function sendVerificationCode(string $phone, string $code, string $purpose): bool;
}
