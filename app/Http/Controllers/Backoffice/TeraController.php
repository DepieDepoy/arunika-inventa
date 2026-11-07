<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AI\GeminiClassifier;
use App\Services\Backoffice\BackofficeAudit;
use App\Services\Intelligence\Basic\TextNormalizer;
use App\Services\Intelligence\TeraSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan Tera AI (khusus Super Admin): aktif/nonaktif global, mode Dasar/Gemini,
 * dan aktif/nonaktif per perusahaan.
 */
class TeraController extends Controller
{
    public function edit(): View
    {
        $s = TeraSettings::all();

        return view('backoffice.settings.tera', [
            's' => $s,
            'hasKey' => (bool) TeraSettings::geminiKey(),
            'modes' => TeraSettings::MODES,
            'disabledCompanies' => Company::query()->where('ai_enabled', false)->orderBy('company_name')->get(['id', 'company_name']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'mode' => ['required', 'in:' . implode(',', array_keys(TeraSettings::MODES))],
            'gemini_model' => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9.\-]+$/'],
            'gemini_key' => ['nullable', 'string', 'max:200'],
            'clear_key' => ['nullable', 'boolean'],
        ], ['gemini_model.regex' => 'Nama model hanya huruf kecil, angka, titik, dan tanda minus.']);

        if ($data['mode'] === 'gemini' && empty($data['gemini_key']) && !TeraSettings::geminiKey()) {
            return back()->withErrors(['gemini_key' => 'Isi API key Gemini untuk memakai mode Gemini.'])->withInput();
        }

        $data['enabled'] = $request->boolean('enabled');
        $data['clear_key'] = $request->boolean('clear_key');
        TeraSettings::save($data, auth('backoffice')->id());

        BackofficeAudit::log('ai.settings', 'Tera AI ' . ($data['enabled'] ? 'aktif' : 'NONAKTIF') . ' · mode ' . $data['mode']
            . (!empty($data['gemini_key']) ? ' · API key Gemini diganti' : '') . ($data['clear_key'] ? ' · API key dihapus' : ''));

        return back()->with('success', 'Pengaturan Tera AI disimpan.');
    }

    /** Uji pemahaman kalimat (tanpa menjalankan query data pelanggan). */
    public function test(Request $request): JsonResponse
    {
        $q = (string) $request->validate(['question' => ['required', 'string', 'max:300']])['question'];

        $result = ['normalized' => TextNormalizer::normalize($q), 'gemini' => null];
        if (TeraSettings::mode() === 'gemini') {
            $result['gemini'] = app(GeminiClassifier::class)->classify($q);
        }

        return response()->json($result);
    }

    /** Aktif / nonaktifkan Tera untuk satu perusahaan (dari halaman Pelanggan). */
    public function toggleCompany(Company $company): RedirectResponse
    {
        $company->forceFill(['ai_enabled' => !$company->ai_enabled])->save();

        BackofficeAudit::log('ai.settings', 'Tera AI ' . ($company->ai_enabled ? 'diaktifkan' : 'dinonaktifkan') . ' untuk ' . $company->company_name);

        return back()->with('success', 'Tera AI ' . ($company->ai_enabled ? 'diaktifkan' : 'dinonaktifkan') . ' untuk ' . $company->company_name . '.');
    }
}
