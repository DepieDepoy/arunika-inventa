<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@if(session('success'))
<script>
Swal.fire({
    icon: 'success',
    title: 'Berhasil',
    text: "{{ session('success') }}",
    confirmButtonColor: '#3A57E8'
});
</script>
@endif

@if(session('error'))
<script>
Swal.fire({
    icon: 'error',
    title: 'Oops...',
    text: "{{ session('error') }}",
    confirmButtonColor: '#dc3545'
});
</script>
@endif

@if(session('warning'))
<script>
Swal.fire({
    icon: 'warning',
    title: 'Perhatian',
    text: "{{ session('warning') }}"
});
</script>
@endif

@if(session('info'))
<script>
Swal.fire({
    icon: 'info',
    title: 'Informasi',
    text: "{{ session('info') }}"
});
</script>
@endif

@if ($errors->any())
<script>
Swal.fire({
    icon: 'error',
    title: 'Validasi Gagal',
    html: `{!! implode('<br>', $errors->all()) !!}`
});
</script>
@endif