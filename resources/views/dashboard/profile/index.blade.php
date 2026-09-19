@extends('dashboard.layouts.wrapper')

@section('title', 'Profile')

@section('content')

<style>

.swal2-container {
    z-index: 99999 !important;
}


/* =====================================================
   PASSWORD
===================================================== */

.password-wrapper {
    position: relative;
}

.password-wrapper .form-control {
    padding-right: 45px;
}

.toggle-password {
    position: absolute;
    top: 50%;
    right: 8px;
    transform: translateY(-50%);

    border: 0;
    background: transparent;

    color: #8592a3;

    padding: 6px 8px;

    cursor: pointer;

    z-index: 5;
}

.toggle-password:hover {
    color: #696cff;
}

.form-label {
    font-weight: 600;
}

.invalid-feedback {
    display: block;
}


/* =====================================================
   ACCOUNT SUMMARY
===================================================== */

.profile-summary {
    border-radius: 12px;

    background: linear-gradient(
        135deg,
        #696cff,
        #8592a3
    );

    color: #fff;
}

.profile-summary-item {
    padding: 12px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,.18);
}

.profile-summary-item:last-child {
    border-bottom: 0;
}

.profile-summary-label {
    font-size: 12px;

    opacity: .75;

    margin-bottom: 3px;
}

.profile-summary-value {
    font-size: 14px;

    font-weight: 600;

    word-break: break-word;
}


/* =====================================================
   AVATAR
===================================================== */

.profile-avatar {
    width: 82px;
    height: 82px;

    border-radius: 50%;

    display: flex;
    align-items: center;
    justify-content: center;

    margin: 0 auto;

    font-size: 32px;
    font-weight: 700;

    color: #fff;

    background: linear-gradient(
        135deg,
        #696cff,
        #8592a3
    );
}


/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1199.98px) {

    .profile-avatar {
        width: 76px;
        height: 76px;

        font-size: 30px;
    }

}


@media (max-width: 767.98px) {

    .profile-avatar {
        width: 70px;
        height: 70px;

        font-size: 27px;
    }

    .card-header {
        padding: 1rem;
    }

    .card-body {
        padding: 1rem;
    }

    .btn-responsive {
        width: 100%;
    }

    .profile-summary {
        border-radius: 10px;
    }

}

</style>

<div class="content-wrapper">

<!-- Content -->

