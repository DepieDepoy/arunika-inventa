@extends('dashboard.layouts.wrapper')

@section('title', 'Role Permission')

@section('content')
<style>
    .permission-page {
        padding-bottom: 30px;
    }

    .permission-header {
        margin-bottom: 1.5rem;
    }

    .permission-header h4 {
        font-weight: 700;
        color: #1f2937;
    }

    .permission-header p {
        font-size: 14px;
        margin-bottom: 0;
    }

    .permission-role-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        margin-bottom: 1.5rem;
    }

    .permission-role-card .card-body {
        padding: 1.25rem 1.5rem;
    }

    .role-info-label {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 3px;
    }

    .role-info-value {
        font-weight: 600;
        color: #1f2937;
    }

    .permission-card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, .06);
        margin-bottom: 1.25rem;
        overflow: hidden;
    }

    .permission-card-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .permission-module-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: #1f2937;
        text-transform: capitalize;
    }

    .permission-module-title i {
        color: #4f46e5;
    }

    .permission-list {
        padding: 1rem 1.25rem;
    }

    .permission-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: .75rem 1rem;
        margin-bottom: .75rem;
        transition: .2s;
    }

    .permission-item:last-child {
        margin-bottom: 0;
    }

    .permission-item:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .permission-name {
        font-weight: 600;
        color: #374151;
    }

    .permission-code {
        display: block;
        font-size: 12px;
        color: #9ca3af;
        margin-top: 2px;
    }

    .permission-description {
        font-size: 12px;
        color: #6b7280;
        margin-top: 4px;
    }

    .form-check-input {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .form-check-label {
        cursor: pointer;
    }

    .select-all-label {
        font-size: 13px;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
    }

    .permission-footer {
        position: sticky;
        bottom: 15px;
        z-index: 10;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, .12);
        padding: 1rem 1.25rem;
        margin-top: 1rem;
    }
</style>

<div class="content-wrapper">
<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
    <div class="col-xxl-12 mb-12 order-0">
    <div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-0">Role Permission</h5>
            <small class="text-muted">
                Atur hak akses untuk role
                    <strong>{{ $role->role_name }}</strong>
            </small>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('roles.index') }}" class="btn btn-light">
                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Back
                </a>
        </div>
    </div>
    <div class="card-body">
        {{-- ROLE INFORMATION --}}
        <div class="card permission-role-card">

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">
                        <div class="role-info-label">
                            Role Name
                        </div>

                        <div class="role-info-value">
                            {{ $role->role_name }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="role-info-label">
                            Role Code
                        </div>

                        <div class="role-info-value">
                            {{ $role->role_code }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="role-info-label">
                            Status
                        </div>

                        <div class="role-info-value">

                            @if($role->status == 1)

                                <span class="badge rounded-pill bg-success-subtle text-success px-3 py-2">
                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    Active
                                </span>

                            @else

                                <span class="badge rounded-pill bg-danger-subtle text-danger px-3 py-2">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>
                                    Inactive
                                </span>

                            @endif

                        </div>
                    </div>

                </div>

            </div>

        </div>


        {{-- PERMISSION FORM --}}
        <form
            method="POST"
            action="{{ route('roles.permission.save', $role->id) }}"
            id="permissionForm"
        >

            @csrf


            @foreach($permissions as $module => $modulePermissions)

                <div class="card permission-card">

                    {{-- MODULE HEADER --}}
                    <div class="permission-card-header">

                        <div class="permission-module-title">

                            <i class="fa-solid fa-folder-open"></i>

                            <span>
                                {{ ucwords(str_replace('_', ' ', $module)) }}
                            </span>

                        </div>


                        <div class="form-check">

                            <input
                                type="checkbox"
                                class="form-check-input select-all-module"
                                data-module="{{ $module }}"
                                id="selectAll_{{ $module }}"
                            >

                            <label
                                class="form-check-label select-all-label"
                                for="selectAll_{{ $module }}"
                            >
                                Select All
                            </label>

                        </div>

                    </div>


                    {{-- PERMISSION LIST --}}
                    <div class="permission-list">

                        @foreach($modulePermissions as $permission)

                            <div class="permission-item">

                                <div class="form-check d-flex align-items-start gap-2">

                                    <input
                                        type="checkbox"
                                        class="form-check-input permission-checkbox permission-{{ $module }}"
                                        name="permissions[]"
                                        value="{{ $permission->id }}"
                                        id="permission_{{ $permission->id }}"
                                        {{ in_array($permission->id, $rolePermissions) ? 'checked' : '' }}
                                    >

                                    <label
                                        class="form-check-label flex-grow-1"
                                        for="permission_{{ $permission->id }}"
                                    >

                                        <span class="permission-name">
                                            {{ $permission->permission_name }}
                                        </span>

                                        <span class="permission-code">
                                            {{ $permission->permission_code }}
                                        </span>

                                        @if($permission->description)

                                            <div class="permission-description">
                                                {{ $permission->description }}
                                            </div>

                                        @endif

                                    </label>

                                </div>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endforeach


            {{-- FOOTER --}}
            <div class="permission-footer">

                <div class="d-flex justify-content-between align-items-center">

                    <div>
                        <small class="text-muted">
                            <i class="fa-solid fa-circle-info me-1"></i>
                            Permission akan langsung berlaku untuk seluruh user dengan role ini.
                        </small>
                    </div>

                    <div class="d-flex gap-2">

                        <a
                            href="{{ route('roles.index') }}"
                            class="btn btn-light"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            Save Permission
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
</div>


<script>

document.addEventListener('DOMContentLoaded', function () {

    // =====================================================
    // SELECT ALL PER MODULE
    // =====================================================

    document.querySelectorAll('.select-all-module')
        .forEach(function (selectAll) {

            selectAll.addEventListener('change', function () {

                const module = this.dataset.module;

                const checkboxes = document.querySelectorAll(
                    '.permission-' + module
                );

                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = selectAll.checked;
                });

            });

        });


    // =====================================================
    // UPDATE SELECT ALL STATUS
    // =====================================================

    document.querySelectorAll('.permission-checkbox')
        .forEach(function (checkbox) {

            checkbox.addEventListener('change', function () {

                const classes = Array.from(this.classList);

                const moduleClass = classes.find(function (className) {
                    return className.startsWith('permission-')
                        && className !== 'permission-checkbox';
                });

                if (!moduleClass) {
                    return;
                }

                const module = moduleClass.replace('permission-', '');

                const allCheckboxes = document.querySelectorAll(
                    '.permission-' + module
                );

                const checkedCheckboxes = document.querySelectorAll(
                    '.permission-' + module + ':checked'
                );

                const selectAll = document.querySelector(
                    '.select-all-module[data-module="' + module + '"]'
                );

                if (!selectAll) {
                    return;
                }

                selectAll.checked =
                    allCheckboxes.length === checkedCheckboxes.length;

            });

        });


    // =====================================================
    // INITIALIZE SELECT ALL
    // =====================================================

    document.querySelectorAll('.select-all-module')
        .forEach(function (selectAll) {

            const module = selectAll.dataset.module;

            const allCheckboxes = document.querySelectorAll(
                '.permission-' + module
            );

            const checkedCheckboxes = document.querySelectorAll(
                '.permission-' + module + ':checked'
            );

            selectAll.checked =
                allCheckboxes.length > 0 &&
                allCheckboxes.length === checkedCheckboxes.length;

        });

});

</script>

@endsection