{{-- Tombol aktif/nonaktif notifikasi browser (di dropdown lonceng). Hanya muncul bila kunci VAPID sudah diatur. --}}
@if(\App\Services\Push\WebPushSender::active())
<div class="border-top px-3 py-2 d-flex align-items-center justify-content-between gap-2" id="wpBox" hidden>
    <div class="small">
        <i class="fa-solid fa-desktop me-1 text-primary"></i>
        <span id="wpText">Notifikasi browser</span>
    </div>
    <div class="d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2" id="wpTest" hidden>Uji</button>
        <button type="button" class="btn btn-sm btn-primary py-0 px-2" id="wpToggle">Aktifkan</button>
    </div>
</div>
<script>
(function () {
    var box = document.getElementById('wpBox');
    if (!box || !('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) return;
    box.hidden = false;

    var KEY = @json(config('push.webpush.public_key'));
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || @json(csrf_token());
    var btn = document.getElementById('wpToggle');
    var txt = document.getElementById('wpText');
    var testBtn = document.getElementById('wpTest');
    var reg = null;

    var b64 = function (s) {
        var pad = '='.repeat((4 - s.length % 4) % 4);
        var raw = atob((s + pad).replace(/-/g, '+').replace(/_/g, '/'));
        var out = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) out[i] = raw.charCodeAt(i);
        return out;
    };
    var post = function (url, body) {
        return fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body || {}) });
    };
    var render = function (on) {
        if (Notification.permission === 'denied') {
            txt.textContent = 'Notifikasi diblokir browser';
            btn.textContent = 'Cara buka';
            btn.dataset.state = 'denied';
            testBtn.hidden = true;
            return;
        }
        txt.textContent = on ? 'Notifikasi browser aktif' : 'Notifikasi browser';
        btn.textContent = on ? 'Matikan' : 'Aktifkan';
        btn.className = 'btn btn-sm py-0 px-2 ' + (on ? 'btn-outline-danger' : 'btn-primary');
        btn.dataset.state = on ? 'on' : 'off';
        testBtn.hidden = !on;
    };

    navigator.serviceWorker.register('/sw-push.js').then(function (r) {
        reg = r;
        return r.pushManager.getSubscription();
    }).then(function (sub) {
        render(!!sub);
        // Sinkronkan ulang ke server (mis. setelah login di akun lain pada browser yang sama).
        if (sub) post(@json(route('push.subscribe')), sub.toJSON());
    }).catch(function () { box.hidden = true; });

    // Jangan tutup dropdown saat tombol diklik.
    box.addEventListener('click', function (e) { e.stopPropagation(); });

    btn.addEventListener('click', function () {
        if (!reg) return;
        var state = btn.dataset.state;
        if (state === 'denied') {
            Swal.fire({ icon: 'info', title: 'Notifikasi diblokir', html: 'Klik ikon gembok di sebelah alamat website, lalu ubah <b>Notifikasi</b> menjadi <b>Izinkan</b>.' });
            return;
        }
        btn.disabled = true;
        if (state === 'on') {
            reg.pushManager.getSubscription().then(function (sub) {
                if (!sub) return render(false);
                return post(@json(route('push.unsubscribe')), { endpoint: sub.endpoint }).then(function () { return sub.unsubscribe(); }).then(function () { render(false); });
            }).finally(function () { btn.disabled = false; });
            return;
        }
        Notification.requestPermission().then(function (perm) {
            if (perm !== 'granted') { render(false); return; }
            return reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64(KEY) }).then(function (sub) {
                return post(@json(route('push.subscribe')), sub.toJSON()).then(function (res) {
                    if (!res.ok) throw new Error('save');
                    render(true);
                    return post(@json(route('push.test')));
                });
            });
        }).catch(function () {
            Swal.fire({ icon: 'error', title: 'Gagal mengaktifkan', text: 'Browser ini belum mendukung notifikasi atau koneksi bermasalah.' });
        }).finally(function () { btn.disabled = false; });
    });

    testBtn.addEventListener('click', function () {
        testBtn.disabled = true;
        post(@json(route('push.test'))).finally(function () { setTimeout(function () { testBtn.disabled = false; }, 3000); });
    });
})();
</script>
@endif
