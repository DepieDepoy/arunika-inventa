{{-- Hapus akun sendiri (wajib untuk aplikasi di Play Store / App Store, juga tersedia di web). --}}
@php $isLastAdmin = app(\App\Services\Account\AccountDeletionService::class)->isLastAdministrator(auth()->user()); @endphp
<div class="row">
    <div class="col-12 mb-4">
        <div class="card border-danger-subtle" style="border:1px solid rgba(220,38,38,.25)">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 text-danger"><i class="bi bi-exclamation-octagon me-1"></i> {{ __('Hapus akun') }}</h5>
                    <small class="text-muted">
                        @if($isLastAdmin)
                            Anda administrator terakhir. Permintaan akan diproses sebagai <b>{{ __('penutupan akun perusahaan') }}</b> oleh tim Vasetra (maks. 30 hari).
                        @else
                            Nama, email, nomor HP, NIK, dan foto Anda dihapus permanen, dan akun tidak bisa dipakai lagi. Riwayat aset perusahaan tetap tersimpan tanpa data pribadi Anda.
                        @endif
                    </small>
                </div>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="collapse" data-bs-target="#delAccountForm">{{ __('Hapus akun saya') }}</button>
            </div>
            <div class="collapse" id="delAccountForm">
                <div class="card-body">
                    <form id="deleteAccountForm" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Password') }}</label>
                            <input type="password" name="password" class="form-control" autocomplete="current-password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Ketik') }} <b>{{ __('HAPUS') }}</b> {{ __('untuk konfirmasi') }}</label>
                            <input type="text" name="confirm_text" class="form-control" autocomplete="off" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">{{ __('Alasan') }} <span class="text-muted">{{ __('(opsional)') }}</span></label>
                            <input type="text" name="reason" class="form-control" maxlength="1000">
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <small class="text-muted">{{ __('Lihat juga') }} <a href="{{ route('legal.privacy') }}" target="_blank">{{ __('Kebijakan Privasi') }}</a>.</small>
                            <button type="submit" class="btn btn-danger" id="deleteAccountBtn">{{ $isLastAdmin ? 'Ajukan penutupan perusahaan' : 'Hapus akun permanen' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('deleteAccountForm');
    if (!form) return;
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = document.getElementById('deleteAccountBtn');
        Swal.fire({
            icon: 'warning',
            title: @json(__('Yakin hapus akun?')),
            text: @json(__('Tindakan ini tidak bisa dibatalkan.')),
            showCancelButton: true,
            confirmButtonText: @json(__('Ya, hapus')),
            cancelButtonText: @json(__('Batal')),
            confirmButtonColor: '#dc2626',
            reverseButtons: true
        }).then(function (r) {
            if (!r.isConfirmed) return;
            btn.disabled = true;
            fetch(@json(route('profile.delete-account')), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                body: new FormData(form)
            }).then(function (res) { return res.json().then(function (j) { return { ok: res.ok, j: j }; }); })
              .then(function (x) {
                  if (!x.ok) {
                      var first = x.j.errors ? Object.values(x.j.errors)[0][0] : (x.j.message || 'Gagal memproses.');
                      Swal.fire({ icon: 'error', title: @json(__('Tidak dapat diproses')), text: first });
                      return;
                  }
                  Swal.fire({ icon: 'success', title: @json(__('Berhasil')), text: x.j.message }).then(function () {
                      if (x.j.redirect) window.location.href = x.j.redirect;
                  });
              })
              .catch(function () { Swal.fire({ icon: 'error', title: @json(__('Gagal')), text: @json(__('Koneksi bermasalah.')) }); })
              .finally(function () { btn.disabled = false; });
        });
    });
});
</script>
