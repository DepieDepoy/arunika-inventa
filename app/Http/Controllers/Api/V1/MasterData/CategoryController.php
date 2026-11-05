<?php

namespace App\Http\Controllers\Api\V1\MasterData;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Controllers\Dashboard\CategoryController as WebCategoryController;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Kelola kategori & subkategori aset.
 *
 * Simpan/ubah/hapus memakai controller web (kode otomatis CAT-01, SUBCAT-01,
 * cek duplikat nama) agar hasilnya identik dengan dashboard.
 * Untuk dropdown form cukup gunakan GET /lookups/categories (hanya yang aktif).
 */
class CategoryController extends ApiController
{
    /**
     * Query: search, status (0|1), per_page.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::query()
            ->where('company_id', $request->user()->company_id)
            ->withCount(['subCategories', 'assets'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->integer('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . trim((string) $request->query('search')) . '%';
                $q->where(fn ($w) => $w->where('category_name', 'like', $term)->orWhere('category_code', 'like', $term));
            })
            ->orderBy('category_name');

        return $this->paginated('categories', $query->paginate($this->perPage($request)), fn (Category $c) => $this->format($c));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $category = $this->findCategory($request, $id)->loadCount(['subCategories', 'assets']);

        $subCategories = SubCategory::query()
            ->where('company_id', $request->user()->company_id)
            ->where('category_id', $category->id)
            ->withCount('assets')
            ->orderBy('sub_category_name')
            ->get()
            ->map(fn (SubCategory $s) => $this->formatSub($s));

        return $this->ok([
            'category' => $this->format($category),
            'sub_categories' => $subCategories,
        ]);
    }

    /**
     * Body: category_name, status (0|1), description (opsional).
     */
    public function store(Request $request): JsonResponse
    {
        $response = $this->delegate(fn () => app(WebCategoryController::class)->store($request), 'CATEGORY_CREATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        $category = Category::query()
            ->where('company_id', $request->user()->company_id)
            ->latest('id')
            ->first()
            ?->loadCount(['subCategories', 'assets']);

        return $this->ok(['category' => $category ? $this->format($category) : null], 'Kategori berhasil ditambahkan.', 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = $this->findCategory($request, $id);
        $request->merge(['id' => $category->id]);

        $response = $this->delegate(fn () => app(WebCategoryController::class)->update($request), 'CATEGORY_UPDATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        return $this->ok(['category' => $this->format($category->refresh()->loadCount(['subCategories', 'assets']))], 'Kategori berhasil diperbarui.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = $this->findCategory($request, $id);

        if ($count = $category->assets()->count()) {
            return $this->fail("Kategori masih dipakai oleh {$count} aset.", 'IN_USE');
        }

        return $this->delegate(fn () => app(WebCategoryController::class)->destroy($category->id), 'CATEGORY_DELETE_FAILED');
    }

    /**
     * Body: sub_category_name, status (0|1), description (opsional).
     */
    public function storeSub(Request $request, int $categoryId): JsonResponse
    {
        $category = $this->findCategory($request, $categoryId);
        $request->merge(['category_id' => $category->id]);

        $response = $this->delegate(fn () => app(WebCategoryController::class)->storeSubCategory($request), 'SUBCATEGORY_CREATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        $sub = SubCategory::query()
            ->where('company_id', $request->user()->company_id)
            ->where('category_id', $category->id)
            ->latest('id')
            ->first()
            ?->loadCount('assets');

        return $this->ok(['sub_category' => $sub ? $this->formatSub($sub) : null], 'Subkategori berhasil ditambahkan.', 201);
    }

    /**
     * Body: sub_category_name, status (0|1), description, category_id (opsional, pindah kategori).
     */
    public function updateSub(Request $request, int $id): JsonResponse
    {
        $sub = $this->findSub($request, $id);

        // Kategori tujuan wajib milik perusahaan yang sama.
        $categoryId = $request->filled('category_id')
            ? $this->findCategory($request, $request->integer('category_id'))->id
            : $sub->category_id;

        $request->merge(['id' => $sub->id, 'category_id' => $categoryId]);

        $response = $this->delegate(fn () => app(WebCategoryController::class)->updateSubCategory($request), 'SUBCATEGORY_UPDATE_FAILED');

        if (!$this->succeeded($response)) {
            return $response;
        }

        return $this->ok(['sub_category' => $this->formatSub($sub->refresh()->loadCount('assets'))], 'Subkategori berhasil diperbarui.');
    }

    public function destroySub(Request $request, int $id): JsonResponse
    {
        $sub = $this->findSub($request, $id);

        if ($count = $sub->assets()->count()) {
            return $this->fail("Subkategori masih dipakai oleh {$count} aset.", 'IN_USE');
        }

        return $this->delegate(fn () => app(WebCategoryController::class)->destroySubCategory($sub->id), 'SUBCATEGORY_DELETE_FAILED');
    }

    protected function findCategory(Request $request, int $id): Category
    {
        return Category::query()->where('company_id', $request->user()->company_id)->findOrFail($id);
    }

    protected function findSub(Request $request, int $id): SubCategory
    {
        return SubCategory::query()->where('company_id', $request->user()->company_id)->findOrFail($id);
    }

    protected function format(Category $c): array
    {
        return [
            'id' => $c->id,
            'code' => $c->category_code,
            'name' => $c->category_name,
            'description' => $c->description,
            'status' => (int) $c->status,
            'sub_categories_count' => $c->sub_categories_count ?? null,
            'assets_count' => $c->assets_count ?? null,
        ];
    }

    protected function formatSub(SubCategory $s): array
    {
        return [
            'id' => $s->id,
            'category_id' => $s->category_id,
            'code' => $s->sub_category_code,
            'name' => $s->sub_category_name,
            'description' => $s->description,
            'status' => (int) $s->status,
            'assets_count' => $s->assets_count ?? null,
        ];
    }
}
