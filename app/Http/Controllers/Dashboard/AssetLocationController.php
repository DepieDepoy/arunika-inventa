<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Asset\AssetLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Pindah lokasi aset. Lokasi tidak diubah lewat form edit aset agar setiap
 * perpindahan tercatat di riwayat (dari, ke, tanggal, oleh siapa).
 */
class AssetLocationController extends Controller
{
    public function __construct(
        protected AssetLifecycleService $lifecycle
    ) {
    }

    public function store(Request $request, string $id): RedirectResponse
    {
        $user = Auth::user();

        $asset = Asset::query()
            ->where('company_id', $user->company_id)
            ->findOrFail(decryptId($id));

        $validated = $request->validate([
            'location' => ['required', 'string', 'max:255'],
            'moved_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'location.required' => 'Lokasi baru wajib diisi.',
            'moved_at.required' => 'Tanggal pindah wajib diisi.',
        ]);

        try {
            $history = $this->lifecycle->moveLocation(
                $asset,
                $validated['location'],
                $validated['moved_at'],
                $validated['notes'] ?? null,
                $user
            );
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('assets.show', ['id' => $id, 'tab' => 'location'])
            ->with('success', 'Lokasi aset dipindahkan ke "' . $history->to_location . '".');
    }
}
