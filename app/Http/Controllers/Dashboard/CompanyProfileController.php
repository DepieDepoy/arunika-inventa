<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\CompanyBranding;
use App\Services\CompanyStorage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Throwable;

/**
 * Profil perusahaan (khusus Administrator): nama, alamat, dan logo
 * yang tampil di laporan PDF / PowerPoint serta aplikasi mobile.
 */
class CompanyProfileController extends Controller
{
    public function edit(): View
    {
        $company = Auth::user()->company;

        return view('dashboard.company.edit', [
            'company' => $company,
            'logoUrl' => CompanyBranding::logoUrl($company),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $company = Auth::user()->company;

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048', 'dimensions:min_width=64,min_height=32,max_width=6000,max_height=6000'],
            'remove_logo' => ['nullable', 'boolean'],
        ], [
            'company_name.required' => __('Nama perusahaan wajib diisi.'),
            'logo.image' => __('Logo harus berupa gambar.'),
            'logo.mimes' => __('Logo harus berformat PNG, JPG, atau WEBP.'),
            'logo.max' => __('Ukuran logo maksimal 2 MB.'),
            'logo.dimensions' => __('Ukuran gambar logo minimal 64 x 32 piksel.'),
        ]);

        $company->company_name = trim($data['company_name']);
        $company->address = isset($data['address']) ? trim($data['address']) : null;
        $company->save();

        try {
            if ($request->hasFile('logo')) {
                $check = CompanyStorage::checkUpload($company->id, [$request->file('logo')]);
                if (!$check['allowed']) {
                    return back()->withErrors(['logo' => $check['message']])->withInput();
                }

                CompanyBranding::store($company, $request->file('logo'));
            } elseif ($request->boolean('remove_logo')) {
                CompanyBranding::remove($company);
            }
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['logo' => 'Logo gagal diproses. Coba gunakan file PNG atau JPG lain.'])->withInput();
        }

        return redirect()->route('company.edit')->with('success', __('Profil perusahaan berhasil disimpan.'));
    }
}
