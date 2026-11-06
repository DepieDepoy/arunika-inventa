<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AddonRate;
use App\Models\Plan;
use App\Models\SiteContent;
use App\Services\Landing\LandingContent;
use App\Services\Backoffice\BackofficeAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Paket & harga — khusus pegawai Vasetra (backoffice).
 *
 * Perubahan harga hanya berlaku untuk pembelian berikutnya. Langganan yang
 * sedang berjalan tidak ikut berubah (harga tersimpan di tabel subscriptions).
 */
class PlanController extends Controller
{
    public function __construct(protected LandingContent $content)
    {
    }

    public function index(): View
    {
        $plans = Plan::query()->orderBy('price_monthly')->orderBy('id')->get();
        $storage = AddonRate::query()->where('type', 'storage')->first();
        $recommended = $this->content->section('pricing')['recommended_plan'] ?? '';

        $activeSubscriptions = DB::table('subscriptions')
            ->where('status', 'active')
            ->whereDate('end_date', '>=', today())
            ->select('plan_id', DB::raw('COUNT(*) AS total'))
            ->groupBy('plan_id')
            ->pluck('total', 'plan_id');

        return view('backoffice.plans.index', compact('plans', 'storage', 'recommended', 'activeSubscriptions'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plans' => ['required', 'array'],
            'plans.*.plan_name' => ['required', 'string', 'max:100'],
            'plans.*.description' => ['nullable', 'string', 'max:255'],
            'plans.*.description_en' => ['nullable', 'string', 'max:255'],
            'plans.*.price_monthly' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'plans.*.price_yearly' => ['required', 'integer', 'min:0', 'max:10000000000'],
            'plans.*.max_users' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'plans.*.max_assets' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'plans.*.storage_mb' => ['required', 'integer', 'min:100', 'max:10485760'],
            'plans.*.trial_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'plans.*.status' => ['nullable', 'in:0,1'],
            'recommended_plan' => ['nullable', 'string', 'max:50'],
            'storage.unit_mb' => ['required', 'integer', 'min:64', 'max:102400'],
            'storage.price_monthly' => ['required', 'integer', 'min:0', 'max:100000000'],
            'storage.price_yearly' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'storage.max_units' => ['required', 'integer', 'min:1', 'max:1000'],
            'storage.is_active' => ['nullable', 'in:0,1'],
        ], [
            'plans.*.plan_name.required' => 'Nama paket wajib diisi.',
            'plans.*.price_monthly.required' => 'Harga bulanan wajib diisi.',
            'plans.*.price_yearly.required' => 'Harga tahunan wajib diisi.',
            'plans.*.storage_mb.required' => 'Kuota storage wajib diisi.',
        ]);

        $changes = [];

        DB::transaction(function () use ($validated, &$changes) {
            foreach ($validated['plans'] as $id => $data) {
                $plan = Plan::query()->find($id);

                if (!$plan) {
                    continue;
                }

                $isFree = strtoupper($plan->plan_code) === 'FREE';

                $plan->fill([
                    'plan_name' => $data['plan_name'],
                    'description' => $data['description'] ?? null,
                    'description_en' => $data['description_en'] ?? null,
                    'price_monthly' => $isFree ? 0 : $data['price_monthly'],
                    'price_yearly' => $isFree ? 0 : $data['price_yearly'],
                    'max_users' => $data['max_users'] ?? null,
                    'max_assets' => $data['max_assets'] ?? null,
                    'max_storage_bytes' => (int) $data['storage_mb'] * 1048576,
                    'status' => $isFree ? 1 : (int) ($data['status'] ?? 0),
                ]);

                if ($isFree && !empty($data['trial_days'])) {
                    $plan->trial_days = (int) $data['trial_days'];
                }

                if ($plan->isDirty()) {
                    $changes[$plan->plan_code] = array_keys($plan->getDirty());
                    $plan->save();
                }
            }

            $storage = AddonRate::query()->where('type', 'storage')->first();

            if ($storage) {
                $mb = (int) $validated['storage']['unit_mb'];
                $storage->fill([
                    'unit_value' => $mb * 1024 * 1024,
                    'unit_label' => $mb >= 1024 && $mb % 1024 === 0 ? ($mb / 1024) . ' GB' : $mb . ' MB',
                    'price_monthly' => $validated['storage']['price_monthly'],
                    'price_yearly' => $validated['storage']['price_yearly'],
                    'max_units' => $validated['storage']['max_units'],
                    'is_active' => (int) ($validated['storage']['is_active'] ?? 0),
                ]);

                if ($storage->isDirty()) {
                    $changes['ADDON_STORAGE'] = array_keys($storage->getDirty());
                    $storage->save();
                }
            }
        });

        // Paket rekomendasi di landing.
        $pricing = SiteContent::query()->firstOrNew(['key' => 'pricing', 'locale' => LandingContent::DEFAULT_LOCALE]);
        $value = $pricing->exists ? (array) $pricing->value : $this->content->section('pricing');
        $recommended = (string) ($validated['recommended_plan'] ?? '');

        if (($value['recommended_plan'] ?? '') !== $recommended) {
            $value['recommended_plan'] = $recommended;
            $pricing->value = $value;
            $pricing->updated_by = auth('backoffice')->id();
            $pricing->save();
            $changes['RECOMMENDED'] = [$recommended ?: '(otomatis)'];
        }

        $this->content->forget();

        if ($changes) {
            BackofficeAudit::log('pricing_updated', 'Mengubah paket & harga: ' . implode(', ', array_keys($changes)), null, $changes);
        }

        return redirect()
            ->route('backoffice.plans.index')
            ->with('success', $changes
                ? 'Paket & harga disimpan. Harga baru berlaku untuk pembelian berikutnya.'
                : 'Tidak ada perubahan.');
    }
}
