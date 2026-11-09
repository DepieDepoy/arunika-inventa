<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Data pilihan (dropdown) untuk form di aplikasi mobile.
 * Semua data dibatasi ke perusahaan user & hanya yang berstatus aktif.
 */
class LookupController extends ApiController
{
    /**
     * Kategori aktif beserta subkategorinya.
     */
    public function categories(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $subCategories = SubCategory::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('sub_category_name')
            ->get(['id', 'category_id', 'sub_category_code', 'sub_category_name'])
            ->groupBy('category_id');

        $categories = Category::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderBy('category_name')
            ->get(['id', 'category_code', 'category_name'])
            ->map(fn ($c) => [
                'id' => $c->id,
                'code' => $c->category_code,
                'name' => $c->category_name,
                'sub_categories' => ($subCategories[$c->id] ?? collect())->map(fn ($s) => [
                    'id' => $s->id,
                    'code' => $s->sub_category_code,
                    'name' => $s->sub_category_name,
                ])->values(),
            ]);

        return $this->ok(['categories' => $categories]);
    }

    public function vendors(Request $request): JsonResponse
    {
        $vendors = Vendor::query()
            ->where('company_id', $request->user()->company_id)
            ->where('status', 1)
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w->where('vendor_name', 'like', $term)->orWhere('vendor_code', 'like', $term));
            })
            ->orderBy('vendor_name')
            ->limit(200)
            ->get(['id', 'vendor_code', 'vendor_name', 'phone', 'pic_name'])
            ->map(fn ($v) => [
                'id' => $v->id,
                'code' => $v->vendor_code,
                'name' => $v->vendor_name,
                'phone' => $v->phone,
                'pic_name' => $v->pic_name,
            ]);

        return $this->ok(['vendors' => $vendors]);
    }

    /**
     * Pilihan user aktif (penanggung jawab aset, peserta meeting).
     * Query: search (nama/email), limit (maks. 100).
     */
    public function users(Request $request): JsonResponse
    {
        // asset_id: hanya user di cabang aset (untuk serah terima). branch_id: filter cabang.
        $assetBranch = $request->filled('asset_id')
            ? \App\Models\Asset::query()->where('company_id', $request->user()->company_id)->whereKey($request->integer('asset_id'))->value('branch_id')
            : null;

        $users = \App\Support\BranchAccess::scopeUsers(User::query()
            ->where('company_id', $request->user()->company_id), $request->user(), 'branch_id')
            ->where('status', 1)
            ->when($assetBranch, fn ($q) => $q->where('branch_id', $assetBranch))
            ->when(!$assetBranch && $request->filled('branch_id'), fn ($q) => $q->where('branch_id', $request->integer('branch_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->with('role:id,role_name')
            ->orderBy('name')
            ->limit(min(max($request->integer('limit', 50), 1), 100))
            ->get(['id', 'name', 'email', 'role_id'])
            ->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role?->role_name,
            ]);

        return $this->ok(['users' => $users]);
    }

    /**
     * Nilai enum yang dipakai form (kode => label).
     */
    public function options(): JsonResponse
    {
        return $this->ok([
            'asset_conditions' => [
                ['value' => 'new', 'label' => 'Baru'],
                ['value' => 'used', 'label' => 'Bekas'],
            ],
            'asset_statuses' => [
                ['value' => 1, 'label' => 'Aktif'],
                ['value' => 0, 'label' => 'Tidak aktif'],
            ],
            'depreciation_methods' => [
                ['value' => 'straight_line', 'label' => 'Garis lurus'],
            ],
            'maintenance_types' => [
                ['value' => 'preventive', 'label' => 'Preventive'],
                ['value' => 'corrective', 'label' => 'Corrective'],
            ],
            'maintenance_triggers' => [
                ['value' => 'calendar', 'label' => 'Berdasarkan kalender'],
            ],
            'maintenance_interval_units' => [
                ['value' => 'day', 'label' => 'Hari'],
                ['value' => 'week', 'label' => 'Minggu'],
                ['value' => 'month', 'label' => 'Bulan'],
                ['value' => 'year', 'label' => 'Tahun'],
            ],
            'calendar_event_types' => [
                ['value' => 'meeting', 'label' => 'Meeting'],
                ['value' => 'announcement', 'label' => 'Pengumuman'],
            ],
            'upload_limits' => [
                'asset_photos_max' => 3,
                'asset_photo_max_kb' => 5120,
                'invoice_documents_max' => 5,
                'invoice_document_max_kb' => 10240,
                'request_photos_max' => 10,
            ],
        ]);
    }
}
