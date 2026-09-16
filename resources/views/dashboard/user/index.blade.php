@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')

<style>
.swal2-container {
    z-index: 99999 !important;
}

.table-responsive {
    overflow: visible !important;
}

.dropdown-menu {
    z-index: 99999 !important;
}
</style>

<div class="content-wrapper">

    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">

            <div class="col-xxl-12 mb-12 order-0">

                <div class="card">

                    {{-- =====================================================
                         CARD HEADER
                    ====================================================== --}}
                    <div class="card-header d-flex justify-content-between align-items-center">

                        <div>
                            <h5 class="mb-0">
                                Users
                            </h5>

                            <small class="text-muted">
                                Manage application users
                            </small>
                        </div>

                        <div class="d-flex gap-2">

                            @if(auth()->user()->hasPermission('users.view'))

                                <a href="{{ route('users.export') }}"
                                   class="btn btn-success">

                                    <i class="fa-solid fa-file-excel"></i>

                                    Export Excel

                                </a>

                            @endif


                            @if(auth()->user()->hasPermission('users.create'))

                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalAddUser">

                                    <i class="fa-solid fa-plus"></i>

                                    Add Users

                                </button>

                            @endif

                        </div>

                    </div>


                    {{-- =====================================================
                         TABLE
                    ====================================================== --}}
                    <div class="card-body">

                        <div class="table-responsive user-table-wrapper">

                            <table
                                class="table table-hover align-middle"
                                id="table-users"
                                width="100%">

                                <thead>

                                    <tr>

                                        <th width="5%">
                                            No
                                        </th>

                                        <th>
                                            Name
                                        </th>

                                        <th>
                                            ID Person
                                        </th>

                                        <th>
                                            Email
                                        </th>

                                        <th>
                                            Phone
                                        </th>

                                        <th>
                                            Companies
                                        </th>

                                        <th>
                                            Role
                                        </th>

                                        <th>
                                            Status
                                        </th>

                                        <th width="10%">
                                            Action
                                        </th>

                                    </tr>

                                </thead>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     MODAL ADD USER
