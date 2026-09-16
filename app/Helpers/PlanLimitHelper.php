<?php

namespace App\Helpers;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PlanLimitHelper
{
    /**
     * Get active subscription beserta plan perusahaan user yang sedang login.
     */
    public static function subscription()
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return null;
        }

        $company = $user->company;

        if (!$company) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil relationship activeSubscription
        |--------------------------------------------------------------------------
        |
        | activeSubscription() adalah HasOne relationship.
        | Jangan gunakan ->load() langsung pada relationship.
        |
        */

        return $company
            ->activeSubscription()
            ->with('plan')
            ->first();
    }


    /**
     * Maximum user sesuai plan aktif.
     */
    public static function maxUsers(): int
    {
        $subscription = self::subscription();

        if (!$subscription || !$subscription->plan) {
            return 0;
        }

        $limit = $subscription->plan->max_users;

        return $limit !== null
            ? (int) $limit
            : 0;
    }


    /**
     * Maximum asset sesuai plan aktif.
     */
    public static function maxAssets(): int
    {
        $subscription = self::subscription();

        if (!$subscription || !$subscription->plan) {
            return 0;
        }

        $limit = $subscription->plan->max_assets;

        return $limit !== null
            ? (int) $limit
            : 0;
    }


    /**
     * Jumlah user perusahaan saat ini.
     */
    public static function currentUsers(): int
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return 0;
        }

        return User::where(
            'company_id',
            $user->company_id
        )->count();
    }


    /**
     * Jumlah asset perusahaan saat ini.
     */
    public static function currentAssets(): int
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return 0;
        }

        return Asset::where(
            'company_id',
            $user->company_id
        )->count();
    }


    /**
     * Cek apakah masih boleh menambah user.
     */
    public static function canAddUsers(
        int $additionalUsers = 1
    ): bool {

        $limit = self::maxUsers();

        /*
        |--------------------------------------------------------------------------
        | Limit 0 dianggap unlimited
        |--------------------------------------------------------------------------
        */

        if ($limit <= 0) {
            return true;
        }

        return (
            self::currentUsers() + $additionalUsers
        ) <= $limit;
    }


    /**
     * Cek apakah masih boleh menambah asset.
     */
    public static function canAddAssets(
        int $additionalAssets = 1
    ): bool {

        $limit = self::maxAssets();

        /*
        |--------------------------------------------------------------------------
        | Limit 0 dianggap unlimited
        |--------------------------------------------------------------------------
        */

        if ($limit <= 0) {
            return true;
        }

        return (
            self::currentAssets() + $additionalAssets
        ) <= $limit;
    }


    /**
     * Sisa slot user.
     */
    public static function remainingUsers(): ?int
    {
        $limit = self::maxUsers();

        if ($limit <= 0) {
            return null;
        }

        return max(
            0,
            $limit - self::currentUsers()
        );
    }


    /**
     * Sisa slot asset.
     */
    public static function remainingAssets(): ?int
    {
        $limit = self::maxAssets();

        if ($limit <= 0) {
            return null;
        }

        return max(
            0,
            $limit - self::currentAssets()
        );
    }
}
