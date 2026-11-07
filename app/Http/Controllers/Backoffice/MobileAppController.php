<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\WebPushSubscription;
use App\Services\Backoffice\BackofficeAudit;
use App\Services\Mobile\MobileAppSettings;
use App\Services\Push\FcmSender;
use App\Services\Push\WebPushSender;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pengaturan aplikasi mobile: wajib update, versi terbaru, tautan toko, pemeliharaan,
 * plus status notifikasi push (khusus Super Admin).
 */
class MobileAppController extends Controller
{
    public function edit(): View
    {
        return view('backoffice.settings.mobile', [
            's' => MobileAppSettings::all(),
            'push' => [
                'web' => WebPushSender::configured(),
                'fcm' => FcmSender::configured(),
                'web_on' => \App\Services\Push\PushSettings::webEnabled(),
                'mobile_on' => \App\Services\Push\PushSettings::mobileEnabled(),
                'browsers' => WebPushSubscription::count(),
                'devices' => DeviceToken::query()->selectRaw('platform, COUNT(*) as n')->groupBy('platform')->pluck('n', 'platform'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $version = ['required', 'regex:/^\d+\.\d+(\.\d+)?$/'];
        $data = $request->validate([
            'android_min' => $version,
            'android_latest' => $version,
            'android_store_url' => ['nullable', 'url', 'max:500'],
            'ios_min' => $version,
            'ios_latest' => $version,
            'ios_store_url' => ['nullable', 'url', 'max:500'],
            'update_message' => ['required', 'string', 'max:300'],
            'maintenance' => ['nullable', 'boolean'],
            'maintenance_message' => ['required', 'string', 'max:300'],
        ], [
            '*.regex' => 'Format versi harus seperti 1.0.0',
        ]);

        foreach (MobileAppSettings::PLATFORMS as $p) {
            if (version_compare($data["{$p}_min"], $data["{$p}_latest"], '>')) {
                return back()->withErrors([$p . '_min' => 'Versi minimal ' . strtoupper($p) . ' tidak boleh lebih tinggi dari versi terbaru.'])->withInput();
            }
        }

        $data['maintenance'] = $request->boolean('maintenance');
        $old = MobileAppSettings::all();
        MobileAppSettings::save($data, auth('backoffice')->id());

        BackofficeAudit::log('mobile.settings', 'Mengubah pengaturan aplikasi mobile' . ($data['maintenance'] ? ' (mode pemeliharaan AKTIF)' : ''), $old, $data);

        return back()->with('success', 'Pengaturan aplikasi mobile disimpan.');
    }

    /**
     * Saklar notifikasi push browser & aplikasi mobile.
     */
    public function updatePush(Request $request): RedirectResponse
    {
        $web = $request->boolean('web_enabled');
        $mobile = $request->boolean('mobile_enabled');

        \App\Services\Push\PushSettings::save($web, $mobile, auth('backoffice')->id());

        BackofficeAudit::log('push.settings', 'Notifikasi browser ' . ($web ? 'AKTIF' : 'NONAKTIF') . ' · notifikasi aplikasi mobile ' . ($mobile ? 'AKTIF' : 'NONAKTIF'));

        return back()->with('success', 'Pengaturan notifikasi disimpan.');
    }
}
