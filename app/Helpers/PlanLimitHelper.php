<?php

namespace App\Helpers;

use App\Models\Asset;
use App\Models\User;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Support\Facades\Auth;

/**
 * Batas user & asset untuk company user yang login.
 *
 * Sumber kuota: SubscriptionService::quota()
 *   = paket yang berlaku hari ini (upgrade menimpa regular) + add-on aktif.
 *
 * maxUsers()/maxAssets() mengembalikan 0 bila UNLIMITED
 * (kompatibel dengan pemakaian lama untuk pesan error).
 */
class PlanLimitHelper
{
    protected static function companyId(): ?int
    {
        $user = Auth::user();

        return $user && $user->company_id ? (int) $user->company_id : null;
    }

    /**
     * @return array{users:?int, assets:?int}|null
     */
    protected static function quota(): ?array
    {
        $companyId = self::companyId();

        if (!$companyId) {
            return null;
        }

        return app(SubscriptionService::class)->quota($companyId);
    }

    public static function subscription()
    {
        return self::quota()['subscription'] ?? null;
    }

    public static function maxUsers(): int
    {
        return (int) (self::quota()['users'] ?? 0);
    }

    public static function maxAssets(): int
    {
        return (int) (self::quota()['assets'] ?? 0);
    }

    public static function currentUsers(): int
    {
        $companyId = self::companyId();

        return $companyId
            ? User::query()->where('company_id', $companyId)->count()
            : 0;
    }

    public static function currentAssets(): int
    {
        $companyId = self::companyId();

        return $companyId
            ? Asset::query()->where('company_id', $companyId)->count()
            : 0;
    }

    public static function canAddUsers(int $additionalUsers = 1): bool
    {
        $quota = self::quota();

        if (!$quota) {
            return false;
        }

        // NULL = unlimited.
        if ($quota['users'] === null) {
            return true;
        }

        return self::currentUsers() + max(1, $additionalUsers) <= $quota['users'];
    }

    public static function canAddAssets(int $additionalAssets = 1): bool
    {
        $quota = self::quota();

        if (!$quota) {
            return false;
        }

        if ($quota['assets'] === null) {
            return true;
        }

        return self::currentAssets() + max(1, $additionalAssets) <= $quota['assets'];
    }

    public static function remainingUsers(): ?int
    {
        $quota = self::quota();

        if (!$quota || $quota['users'] === null) {
            return null;
        }

        return max(0, $quota['users'] - self::currentUsers());
    }

    public static function remainingAssets(): ?int
    {
        $quota = self::quota();

        if (!$quota || $quota['assets'] === null) {
            return null;
        }

        return max(0, $quota['assets'] - self::currentAssets());
    }
}
