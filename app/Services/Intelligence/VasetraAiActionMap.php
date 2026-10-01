<?php

namespace App\Services\Intelligence;

class VasetraAiActionMap
{
    public const ACTIONS = [

        'asset.create' => [
            'permission' => 'asset.create',
            'label' => 'Tambah Asset',
        ],

        'asset.edit' => [
            'permission' => 'asset.edit',
            'label' => 'Edit Asset',
        ],

        'asset.delete' => [
            'permission' => 'asset.delete',
            'label' => 'Hapus Asset',
        ],

        'category.create' => [
            'permission' => 'category.create',
            'label' => 'Tambah Category',
        ],

        'category.edit' => [
            'permission' => 'category.edit',
            'label' => 'Edit Category',
        ],

        'subcategory.create' => [
            'permission' => 'subcategory.create',
            'label' => 'Tambah Subcategory',
        ],

        'vendor.create' => [
            'permission' => 'vendor.create',
            'label' => 'Tambah Vendor',
        ],

        'user.create' => [
            'permission' => 'user.create',
            'label' => 'Tambah User',
        ],

        'maintenance.create' => [
            'permission' => 'maintenance.create',
            'label' => 'Tambah Maintenance',
        ],

        'maintenance.edit' => [
            'permission' => 'maintenance.edit',
            'label' => 'Edit Maintenance',
        ],

        'maintenance.delete' => [
            'permission' => 'maintenance.delete',
            'label' => 'Hapus Maintenance',
        ],
    ];


    public static function get(
        string $action
    ): ?array {

        return self::ACTIONS[$action] ?? null;
    }
}