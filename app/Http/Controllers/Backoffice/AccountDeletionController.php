<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\Account\AccountDeletionService;
use App\Services\Backoffice\BackofficeAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Daftar & proses permintaan hapus akun / data (khusus Super Admin).
 */
class AccountDeletionController extends Controller
{
    public function index(Request $request): View
    {
        $status = array_key_exists((string) $request->query('status'), AccountDeletionRequest::STATUSES) ? $request->query('status') : 'pending';

        return view('backoffice.account-deletions.index', [
            'requests' => AccountDeletionRequest::query()
                ->with(['company:id,company_name,status', 'processor:id,name'])
                ->where('status', $status)
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
            'status' => $status,
            'counts' => AccountDeletionRequest::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function process(Request $request, AccountDeletionRequest $deletion, AccountDeletionService $service): RedirectResponse
    {
        abort_unless($deletion->status === 'pending', 422, 'Permintaan sudah diproses.');

        $note = $request->validate(['note' => ['nullable', 'string', 'max:500']])['note'] ?? null;

        if (!$deletion->user_id && !$deletion->company_id) {
            $deletion->forceFill(['status' => 'done', 'processed_by' => auth('backoffice')->id(), 'processed_at' => now(), 'note' => $note ?: 'Email tidak terdaftar di Vasetra.'])->save();
        } else {
            $service->process($deletion, auth('backoffice')->id(), $note);
        }

        BackofficeAudit::log('account.deletion_processed', "Memproses hapus akun #{$deletion->id} ({$deletion->email}, " . AccountDeletionRequest::SCOPES[$deletion->scope] . ')');

        return back()->with('success', 'Permintaan hapus akun selesai diproses. Data pribadi sudah dihapus/dianonimkan.');
    }

    public function reject(Request $request, AccountDeletionRequest $deletion): RedirectResponse
    {
        abort_unless($deletion->status === 'pending', 422, 'Permintaan sudah diproses.');

        $note = $request->validate(['note' => ['required', 'string', 'max:500']], ['note.required' => 'Alasan penolakan wajib diisi.'])['note'];
        $deletion->forceFill(['status' => 'rejected', 'processed_by' => auth('backoffice')->id(), 'processed_at' => now(), 'note' => $note])->save();

        BackofficeAudit::log('account.deletion_rejected', "Menolak hapus akun #{$deletion->id} ({$deletion->email}): {$note}");

        return back()->with('success', 'Permintaan ditolak.');
    }
}
