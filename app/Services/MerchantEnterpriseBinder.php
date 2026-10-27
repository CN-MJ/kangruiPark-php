<?php

namespace App\Services;

use App\Models\Enterprise;
use App\Models\Merchant;
use App\Models\User;

/**
 * 将小程序「商户」用户与 enterprises / merchants 工作台账号对齐。
 */
class MerchantEnterpriseBinder
{
    /**
     * 访客提交商户入驻申请（不立刻生效，待园区审核）。
     */
    public function apply(User $user, Enterprise $enterprise, string $note): void
    {
        $user->merchant_apply_status = User::APPLY_PENDING;
        $user->merchant_applied_at = now();
        $user->merchant_apply_enterprise_id = $enterprise->id;
        $user->merchant_apply_note = $note;
        $user->merchant_reject_reason = null;
        // 审核通过前保持访客，避免未审即可管店
        if ($user->role !== User::ROLE_PARK && $user->role !== User::ROLE_MERCHANT) {
            $user->role = User::ROLE_VISITOR;
        }
        $user->save();
    }

    /**
     * 园区审核通过：绑定企业并同步 merchants。
     */
    public function approve(User $user, Enterprise $enterprise): Merchant
    {
        $user->merchant_apply_status = User::APPLY_APPROVED;
        $user->merchant_reject_reason = null;
        $user->merchant_apply_note = $user->merchant_apply_note; // 保留备案
        $user->merchant_apply_enterprise_id = $enterprise->id;

        return $this->bind($user, $enterprise);
    }

    /**
     * 园区拒绝申请。
     */
    public function reject(User $user, string $reason): void
    {
        $user->merchant_apply_status = User::APPLY_REJECTED;
        $user->merchant_reject_reason = $reason;
        // 未通过前若尚不是已认证商户，确保仍是访客
        if ($user->role === User::ROLE_MERCHANT && ! $user->enterprise_id) {
            $user->role = User::ROLE_VISITOR;
        }
        $user->save();
    }

    /**
     * 取消进行中的申请（用户改回访客时）。
     */
    public function cancelPendingApplication(User $user): void
    {
        if ($user->merchant_apply_status !== User::APPLY_PENDING) {
            return;
        }
        $user->merchant_apply_status = User::APPLY_NONE;
        $user->merchant_apply_enterprise_id = null;
        $user->merchant_apply_note = null;
        $user->merchant_reject_reason = null;
        $user->save();
    }

    /**
     * 绑定用户到企业，并确保同手机号的 merchants 账号指向该企业。
     */
    public function bind(User $user, Enterprise $enterprise): Merchant
    {
        $user->enterprise_id = $enterprise->id;
        $user->role = User::ROLE_MERCHANT;
        $user->merchant_apply_status = User::APPLY_APPROVED;
        if (! $user->merchant_applied_at) {
            $user->merchant_applied_at = now();
        }
        $user->merchant_apply_enterprise_id = $enterprise->id;
        $user->merchant_reject_reason = null;
        $user->save();

        return $this->syncMerchantAccount($user, $enterprise);
    }

    /**
     * 取消商户身份时的清理（保留 merchants 行，解除企业绑定）。
     */
    public function unbind(User $user): void
    {
        $phone = trim((string) $user->phone);
        $enterpriseId = $user->enterprise_id;

        $user->enterprise_id = null;
        if ($user->role === User::ROLE_MERCHANT) {
            $user->role = User::ROLE_VISITOR;
        }
        $user->merchant_apply_status = User::APPLY_NONE;
        $user->merchant_apply_enterprise_id = null;
        $user->merchant_apply_note = null;
        $user->merchant_reject_reason = null;
        $user->save();

        if ($phone === '' || ! $enterpriseId) {
            return;
        }

        Merchant::query()
            ->where('contact_phone', $phone)
            ->where('enterprise_id', $enterpriseId)
            ->update(['enterprise_id' => null]);
    }

    public function syncMerchantAccount(User $user, Enterprise $enterprise): Merchant
    {
        $phone = trim((string) $user->phone);
        $merchant = Merchant::query()->where('contact_phone', $phone)->first();

        $payload = [
            'enterprise_id' => $enterprise->id,
            'name' => $enterprise->name,
            'contact_name' => $user->name ?: $enterprise->contact_name,
            'contact_phone' => $phone,
            'status' => Merchant::STATUS_ACTIVE,
        ];

        if (is_string($user->mp_openid) && $user->mp_openid !== '') {
            $payload['mp_openid'] = $user->mp_openid;
        }

        if ($merchant) {
            $merchant->update($payload);
        } else {
            $digits = preg_replace('/\D+/', '', $phone) ?: '000000';
            $payload['password'] = substr($digits, -6) ?: '000000';
            $merchant = Merchant::create($payload);
        }

        return $merchant->fresh();
    }

    public function resolveMerchant(User $user): ?Merchant
    {
        if (! $user->isMerchant() || ! $user->enterprise_id) {
            return null;
        }

        $phone = trim((string) $user->phone);
        $query = Merchant::query()
            ->active()
            ->where('enterprise_id', $user->enterprise_id);

        if ($phone !== '') {
            $byPhone = (clone $query)->where('contact_phone', $phone)->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        return $query->orderByDesc('id')->first();
    }
}
