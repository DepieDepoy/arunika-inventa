<?php

namespace App\Helpers;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class PlanLimitHelper
{
    /**
     * =========================================================
     * GET ACTIVE SUBSCRIPTION
     * =========================================================
     *
     * Mengambil subscription aktif milik company user yang login
     * beserta data plan.
     */
    public static function subscription()
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil company
        |--------------------------------------------------------------------------
        */

        $company = $user->company;

        if (!$company) {
            return null;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil active subscription + plan
        |--------------------------------------------------------------------------
        |
        | activeSubscription() adalah HasOne.
        |
        | Gunakan with('plan') pada relationship query,
        | BUKAN load() pada HasOne.
        |
        */

        return $company
            ->activeSubscription()
            ->with('plan')
            ->first();
    }


    /**
     * =========================================================
     * MAX USERS
     * =========================================================
     *
     * Mengambil batas maksimal user dari plan aktif.
     */
    public static function maxUsers(): int
    {
        $subscription = self::subscription();

        if (!$subscription) {
            return 0;
        }

        if (!$subscription->plan) {
            return 0;
        }

        if ($subscription->plan->max_users === null) {
            return 0;
        }

        return (int) $subscription->plan->max_users;
    }


    /**
     * =========================================================
     * MAX ASSETS
     * =========================================================
     *
     * Mengambil batas maksimal asset dari plan aktif.
     */
    public static function maxAssets(): int
    {
        $subscription = self::subscription();

        if (!$subscription) {
            return 0;
        }

        if (!$subscription->plan) {
            return 0;
        }

        if ($subscription->plan->max_assets === null) {
            return 0;
        }

        return (int) $subscription->plan->max_assets;
    }


    /**
     * =========================================================
     * CURRENT USERS
     * =========================================================
     *
     * Menghitung jumlah user dalam company yang sedang login.
     */
    public static function currentUsers(): int
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return 0;
        }

        return User::query()
            ->where('company_id', $user->company_id)
            ->count();
    }


    /**
     * =========================================================
     * CURRENT ASSETS
     * =========================================================
     *
     * Menghitung jumlah asset dalam company yang sedang login.
     */
    public static function currentAssets(): int
    {
        $user = Auth::user();

        if (!$user || !$user->company_id) {
            return 0;
        }

        return Asset::query()
            ->where('company_id', $user->company_id)
            ->count();
    }


    /**
     * =========================================================
     * CAN ADD USERS
     * =========================================================
     *
     * Mengecek apakah company masih mempunyai slot user.
     *
     * Contoh:
     *
     * max_users = 5
     * current   = 4
     * additional = 1
     *
     * hasil = true
     *
     * Jika current = 5:
     * hasil = false
     */
    public static function canAddUsers(
        int $additionalUsers = 1
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Additional tidak boleh negatif
        |--------------------------------------------------------------------------
        */

        if ($additionalUsers < 1) {
            $additionalUsers = 1;
        }

        $limit = self::maxUsers();

        /*
        |--------------------------------------------------------------------------
        | Limit 0 = unlimited
        |--------------------------------------------------------------------------
        */

        if ($limit <= 0) {
            return true;
        }

        $current = self::currentUsers();

        return (
            $current + $additionalUsers
        ) <= $limit;
    }


    /**
     * =========================================================
     * CAN ADD ASSETS
     * =========================================================
     *
     * Mengecek apakah company masih mempunyai slot asset.
     */
    public static function canAddAssets(
        int $additionalAssets = 1
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | Additional tidak boleh negatif
        |--------------------------------------------------------------------------
        */

        if ($additionalAssets < 1) {
            $additionalAssets = 1;
        }

        $limit = self::maxAssets();

        /*
        |--------------------------------------------------------------------------
        | Limit 0 = unlimited
        |--------------------------------------------------------------------------
        */

        if ($limit <= 0) {
            return true;
        }

        $current = self::currentAssets();

        return (
            $current + $additionalAssets
        ) <= $limit;
    }


    /**
     * =========================================================
     * REMAINING USERS
     * =========================================================
     *
     * Mengembalikan jumlah slot user yang masih tersedia.
     *
     * null = unlimited.
     */
    public static function remainingUsers(): ?int
    {
        $limit = self::maxUsers();

        /*
        |--------------------------------------------------------------------------
        | Unlimited
        |--------------------------------------------------------------------------
        */

        if ($limit <= 0) {
            return null;
        }

        $current = self::currentUsers();

        return max(
            0,
            $limit - $current
        );
    }


    /**
     * =========================================================
     * REMAINING ASSETS
     * =========================================================
     *
     * Mengembalikan jumlah slot asset yang masih tersedia.
     *
     * null = unlimited.
     */
    public static function remainingAssets(): ?int
    {
        $limit = self::maxAssets();

        /*
        |--------------------------------------------------------------------------
        | Unlimited
        |--------------------------------------------------------------------------
        */

        if ($limit <= 0) {
            return null;
        }

        $current = self::currentAssets();

        return max(
            0,
            $limit - $current
        );
    }
}