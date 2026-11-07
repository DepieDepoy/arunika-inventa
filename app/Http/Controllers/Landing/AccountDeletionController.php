<?php

namespace App\Http\Controllers\Landing;

use App\Http\Controllers\Controller;
use App\Services\Account\AccountDeletionService;
use App\Services\Landing\LandingContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

/**
 * Formulir publik permintaan hapus akun & data (URL "Data deletion" di Google Play).
 */
class AccountDeletionController extends Controller
{
    public function create(LandingContent $landing, string $locale = 'id'): View
    {
        $locale = LandingContent::normalizeLocale($locale);
        App::setLocale($locale);

        return view('landing.delete-account', [
            'locale' => $locale,
            'footer' => $landing->section('footer', $locale),
        ]);
    }

    public function store(Request $request, AccountDeletionService $service): RedirectResponse
    {
        // Honeypot: bot biasanya mengisi semua kolom.
        if (filled($request->input('website'))) {
            return back()->with('submitted', true);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['accepted'],
        ], [
            'email.required' => __('Email wajib diisi.'),
            'email.email' => __('Format email tidak valid.'),
            'confirm.accepted' => __('Centang pernyataan persetujuan terlebih dahulu.'),
        ]);

        $service->requestFromForm($data['email'], $data['reason'] ?? null, $request->ip());

        // Pesan sama untuk email terdaftar maupun tidak (tidak membocorkan data).
        return back()->with('submitted', true);
    }
}