<div class="container-xxl flex-grow-1 container-p-y">

    {{-- =====================================================
         PAGE HEADER
    ====================================================== --}}

    <div class="row">

        <div class="col-xxl-12 mb-4 order-0">

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            Profile
                        </h5>

                        <small class="text-muted">
                            Manage your account and password
                        </small>

                    </div>

                </div>
{{-- =====================================================
         MAIN CONTENT
    ====================================================== --}}

    <div class="row">


        {{-- =================================================
             CHANGE PASSWORD
        ================================================== --}}

        <div class="col-xxl-8 col-xl-8 col-lg-8 col-md-12 mb-4">

            <div class="card h-100">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            Change Password
                        </h5>

                        <small class="text-muted">
                            Update your account password
                        </small>

                    </div>

                </div>


                <div class="card-body">

                    <form id="passwordForm">

                        @csrf

                        <div class="row">


                            {{-- =================================================
                                 CURRENT PASSWORD
                            ================================================== --}}

                            <div class="col-xl-4 col-lg-4 col-md-12 mb-4">

                                <label
                                    for="current_password"
                                    class="form-label"
                                >
                                    Current Password
                                    <span class="text-danger">*</span>
                                </label>


                                <div class="password-wrapper">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="current_password"
                                        name="current_password"
                                        autocomplete="current-password"
                                    >


                                    <button
                                        type="button"
                                        class="toggle-password"
                                        data-target="current_password"
                                        aria-label="Show or hide current password"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>


                                <div
                                    class="invalid-feedback"
                                    id="current_passwordError"
                                ></div>

                            </div>


                            {{-- =================================================
                                 NEW PASSWORD
                            ================================================== --}}

                            <div class="col-xl-4 col-lg-4 col-md-12 mb-4">

                                <label
                                    for="password"
                                    class="form-label"
                                >
                                    New Password
                                    <span class="text-danger">*</span>
                                </label>


                                <div class="password-wrapper">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password"
                                        name="password"
                                        autocomplete="new-password"
                                    >


                                    <button
                                        type="button"
                                        class="toggle-password"
                                        data-target="password"
                                        aria-label="Show or hide new password"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>


                                <small class="text-muted">
                                    Minimum 8 characters.
                                </small>


                                <div
                                    class="invalid-feedback"
                                    id="passwordError"
                                ></div>

                            </div>


                            {{-- =================================================
                                 CONFIRM PASSWORD
                            ================================================== --}}

                            <div class="col-xl-4 col-lg-4 col-md-12 mb-4">

                                <label
                                    for="password_confirmation"
                                    class="form-label"
                                >
                                    Confirm Password
                                    <span class="text-danger">*</span>
                                </label>


                                <div class="password-wrapper">

                                    <input
                                        type="password"
                                        class="form-control"
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autocomplete="new-password"
                                    >


                                    <button
                                        type="button"
                                        class="toggle-password"
                                        data-target="password_confirmation"
                                        aria-label="Show or hide confirm password"
                                    >

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>


                                <div
                                    class="invalid-feedback"
                                    id="password_confirmationError"
                                ></div>

                            </div>


                            {{-- =================================================
                                 BUTTON
                            ================================================== --}}

                            <div class="col-12">

                                <div class="d-flex justify-content-end">

                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-responsive"
                                        id="btnUpdatePassword"
                                    >

                                        <i class="fa-solid fa-key me-1"></i>

                                        Update Password

                                    </button>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        {{-- =================================================
             ACCOUNT
        ================================================== --}}

        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12 mb-4">

            <div class="card h-100">

                <div class="card-header d-flex justify-content-between align-items-center">

                    <div>

                        <h5 class="mb-0">
                            Account
                        </h5>

                        <small class="text-muted">
                            Account information
                        </small>

                    </div>

                </div>


                <div class="card-body">

                    <div class="profile-summary p-4">


                        {{-- =================================================
                             AVATAR
                        ================================================== --}}

                        <div class="text-center mb-4">

                            <div class="profile-avatar mb-3">

                                {{
                                    strtoupper(
                                        substr(
                                            $user->name ?? 'U',
                                            0,
                                            1
                                        )
                                    )
                                }}

                            </div>


                            <h5 class="text-white mb-1">

                                {{ $user->name }}

                            </h5>


                            <div class="text-white-50">

                                {{ $user->email }}

                            </div>

                        </div>


                        {{-- =================================================
                             ROLE
                        ================================================== --}}

                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                Role
                            </div>

                            <div class="profile-summary-value">

                                {{ $user->role?->role_name ?? '-' }}

                            </div>

                        </div>


                        {{-- =================================================
                             PHONE
                        ================================================== --}}

                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                Phone
                            </div>

                            <div class="profile-summary-value">

                                {{ $user->phone ?: '-' }}

                            </div>

                        </div>


                        {{-- =================================================
                             NIK
                        ================================================== --}}

                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                NIK
                            </div>

                            <div class="profile-summary-value">

                                {{ $user->nik ?: '-' }}

                            </div>

                        </div>


                        {{-- =================================================
                             STATUS
                        ================================================== --}}

                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                Status
                            </div>

                            <div class="profile-summary-value">

                                @if($user->status)

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        Inactive
                                    </span>

                                @endif

                            </div>

                        </div>


                    </div>

                </div>

            </div>

        </div>

    </div>

</div>
            </div>
            

        </div>

    </div>


    

</div>

@endsection

@section('js')

<script>

