<script src="{{ asset('assets/vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>

{{--
    Semua teks dikirim ke JavaScript lewat @json agar aman dari XSS
    (tanda kutip, backtick, atau tag HTML di dalam pesan tidak dieksekusi).
--}}

@if(session('success'))
<script>
Swal.fire({
    icon: 'success',
    title: @json(__('Berhasil')),
    text: @json(session('success')),
    confirmButtonColor: '#3A57E8'
});
</script>
@endif

@if(session('error'))
<script>
Swal.fire({
    icon: 'error',
    title: @json(__('Oops...')),
    text: @json(session('error')),
    confirmButtonColor: '#dc3545'
});
</script>
@endif

@if(session('payment_error'))
<script>
Swal.fire({
    icon: 'error',
    title: @json(session('payment_error')['title'] ?? 'Pembayaran Belum Dapat Diproses'),
    text: @json(session('payment_error')['message'] ?? ''),
    confirmButtonText: @json(__('Mengerti')),
    confirmButtonColor: '#3A57E8'
});
</script>
@endif

@if(session('warning'))
<script>
Swal.fire({
    icon: 'warning',
    title: @json(__('Perhatian')),
    text: @json(session('warning'))
});
</script>
@endif

@if(session('info'))
<script>
Swal.fire({
    icon: 'info',
    title: @json(__('Informasi')),
    text: @json(session('info'))
});
</script>
@endif

@if ($errors->any())
<script>
Swal.fire({
    icon: 'error',
    title: @json(__('Validasi Gagal')),
    html: @json(implode('<br>', array_map('e', $errors->all())))
});
</script>
@endif
