<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            // =====================================================
            // ASSET
            // =====================================================
            [
                'permission_name' => 'View Asset',
                'permission_code' => 'asset.view',
                'module'          => 'asset',
                'action'          => 'view',
                'description'     => 'Melihat data asset',
            ],
            [
                'permission_name' => 'Create Asset',
                'permission_code' => 'asset.create',
                'module'          => 'asset',
                'action'          => 'create',
                'description'     => 'Menambahkan asset',
            ],
            [
                'permission_name' => 'Edit Asset',
                'permission_code' => 'asset.edit',
                'module'          => 'asset',
                'action'          => 'edit',
                'description'     => 'Mengubah data asset',
            ],
            [
                'permission_name' => 'Delete Asset',
                'permission_code' => 'asset.delete',
                'module'          => 'asset',
                'action'          => 'delete',
                'description'     => 'Menghapus asset',
            ],
            [
                'permission_name' => 'Import Asset',
                'permission_code' => 'asset.import',
                'module'          => 'asset',
                'action'          => 'import',
                'description'     => 'Import data asset',
            ],
            [
                'permission_name' => 'Export Asset',
                'permission_code' => 'asset.export',
                'module'          => 'asset',
                'action'          => 'export',
                'description'     => 'Export data asset',
            ],
            [
                'permission_name' => 'Print QR Asset',
                'permission_code' => 'asset.print_qr',
                'module'          => 'asset',
                'action'          => 'print_qr',
                'description'     => 'Mencetak QR asset',
            ],

            // =====================================================
            // CATEGORY
            // =====================================================
            [
                'permission_name' => 'View Category',
                'permission_code' => 'category.view',
                'module'          => 'category',
                'action'          => 'view',
                'description'     => 'Melihat kategori',
            ],
            [
                'permission_name' => 'Create Category',
                'permission_code' => 'category.create',
                'module'          => 'category',
                'action'          => 'create',
                'description'     => 'Menambahkan kategori',
            ],
            [
                'permission_name' => 'Edit Category',
                'permission_code' => 'category.edit',
                'module'          => 'category',
                'action'          => 'edit',
                'description'     => 'Mengubah kategori',
            ],
            [
                'permission_name' => 'Delete Category',
                'permission_code' => 'category.delete',
                'module'          => 'category',
                'action'          => 'delete',
                'description'     => 'Menghapus kategori',
            ],
            [
                'permission_name' => 'Export Category',
                'permission_code' => 'category.export',
                'module'          => 'category',
                'action'          => 'export',
                'description'     => 'Export data category',
            ],

            // =====================================================
            // SUB CATEGORY
            // =====================================================
            [
                'permission_name' => 'View Sub Category',
                'permission_code' => 'subcategory.view',
                'module'          => 'subcategory',
                'action'          => 'view',
                'description'     => 'Melihat sub kategori',
            ],
            [
                'permission_name' => 'Create Sub Category',
                'permission_code' => 'subcategory.create',
                'module'          => 'subcategory',
                'action'          => 'create',
                'description'     => 'Menambahkan sub kategori',
            ],
            [
                'permission_name' => 'Edit Sub Category',
                'permission_code' => 'subcategory.edit',
                'module'          => 'subcategory',
                'action'          => 'edit',
                'description'     => 'Mengubah sub kategori',
            ],
            [
                'permission_name' => 'Delete Sub Category',
                'permission_code' => 'subcategory.delete',
                'module'          => 'subcategory',
                'action'          => 'delete',
                'description'     => 'Menghapus sub kategori',
            ],
            [
                'permission_name' => 'Export Sub Category',
                'permission_code' => 'subcategory.export',
                'module'          => 'subcategory',
                'action'          => 'export',
                'description'     => 'Export data category',
            ],

            // =====================================================
            // VENDOR
            // =====================================================
            [
                'permission_name' => 'View Vendor',
                'permission_code' => 'vendor.view',
                'module'          => 'vendor',
                'action'          => 'view',
                'description'     => 'Melihat vendor',
            ],
            [
                'permission_name' => 'Create Vendor',
                'permission_code' => 'vendor.create',
                'module'          => 'vendor',
                'action'          => 'create',
                'description'     => 'Menambahkan vendor',
            ],
            [
                'permission_name' => 'Edit Vendor',
                'permission_code' => 'vendor.edit',
                'module'          => 'vendor',
                'action'          => 'edit',
                'description'     => 'Mengubah vendor',
            ],
            [
                'permission_name' => 'Delete Vendor',
                'permission_code' => 'vendor.delete',
                'module'          => 'vendor',
                'action'          => 'delete',
                'description'     => 'Menghapus vendor',
            ],
            [
                'permission_name' => 'Export Vendor',
                'permission_code' => 'vendor.export',
                'module'          => 'vendor',
                'action'          => 'export',
                'description'     => 'Export data vendor',
            ],

            // =====================================================
            // USER
            // =====================================================
            [
                'permission_name' => 'View User',
                'permission_code' => 'user.view',
                'module'          => 'user',
                'action'          => 'view',
                'description'     => 'Melihat user',
            ],
            [
                'permission_name' => 'Create User',
                'permission_code' => 'user.create',
                'module'          => 'user',
                'action'          => 'create',
                'description'     => 'Menambahkan user',
            ],
            [
                'permission_name' => 'Edit User',
                'permission_code' => 'user.edit',
                'module'          => 'user',
                'action'          => 'edit',
                'description'     => 'Mengubah user',
            ],
            [
                'permission_name' => 'Delete User',
                'permission_code' => 'user.delete',
                'module'          => 'user',
                'action'          => 'delete',
                'description'     => 'Menghapus user',
            ],
            [
                'permission_name' => 'Export User',
                'permission_code' => 'user.export',
                'module'          => 'user',
                'action'          => 'export',
                'description'     => 'Export data user',
            ],
            [
                'permission_name' => 'Import User',
                'permission_code' => 'user.import',
                'module'          => 'user',
                'action'          => 'import',
                'description'     => 'Import data user',
            ],

            // =====================================================
            // ROLE
            // =====================================================
            [
                'permission_name' => 'View Role',
                'permission_code' => 'role.view',
                'module'          => 'role',
                'action'          => 'view',
                'description'     => 'Melihat role',
            ],
            [
                'permission_name' => 'Create Role',
                'permission_code' => 'role.create',
                'module'          => 'role',
                'action'          => 'create',
                'description'     => 'Menambahkan role',
            ],
            [
                'permission_name' => 'Edit Role',
                'permission_code' => 'role.edit',
                'module'          => 'role',
                'action'          => 'edit',
                'description'     => 'Mengubah role',
            ],
            [
                'permission_name' => 'Delete Role',
                'permission_code' => 'role.delete',
                'module'          => 'role',
                'action'          => 'delete',
                'description'     => 'Menghapus role',
            ],
            [
                'permission_name' => 'Manage Role Permission',
                'permission_code' => 'role.permission',
                'module'          => 'role',
                'action'          => 'permission',
                'description'     => 'Mengatur permission role',
            ],

            // =====================================================
            // IMPORT HISTORY
            // =====================================================
            [
                'permission_name' => 'View Import History',
                'permission_code' => 'import_history.view',
                'module'          => 'import_history',
                'action'          => 'view',
                'description'     => 'Melihat riwayat import',
            ],

            // =====================================================
            // MAINTENANCE REQUEST
            // =====================================================
            [
                'permission_name' => 'View Maintenance Requests',
                'permission_code' => 'maintenance.request.view',
                'module'          => 'maintenance_request',
                'action'          => 'view',
                'description'     => 'Melihat daftar permintaan maintenance',
            ],
            [
                'permission_name' => 'Create Maintenance Request',
                'permission_code' => 'maintenance.request.create',
                'module'          => 'maintenance_request',
                'action'          => 'create',
                'description'     => 'Membuat permintaan maintenance dari aset yang menjadi tanggung jawabnya',
            ],
            [
                'permission_name' => 'Edit Maintenance Request',
                'permission_code' => 'maintenance.request.edit',
                'module'          => 'maintenance_request',
                'action'          => 'edit',
                'description'     => 'Mengambil, mengerjakan, menambahkan progres, dan menyelesaikan permintaan maintenance',
            ],
            [
                'permission_name' => 'Delete Maintenance Request',
                'permission_code' => 'maintenance.request.delete',
                'module'          => 'maintenance_request',
                'action'          => 'delete',
                'description'     => 'Menghapus permintaan maintenance',
            ],

            // =====================================================
            // MAINTENANCE HISTORY
            // =====================================================
            [
                'permission_name' => 'View Maintenance History',
                'permission_code' => 'maintenance.history.view',
                'module'          => 'maintenance_history',
                'action'          => 'view',
                'description'     => 'Melihat riwayat maintenance yang telah selesai',
            ],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                [
                    'permission_code' => $permission['permission_code'],
                ],
                [
                    'permission_name' => $permission['permission_name'],
                    'module'          => $permission['module'],
                    'action'          => $permission['action'],
                    'description'     => $permission['description'],
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );
        }
    }
}