$(function () {


    /*
    |--------------------------------------------------------------------------
    | CSRF
    |--------------------------------------------------------------------------
    */

    $.ajaxSetup({

        headers: {

            'X-CSRF-TOKEN':
                $('meta[name="csrf-token"]').attr('content')

        }

    });


    /*
    |--------------------------------------------------------------------------
    | ERROR HANDLER
    |--------------------------------------------------------------------------
    */

    function showErrors(errors, form)
    {

        form.find('.is-invalid')
            .removeClass('is-invalid');


        form.find('.invalid-feedback')
            .text('');


        $.each(errors, function (key, value) {

            let input =
                form.find(
                    '[name="' + key + '"]'
                );


            let error =
                $('#' + key + 'Error');


            if (input.length) {

                input.addClass(
                    'is-invalid'
                );

            }


            if (error.length) {

                error.text(
                    Array.isArray(value)
                        ? value[0]
                        : value
                );

            }

        });

    }


    /*
    |--------------------------------------------------------------------------
    | PASSWORD
    |--------------------------------------------------------------------------
    */

    $('#passwordForm').submit(function (e) {

        e.preventDefault();


        let form =
            $(this);


        let button =
            $('#btnUpdatePassword');


        let original =
            button.html();


        /*
        |----------------------------------------------------------------------
        | CLEAR ERROR
        |----------------------------------------------------------------------
        */

        form.find('.is-invalid')
            .removeClass('is-invalid');


        form.find('.invalid-feedback')
            .text('');


        /*
        |----------------------------------------------------------------------
        | LOADING
        |----------------------------------------------------------------------
        */

        button
            .prop(
                'disabled',
                true
            )
            .html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...'
            );


        /*
        |----------------------------------------------------------------------
        | AJAX
        |----------------------------------------------------------------------
        */

        $.ajax({

            url:
                "{{ route('profile.password') }}",

            type:
                "POST",

            data:
                form.serialize(),

            dataType:
                "json",

            headers: {

                'Accept':
                    'application/json'

            },


            success:
                function (response) {

                    console.log(
                        'PASSWORD RESPONSE:',
                        response
                    );


                    if (
                        response.success
                    ) {

                        form[0].reset();


                        Swal.fire({

                            icon:
                                'success',

                            title:
                                'Password Updated',

                            text:
                                response.message,

                            timer:
                                1800,

                            showConfirmButton:
                                false

                        });

                    }

                },


            error:
                function (xhr) {

                    console.log(
                        'PASSWORD RESPONSE:',
                        xhr.status,
                        xhr.responseText
                    );


                    /*
                    |----------------------------------------------------------
                    | VALIDATION
                    |----------------------------------------------------------
                    */

                    if (
                        xhr.status === 422
                    ) {

                        let errors =
                            xhr.responseJSON?.errors || {};


                        showErrors(
                            errors,
                            form
                        );


                        Swal.fire({

                            icon:
                                'warning',

                            title:
                                'Validation Failed',

                            text:
                                xhr.responseJSON?.message ||
                                'Password belum dapat diperbarui.'

                        });


                        return;

                    }


                    /*
                    |----------------------------------------------------------
                    | OTHER ERROR
                    |----------------------------------------------------------
                    */

                    let message =
                        xhr.responseJSON?.message;


                    if (!message) {

                        if (
                            xhr.status === 419
                        ) {

                            message =
                                'Session expired. Silakan refresh halaman dan coba lagi.';

                        }
                        else if (
                            xhr.status === 403
                        ) {

                            message =
                                'Anda tidak memiliki akses untuk mengubah password.';

                        }
                        else if (
                            xhr.status === 404
                        ) {

                            message =
                                'Route password tidak ditemukan.';

                        }
                        else if (
                            xhr.status === 500
                        ) {

                            message =
                                xhr.responseJSON?.error ||
                                'Terjadi error pada server.';

                        }
                        else {

                            message =
                                'Password gagal diperbarui.';

                        }

                    }


                    Swal.fire({

                        icon:
                            'error',

                        title:
                            'Password Error',

                        text:
                            message

                    });

                },


            /*
            |----------------------------------------------------------------------
            | COMPLETE
            |----------------------------------------------------------------------
            */

            complete:
                function () {

                    button
                        .prop(
                            'disabled',
                            false
                        )
                        .html(
                            original
                        );

                }

        });

    });


    /*
    |--------------------------------------------------------------------------
    | TOGGLE PASSWORD
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.toggle-password',
        function () {

            let target =
                $('#' + $(this).data('target'));


            let icon =
                $(this).find('i');


            if (
                target.attr('type') ===
                'password'
            ) {

                target.attr(
                    'type',
                    'text'
                );


                icon
                    .removeClass(
                        'fa-eye'
                    )
                    .addClass(
                        'fa-eye-slash'
                    );

            }
            else {

                target.attr(
                    'type',
                    'password'
                );


                icon
                    .removeClass(
                        'fa-eye-slash'
                    )
                    .addClass(
                        'fa-eye'
                    );

            }

        }
    );

});

</script>

@endsection
