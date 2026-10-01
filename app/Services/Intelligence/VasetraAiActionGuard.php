<?php

namespace App\Services\Intelligence;

use App\Models\User;

class VasetraAiActionGuard
{
    /**
     * Check whether the authenticated user
     * is allowed to execute an AI action.
     */
    public function can(
        User $user,
        string $permission
    ): bool {
        return $user->hasPermission($permission);
    }


    /**
     * Require permission.
     *
     * Returns a structured result instead of throwing an exception,
     * because the AI interface should explain the restriction naturally.
     */
    public function check(
        User $user,
        string $permission
    ): array {

        if ($this->can($user, $permission)) {

            return [
                'allowed' => true,
                'permission' => $permission,
                'message' => null,
            ];
        }


        return [
            'allowed' => false,
            'permission' => $permission,
            'message' =>
                'Maaf, Anda tidak memiliki permission ' .
                $permission .
                ' untuk melakukan tindakan ini.',
        ];
    }


    /**
     * Check multiple permissions.
     *
     * ALL permissions must be granted.
     */
    public function checkAll(
        User $user,
        array $permissions
    ): array {

        $missing = [];

        foreach ($permissions as $permission) {

            if (!$this->can($user, $permission)) {
                $missing[] = $permission;
            }
        }


        if (empty($missing)) {

            return [
                'allowed' => true,
                'missing' => [],
                'message' => null,
            ];
        }


        return [
            'allowed' => false,
            'missing' => $missing,
            'message' =>
                'Anda tidak memiliki permission yang diperlukan: ' .
                implode(', ', $missing) .
                '.',
        ];
    }


    /**
     * Check whether at least one permission is available.
     */
    public function checkAny(
        User $user,
        array $permissions
    ): array {

        foreach ($permissions as $permission) {

            if ($this->can($user, $permission)) {

                return [
                    'allowed' => true,
                    'permission' => $permission,
                    'message' => null,
                ];
            }
        }


        return [
            'allowed' => false,
            'permission' => null,
            'message' =>
                'Anda tidak memiliki permission untuk melakukan tindakan ini.',
        ];
    }
}