========================================================= --}}
<div
    class="modal fade"
    id="modalAddUser"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <form id="formAddUser">

            @csrf

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Add User
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row">

                        {{-- NAME --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                class="form-control"
                                id="name"
                                required>

                        </div>


                        {{-- NIK --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                ID Person
                            </label>

                            <input
                                type="text"
                                name="nik"
                                class="form-control"
                                id="nik"
                                required>

                        </div>


                        {{-- EMAIL --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                required>

                        </div>


                        {{-- PHONE --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Phone
                            </label>

                            <input
                                type="number"
                                name="phone"
                                id="phone"
                                class="form-control"
                                required>

                        </div>


                        {{-- ROLE --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Role
                            </label>

                            <select
                                name="role_id"
                                id="add_role"
                                class="form-select"
                                required>

                                @foreach($roles as $role)

                                    <option value="{{ $role->id }}">
                                        {{ $role->role_name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- STATUS --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                id="add_status"
                                class="form-select">

                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-label-secondary"
                        data-bs-dismiss="modal">

                        Close

                    </button>


                    <button
                        type="submit"
                        id="btnSaveUser"
                        class="btn btn-primary">

                        <span class="btn-save-text">
                            Save User
                        </span>

                        <span
                            class="btn-save-loading d-none">

                            <span
                                class="spinner-border spinner-border-sm me-1"
                                role="status">
                            </span>

                            Saving...

                        </span>

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>


{{-- =========================================================
     MODAL EDIT USER
========================================================= --}}
<div
    class="modal fade"
    id="modalEditUser"
    tabindex="-1"
    aria-hidden="true">

    <div class="modal-dialog modal-lg">

        <form id="formEditUser">

            @csrf

            <input
                type="hidden"
                name="id"
                id="edit_id">


            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Edit User
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>


                <div class="modal-body">

                    <div class="row">

                        {{-- NAME --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Name
                            </label>

                            <input
                                type="text"
                                name="name"
                                id="edit_name"
                                class="form-control"
                                required>

                        </div>


                        {{-- NIK --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                ID Person
                            </label>

                            <input
                                type="text"
                                name="nik"
                                id="edit_nik"
                                class="form-control"
                                required>

                        </div>


                        {{-- EMAIL --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Email
                            </label>

                            <input
                                type="email"
                                name="email"
                                id="edit_email"
                                class="form-control"
                                readonly>

                        </div>


                        {{-- PHONE --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Phone
                            </label>

                            <input
                                type="number"
                                name="phone"
                                id="edit_phone"
                                class="form-control"
                                required>

                        </div>


                        {{-- ROLE --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Role
                            </label>

                            <select
                                name="role_id"
                                id="edit_role"
                                class="form-select"
                                required>

                                @foreach($roles as $role)

                                    <option value="{{ $role->id }}">
                                        {{ $role->role_name }}
                                    </option>

                                @endforeach

                            </select>

                        </div>


                        {{-- STATUS --}}
                        <div class="col-md-6 mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select
                                name="status"
                                id="edit_status"
                                class="form-select">

                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-label-secondary"
                        data-bs-dismiss="modal">

                        Close

                    </button>


                    <button
                        type="submit"
                        id="btnUpdateUser"
                        class="btn btn-primary">

                        <span class="btn-update-text">
                            Save User
                        </span>

                        <span
                            class="btn-update-loading d-none">

                            <span
                                class="spinner-border spinner-border-sm me-1"
                                role="status">
                            </span>

                            Saving...

                        </span>

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

@endsection


@section('js')

<script>

$(function () {


    /* =====================================================
       DATATABLE
    ====================================================== */

    $('#table-users').DataTable({

        processing: true,

        serverSide: true,

        responsive: true,

        autoWidth: false,

        ajax: "{{ route('users.data') }}",

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },

            {
                data: 'name',
                name: 'name'
            },

            {
                data: 'nik',
                name: 'nik'
            },

            {
                data: 'email',
                name: 'email'
            },

            {
                data: 'phone',
                name: 'phone',
                className: 'text-start'
            },

            {
                data: 'company_name',
                name: 'company_name'
            },

            {
                data: 'role_name',
                name: 'role_name'
            },

            {
                data: 'status',
                name: 'status',
                orderable: false
            },

            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }

        ],

        order: [
            [2, 'asc']
        ]

    });


    /* =====================================================
       ADD USER
    ====================================================== */

    $('#formAddUser').on('submit', function (e) {

        e.preventDefault();

        let form = this;

        if (!form.checkValidity()) {

            form.reportValidity();

            return false;

        }

        let formData = new FormData(form);

        let button = $('#btnSaveUser');


        /*
        |--------------------------------------------------------------------------
        | DISABLE BUTTON
        |--------------------------------------------------------------------------
        */

        button.prop('disabled', true);

        button.find('.btn-save-text')
            .addClass('d-none');

        button.find('.btn-save-loading')
            .removeClass('d-none');


        $.ajax({

            url: "{{ route('users.store') }}",

            type: "POST",

            data: formData,

            processData: false,

            contentType: false,

            headers: {

                'Accept': 'application/json',

                'X-Requested-With':
                    'XMLHttpRequest'

            },


            success: function (response) {


                if (response.success) {


                    Swal.fire({

                        icon: 'success',

                        title: 'Berhasil',

                        html: `

                            <div class="text-start">

                                <p class="mb-2">
                                    User berhasil ditambahkan.
                                </p>

                                <label class="fw-bold">
                                    Password sementara
                                </label>

                                <div class="input-group mb-3">

                                    <input
                                        type="text"
                                        id="tempPassword"
                                        class="form-control"
                                        value="${escapeHtml(response.password ?? '')}"
                                        readonly>

                                    <button
                                        class="btn btn-primary"
                                        type="button"
                                        onclick="copyPassword()">

                                        <i class="fa-solid fa-copy"></i>

                                    </button>

                                </div>

                                <small class="text-danger">
                                    Simpan password ini sebelum menutup pesan.
                                </small>

                            </div>

                        `,

                        confirmButtonText:
                            'OK'

                    });


                    let modal =
                        bootstrap.Modal.getInstance(
                            document.getElementById(
                                'modalAddUser'
                            )
                        );


                    if (modal) {

                        modal.hide();

                    }


                    form.reset();


                    $('#table-users')
                        .DataTable()
                        .ajax
                        .reload(
                            null,
                            false
                        );


                } else {


                    Swal.fire({

                        icon: 'error',

                        title: 'Gagal',

                        text:
                            response.message ||
                            'User gagal ditambahkan.'

                    });

                }

            },


            error: function (xhr) {

                console.log(
                    'ADD USER ERROR:',
                    xhr
                );

                showAjaxError(
                    xhr,
                    'User gagal ditambahkan.'
                );

            },


            complete: function () {

                button.prop(
                    'disabled',
                    false
                );

                button.find(
                    '.btn-save-text'
                ).removeClass('d-none');

                button.find(
                    '.btn-save-loading'
                ).addClass('d-none');

            }

        });

    });


    /* =====================================================
       EDIT USER
    ====================================================== */

    $(document).on(
        'click',
        '.btn-edit',
        function () {


            let id =
                $(this).data('id');


            let url =
                "{{ route('users.edit', ['id' => ':id']) }}";


            url =
                url.replace(
                    ':id',
                    id
                );


            $.ajax({

                url: url,

                type: 'GET',

                headers: {

                    'Accept':
                        'application/json',

                    'X-Requested-With':
                        'XMLHttpRequest'

                },


                success: function (response) {


                    $('#edit_id')
                        .val(response.id);

                    $('#edit_name')
                        .val(response.name);

                    $('#edit_nik')
                        .val(response.nik);

                    $('#edit_email')
                        .val(response.email);

                    $('#edit_phone')
                        .val(response.phone);

                    $('#edit_status')
                        .val(response.status);


                    if (
                        response.role_id !== undefined &&
                        response.role_id !== null
                    ) {

                        $('#edit_role')
                            .val(
                                String(
                                    response.role_id
                                )
                            );

                    } else {

                        console.warn(
                            'role_id tidak ditemukan pada response users.edit'
                        );

                        $('#edit_role')
                            .val('');

                    }


                    let modal =
                        new bootstrap.Modal(
                            document.getElementById(
                                'modalEditUser'
                            )
                        );


                    modal.show();

                },


                error: function (xhr) {

                    console.log(
                        'GET USER ERROR:',
                        xhr
                    );

                    showAjaxError(
                        xhr,
                        'Data user gagal dimuat.'
                    );

                }

            });

        }
    );


    /* =====================================================
       UPDATE USER
    ====================================================== */

    $('#formEditUser').on('submit', function (e) {

        e.preventDefault();


        let form = this;


        if (!form.checkValidity()) {

            form.reportValidity();

            return false;

        }


        let formData =
            new FormData(form);


        let button =
            $('#btnUpdateUser');


        button.prop(
            'disabled',
            true
        );

        button.find(
            '.btn-update-text'
        ).addClass('d-none');

        button.find(
            '.btn-update-loading'
        ).removeClass('d-none');


        $.ajax({

            url:
                "{{ route('users.update') }}",

            type:
                "POST",

            data:
                formData,

            processData:
                false,

            contentType:
                false,

            headers: {

                'Accept':
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest'

            },


            success: function (response) {


                if (response.success) {


                    Swal.fire({

                        icon:
                            'success',

                        title:
                            'Berhasil',

                        text:
                            'User berhasil diupdate',

                        timer:
                            1500,

                        showConfirmButton:
                            false

                    });


                    let modal =
                        bootstrap.Modal.getInstance(
                            document.getElementById(
                                'modalEditUser'
                            )
                        );


                    if (modal) {

                        modal.hide();

                    }


                    $('#table-users')
                        .DataTable()
                        .ajax
                        .reload(
                            null,
                            false
                        );


                } else {


                    Swal.fire({

                        icon:
                            'error',

                        title:
                            'Gagal',

                        text:
                            response.message ||
                            'User gagal diupdate.'

                    });

                }

            },


            error: function (xhr) {

                console.log(
                    'UPDATE USER ERROR:',
                    xhr
                );

                showAjaxError(
                    xhr,
                    'User gagal diupdate.'
                );

            },


            complete: function () {

                button.prop(
                    'disabled',
                    false
                );

                button.find(
                    '.btn-update-text'
                ).removeClass('d-none');

                button.find(
                    '.btn-update-loading'
                ).addClass('d-none');

            }

        });

    });


    /* =====================================================
       DELETE USER
    ====================================================== */

    $(document).on(
        'click',
        '.btn-delete',
        function () {


            let id =
                $(this).data('id');


            let url =
                "{{ route('users.destroy', ['id' => ':id']) }}";


            url =
                url.replace(
                    ':id',
                    id
                );


            Swal.fire({

                title:
                    'Hapus Data?',

                text:
                    'Data yang sudah dihapus tidak bisa dikembalikan!',

                icon:
                    'warning',

                showCancelButton:
                    true,

                confirmButtonText:
                    'Ya, Hapus!',

                cancelButtonText:
                    'Batal'

            }).then((result) => {


                if (!result.isConfirmed) {

                    return;

                }


                $.ajax({

                    url:
                        url,

                    type:
                        'DELETE',

                    headers: {

                        'Accept':
                            'application/json',

                        'X-Requested-With':
                            'XMLHttpRequest'

                    },

                    data: {

                        _token:
                            $('meta[name="csrf-token"]')
                                .attr('content')

                    },


                    success:
                        function (response) {


                            Swal.fire({

                                icon:
                                    'success',

                                title:
                                    'Berhasil',

                                text:
                                    response.message ||
                                    'User berhasil dihapus.'

                            });


                            $('#table-users')
                                .DataTable()
                                .ajax
                                .reload(
                                    null,
                                    false
                                );

                        },


                    error:
                        function (xhr) {

                            console.log(
                                'DELETE USER ERROR:',
                                xhr
                            );

                            showAjaxError(
                                xhr,
                                'Data user gagal dihapus.'
                            );

                        }

                });

            });

        }
    );


    /* =====================================================
       FOCUS ADD MODAL
    ====================================================== */

    $('#modalAddUser').on(
        'shown.bs.modal',
        function () {

            $('#name')
                .trigger('focus');

        }
    );


    /* =====================================================
       FOCUS EDIT MODAL
    ====================================================== */

    $('#modalEditUser').on(
        'shown.bs.modal',
        function () {

            $('#edit_name')
                .trigger('focus')
                .select();

        }
    );

});


/* =========================================================
   GLOBAL AJAX ERROR HANDLER
========================================================= */

function showAjaxError(
    xhr,
    defaultMessage = 'Terjadi kesalahan.'
) {


    let title =
        'Gagal';


    let message =
        defaultMessage;


    /*
    |--------------------------------------------------------------------------
    | HTTP STATUS
    |--------------------------------------------------------------------------
    */

    if (xhr.status === 0) {

        title =
            'Koneksi Gagal';

        message =
            'Tidak dapat terhubung ke server. Periksa koneksi Anda.';

    }


    else if (xhr.status === 401) {

        title =
            'Sesi Berakhir';

        message =
            'Sesi login Anda telah berakhir. Silakan login kembali.';

    }


    else if (xhr.status === 403) {

        title =
            'Tidak Diizinkan';

        message =
            'Anda tidak memiliki izin untuk melakukan tindakan ini.';

    }


    else if (xhr.status === 404) {

        title =
            'Data Tidak Ditemukan';

        message =
            'Endpoint atau data yang diminta tidak ditemukan.';

    }


    else if (xhr.status === 419) {

        title =
            'Sesi Berakhir';

        message =
            'Halaman sudah tidak aktif. Silakan refresh halaman dan coba lagi.';

    }


    else if (xhr.status === 422) {

        title =
            'Validasi Gagal';

    }


    else if (xhr.status === 409) {

        title =
            'Data Sudah Ada';

        message =
            'Data yang dimasukkan sudah terdaftar.';

    }


    else if (xhr.status >= 500) {

        title =
            'Server Error';

        message =
            'Terjadi kesalahan pada server. Silakan coba lagi.';

    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSE JSON
    |--------------------------------------------------------------------------
    */

    let response =
        xhr.responseJSON;


    /*
    |--------------------------------------------------------------------------
    | VALIDATION / BUSINESS ERRORS
    |--------------------------------------------------------------------------
    */

    if (
        response &&
        response.errors
    ) {


        let errors = [];


        $.each(
            response.errors,
            function (
                key,
                value
            ) {


                if (
                    Array.isArray(value)
                ) {


                    value.forEach(
                        function (error) {

                            if (
                                error !== null &&
                                error !== undefined &&
                                String(error).trim() !== ''
                            ) {

                                errors.push(
                                    String(error)
                                );

                            }

                        }
                    );

                }


                else if (
                    typeof value === 'string'
                ) {


                    if (
                        value.trim() !== ''
                    ) {

                        errors.push(
                            value
                        );

                    }

                }

            }
        );


        if (errors.length > 0) {

            message =
                errors.join('<br>');


            /*
            |--------------------------------------------------------------------------
            | KHUSUS DUPLICATE EMAIL
            |--------------------------------------------------------------------------
            |
            | Hanya jika Laravel benar-benar mengirim
            | errors.email.
            |
            */

            if (
                response.errors.email &&
                Array.isArray(
                    response.errors.email
                )
            ) {

                let emailErrors =
                    response.errors.email.join(' ');

                if (
                    emailErrors
                        .toLowerCase()
                        .includes('sudah terdaftar')
                ) {

                    title =
                        'Email Sudah Terdaftar';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | KHUSUS DUPLICATE PHONE
            |--------------------------------------------------------------------------
            */

            if (
                response.errors.phone &&
                Array.isArray(
                    response.errors.phone
                )
            ) {

                let phoneErrors =
                    response.errors.phone.join(' ');

                if (
                    phoneErrors
                        .toLowerCase()
                        .includes('sudah terdaftar')
                ) {

                    title =
                        'Phone Sudah Terdaftar';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | PLAN LIMIT
            |--------------------------------------------------------------------------
            */

            if (
                response.errors.plan_limit
            ) {

                title =
                    'Batas Paket Tercapai';

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | NORMAL SERVER MESSAGE
    |--------------------------------------------------------------------------
    */

    else if (
        response &&
        response.message
    ) {

        message =
            response.message;

    }


    /*
    |--------------------------------------------------------------------------
    | SWEETALERT
    |--------------------------------------------------------------------------
    */

    Swal.fire({

        icon:
            'error',

        title:
            title,

        html:
            message,

        allowOutsideClick:
            false,

        confirmButtonText:
            'OK'

    });

}


/* =========================================================
   ESCAPE HTML
========================================================= */

function escapeHtml(value) {

    return String(value ?? '')
        .replace(
            /&/g,
            '&amp;'
        )
        .replace(
            /</g,
            '&lt;'
        )
        .replace(
            />/g,
            '&gt;'
        )
        .replace(
            /"/g,
            '&quot;'
        )
        .replace(
            /'/g,
            '&#039;'
        );

}


/* =========================================================
   COPY TEMPORARY PASSWORD
========================================================= */

function copyPassword() {


    let input =
        document.getElementById(
            'tempPassword'
        );


    if (!input) {

        return;

    }


    navigator.clipboard.writeText(
        input.value
    );


    Swal.fire({

        icon:
            'success',

        title:
            'Password berhasil disalin',

        timer:
            1000,

        showConfirmButton:
            false

    });

}

</script>

@endsection
