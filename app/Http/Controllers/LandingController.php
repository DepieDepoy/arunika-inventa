<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Landing\LandingContent;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Halaman depan publik, dua bahasa:
 *   /    → Bahasa Indonesia (utama)
 *   /en  → English
 *
 * - Harga & kuota paket dibaca dari tabel plans (sama dengan halaman Langganan).
 * - Teks & gambar dari CMS backoffice (LandingContent), per bahasa.
 * - Teks tetap (menu, tombol, mockup) diterjemahkan lewat lang/en.json.
 */
class LandingController extends Controller
{
    public function __invoke(LandingContent $landing, string $locale = LandingContent::DEFAULT_LOCALE): View
    {
        $locale = LandingContent::normalizeLocale($locale);
        App::setLocale($locale);

        $plans = Plan::query()
            ->where('status', 1)
            ->orderBy('price_monthly')
            ->get();

        $trial = $plans->firstWhere('plan_code', 'FREE');
        $trialDays = (int) ($trial?->trial_days ?: 7);
        $paidPlans = $plans->where('plan_code', '!=', 'FREE')->values();

        $content = $landing->all($trialDays, $locale);

        $recommendedCode = $content['pricing']['recommended_plan'] ?? '';

        // Paket rekomendasi diatur sekali (di versi Indonesia) dan berlaku untuk semua bahasa.
        if ($locale !== LandingContent::DEFAULT_LOCALE) {
            $recommendedCode = $landing->all($trialDays)['pricing']['recommended_plan'] ?? '';
        }

        if (!$paidPlans->contains('plan_code', $recommendedCode)) {
            $recommendedCode = $paidPlans->count() >= 2 ? $paidPlans[1]->plan_code : $paidPlans->first()?->plan_code;
        }

        return view('landing.index', [
            'c' => $content,
            'locale' => $locale,
            'trial' => $trial,
            'trialDays' => $trialDays,
            'paidPlans' => $paidPlans,
            'recommendedCode' => $recommendedCode,
        ]);
    }
}
