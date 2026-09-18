@extends('dashboard.layouts.wrapper')

@section('title', 'Profile')

@section('content')

<style>
    .swal2-container {
        z-index: 99999 !important;
    }

    /* =====================================================
       PROFILE
    ====================================================== */

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
        background: linear-gradient(135deg, #696cff, #8592a3);
    }

    .profile-summary {
        border-radius: 12px;
        background: linear-gradient(135deg, #696cff, #8592a3);
        color: #fff;
    }

    .profile-summary .text-muted {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    .profile-summary-item {
        padding: 12px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.18);
    }

    .profile-summary-item:last-child {
        border-bottom: 0;
    }

    .profile-summary-label {
        font-size: 12px;
        opacity: 0.75;
        margin-bottom: 3px;
    }

    .profile-summary-value {
        font-size: 14px;
        font-weight: 600;
        word-break: break-word;
    }

    .form-label {
        font-weight: 600;
    }

    .password-wrapper {
        position: relative;
    }

    .password-wrapper .form-control {
        padding-right: 45px;
    }

    .toggle-password {
        position: absolute;
        top: 50%;
        right: 10px;
        transform: translateY(-50%);
        border: 0;
        background: transparent;
        color: #8592a3;
        padding: 5px 8px;
        cursor: pointer;
        z-index: 5;
    }

    .toggle-password:hover {
        color: #696cff;
    }

    .invalid-feedback {
        display: block;
    }

    /* =====================================================
       RESPONSIVE
    ====================================================== */

    @media (max-width: 991.98px) {
        .profile-summary {
            margin-top: 0;
        }
    }

    @media (max-width: 767.98px) {

        .container-xxl.container-p-y {
            padding-left: 12px;
            padding-right: 12px;
        }

        .card-header {
            padding: 1rem;
        }

        .card-body {
            padding: 1rem;
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            font-size: 28px;
        }

        .profile-summary {
            padding: 20px !important;
        }

        .btn-responsive {
            width: 100%;
        }

        .profile-page-title {
            font-size: 20px;
        }
    }

    @media (max-width: 575.98px) {

        .container-xxl.container-p-y {
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .row.g-4 {
            --bs-gutter-y: 1rem;
        }

        .profile-summary {
            border-radius: 10px;
        }

        .profile-summary-item {
            padding: 10px 0;
        }

        .profile-summary-value {
            font-size: 13px;
        }

        .form-control {
            min-height: 42px;
        }

        .btn {
            min-height: 42px;
        }
    }
</style>

<div class="content-wrapper">

```
<div class="container-xxl flex-grow-1 container-p-y">

    {{-- =====================================================
         PAGE HEADER
    ====================================================== --}}
    <div class="row">
        <div class="col-12">

            <div class="mb-4">

                <h4 class="fw-bold mb-1 profile-page-title">
                    Profile
                </h4>

                <p class="text-muted mb-0">
                    Manage your personal information and account password.
                </p>

            </div>

        </div>
    </div>


    {{-- =====================================================
         PROFILE INFORMATION + ACCOUNT SUMMARY
    ====================================================== --}}
    <div class="row g-4">

        {{-- =================================================
             PROFILE INFORMATION
        ================================================== --}}
        <div class="col-xxl-8 col-xl-8 col-lg-8 col-md-12">

            <div class="card h-100">

                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <div>
                        <h5 class="mb-0">
                            Profile Information
                        </h5>

                        <small class="text-muted">
                            Manage your personal information
                        </small>
                    </div>

                </div>


                <div class="card-body">

                    <form id="profileForm">

                        @csrf

                        <div class="row g-3">

                            {{-- NAME --}}
                            <div class="col-md-6">

                                <label for="name" class="form-label">
                                    Name
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="name"
                                    name="name"
                                    value="{{ old('name', $user->name) }}"
                                    autocomplete="name"
                                >

                                <div
                                    class="invalid-feedback"
                                    id="nameError">
                                </div>

                            </div>


                            {{-- NIK --}}
                            <div class="col-md-6">

                                <label for="nik" class="form-label">
                                    NIK
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="nik"
                                    name="nik"
                                    value="{{ old('nik', $user->nik) }}"
                                    autocomplete="off"
                                >

                                <div
                                    class="invalid-feedback"
                                    id="nikError">
                                </div>

                            </div>


                            {{-- EMAIL --}}
                            <div class="col-md-6">

                                <label for="email" class="form-label">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    class="form-control"
                                    id="email"
                                    name="email"
                                    value="{{ $user->email }}"
                                    readonly
                                >

                                <small class="text-muted">
                                    Email cannot be changed from this page.
                                </small>

                                <div
                                    class="invalid-feedback"
                                    id="emailError">
                                </div>

                            </div>


                            {{-- PHONE --}}
                            <div class="col-md-6">

                                <label for="phone" class="form-label">
                                    Phone
                                    <span class="text-danger">*</span>
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="phone"
                                    name="phone"
                                    value="{{ old('phone', $user->phone) }}"
                                    autocomplete="tel"
                                >

                                <div
                                    class="invalid-feedback"
                                    id="phoneError">
                                </div>

                            </div>


                            {{-- BUTTON --}}
                            <div class="col-12">

                                <div class="d-flex justify-content-end">

                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-responsive"
                                        id="btnUpdateProfile">

                                        <i class="fa-solid fa-floppy-disk me-1"></i>

                                        Save Changes

                                    </button>

                                </div>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

        </div>


        {{-- =================================================
             ACCOUNT SUMMARY
        ================================================== --}}
        <div class="col-xxl-4 col-xl-4 col-lg-4 col-md-12">

            <div class="card h-100">

                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">

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

                        {{-- AVATAR --}}
                        <div class="text-center mb-4">

                            <div class="profile-avatar mb-3">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>

                            <h5 class="text-white mb-1 profile-name">
                                {{ $user->name }}
                            </h5>

                            <div class="text-muted profile-email">
                                {{ $user->email }}
                            </div>

                        </div>


                        {{-- ROLE --}}
                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                Role
                            </div>

                            <div class="profile-summary-value">
                                {{ $user->role?->role_name ?? '-' }}
                            </div>

                        </div>


                        {{-- PHONE --}}
                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                Phone
                            </div>

                            <div class="profile-summary-value profile-phone">
                                {{ $user->phone ?: '-' }}
                            </div>

                        </div>


                        {{-- NIK --}}
                        <div class="profile-summary-item">

                            <div class="profile-summary-label">
                                NIK
                            </div>

                            <div class="profile-summary-value profile-nik">
                                {{ $user->nik ?: '-' }}
                            </div>

                        </div>


                        {{-- STATUS --}}
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


    {{-- =====================================================
         CHANGE PASSWORD
    ====================================================== --}}
    <div class="row mt-4">

        <div class="col-12">

            <div class="card">

                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-3">

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

                        <div class="row g-3">

                            {{-- CURRENT PASSWORD --}}
                            <div class="col-xl-4 col-lg-4 col-md-12">

                                <label
                                    for="current_password"
                                    class="form-label">

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
                                        tabindex="-1">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>

                                <div
                                    class="invalid-feedback"
                                    id="current_passwordError">
                                </div>

                            </div>


                            {{-- NEW PASSWORD --}}
                            <div class="col-xl-4 col-lg-4 col-md-12">

                                <label
                                    for="password"
                                    class="form-label">

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
                                        tabindex="-1">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>

                                <small class="text-muted">
                                    Minimum 8 characters.
                                </small>

                                <div
                                    class="invalid-feedback"
                                    id="passwordError">
                                </div>

                            </div>


                            {{-- CONFIRM PASSWORD --}}
                            <div class="col-xl-4 col-lg-4 col-md-12">

                                <label
                                    for="password_confirmation"
                                    class="form-label">

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
                                        tabindex="-1">

                                        <i class="fa-solid fa-eye"></i>

                                    </button>

                                </div>

                                <div
                                    class="invalid-feedback"
                                    id="password_confirmationError">
                                </div>

                            </div>


                            {{-- PASSWORD BUTTON --}}
                            <div class="col-12">

                                <div class="d-flex justify-content-end">

                                    <button
                                        type="submit"
                                        class="btn btn-primary btn-responsive"
                                        id="btnUpdatePassword">

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

    </div>

</div>
```

</div>

@endsection

@section('js')

<script>

$(function () {

    /* =====================================================
       CLEAR PROFILE ERRORS
    ====================================================== */

    function clearProfileErrors() {

        $('#profileForm .is-invalid')
            .removeClass('is-invalid');

        $('#profileForm .invalid-feedback')
            .text('');

    }


    /* =====================================================
       CLEAR PASSWORD ERRORS
    ====================================================== */

    function clearPasswordErrors() {

        $('#passwordForm .is-invalid')
            .removeClass('is-invalid');

        $('#passwordForm .invalid-feedback')
            .text('');

    }


    /* =====================================================
       SHOW VALIDATION ERRORS
    ====================================================== */

    function showErrors(errors, form) {

        $.each(errors, function (key, value) {

            let input = form.find(
                '[name="' + key + '"]'
            );

            let error = $('#' + key + 'Error');

            if (input.length) {

                input.addClass('is-invalid');

            }

            if (error.length) {

                error.text(value[0]);

            }

        });

    }


    /* =====================================================
       UPDATE PROFILE
    ====================================================== */

    $('#profileForm').on('submit', function (e) {

        e.preventDefault();

        clearProfileErrors();

        let button = $('#btnUpdateProfile');

        let originalText = button.html();

        button
            .prop('disabled', true)
            .html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...'
            );


        $.ajax({

            url: "{{ route('profile.update') }}",

            type: "POST",

            data: $(this).serialize(),

            headers: {
                'Accept': 'application/json'
            },


            success: function (response) {

                if (response.success) {

                    /*
                    |--------------------------------------------------
                    | UPDATE DISPLAY DATA
                    |--------------------------------------------------
                    */

                    $('.profile-name')
                        .text($('#name').val());

                    $('.profile-phone')
                        .text($('#phone').val() || '-');

                    $('.profile-nik')
                        .text($('#nik').val() || '-');


                    /*
                    |--------------------------------------------------
                    | UPDATE HEADER USER NAME
                    |--------------------------------------------------
                    */

                    $('.caption-title')
                        .first()
                        .text($('#name').val());


                    /*
                    |--------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------
                    */

                    Swal.fire({

                        icon: 'success',

                        title: 'Success',

                        text: response.message,

                        timer: 1600,

                        showConfirmButton: false

                    });

                }

            },


            error: function (xhr) {

                if (xhr.status === 422) {

                    let errors =
                        xhr.responseJSON.errors || {};

                    showErrors(
                        errors,
                        $('#profileForm')
                    );


                    Swal.fire({

                        icon: 'warning',

                        title: 'Validation Failed',

                        text:
                            'Please check the highlighted fields.'

                    });

                    return;

                }


                Swal.fire({

                    icon: 'error',

                    title: 'Error',

                    text:
                        'Profile gagal diperbarui. Silakan coba lagi.'

                });

            },


            complete: function () {

                button
                    .prop('disabled', false)
                    .html(originalText);

            }

        });

    });


    /* =====================================================
       UPDATE PASSWORD
    ====================================================== */

    $('#passwordForm').on('submit', function (e) {

        e.preventDefault();

        clearPasswordErrors();

        let button = $('#btnUpdatePassword');

        let originalText = button.html();

        button
            .prop('disabled', true)
            .html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Updating...'
            );


        $.ajax({

            url: "{{ route('profile.password') }}",

            type: "POST",

            data: $(this).serialize(),

            headers: {
                'Accept': 'application/json'
            },


            success: function (response) {

                if (response.success) {

                    /*
                    |--------------------------------------------------
                    | RESET FORM
                    |--------------------------------------------------
                    */

                    $('#passwordForm')[0].reset();


                    /*
                    |--------------------------------------------------
                    | SUCCESS
                    |--------------------------------------------------
                    */

                    Swal.fire({

                        icon: 'success',

                        title: 'Password Updated',

                        text: response.message,

                        timer: 1800,

                        showConfirmButton: false

                    });

                }

            },


            error: function (xhr) {

                if (xhr.status === 422) {

                    let errors =
                        xhr.responseJSON.errors || {};

                    showErrors(
                        errors,
                        $('#passwordForm')
                    );


                    Swal.fire({

                        icon: 'warning',

                        title: 'Validation Failed',

                        text:
                            'Password belum dapat diperbarui.'

                    });

                    return;

                }


                Swal.fire({

                    icon: 'error',

                    title: 'Error',

                    text:
                        'Password gagal diperbarui. Silakan coba lagi.'

                });

            },


            complete: function () {

                button
                    .prop('disabled', false)
                    .html(originalText);

            }

        });

    });


    /* =====================================================
       TOGGLE PASSWORD
    ====================================================== */

    $(document).on(
        'click',
        '.toggle-password',
        function () {

            let target = $('#' + $(this).data('target'));

            let icon = $(this).find('i');


            if (target.attr('type') === 'password') {

                target.attr(
                    'type',
                    'text'
                );

                icon
                    .removeClass('fa-eye')
                    .addClass('fa-eye-slash');

            } else {

                target.attr(
                    'type',
                    'password'
                );

                icon
                    .removeClass('fa-eye-slash')
                    .addClass('fa-eye');

            }

        }
    );


    /* =====================================================
       FOCUS
    ====================================================== */

    $('#name').trigger('focus');

});

</script>

@endsection
