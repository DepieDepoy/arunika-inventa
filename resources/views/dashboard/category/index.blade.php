@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')

<div class="content-wrapper">

    <div class="container-xxl flex-grow-1 container-p-y">

        <div class="row">

            <div class="col-xxl-12 mb-12 order-0">

                <div class="card">

                    {{-- =====================================================
                         HEADER
                    ====================================================== --}}
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                Asset Category
                            </h5>
                            <small class="text-muted">
                                Manage application Category
                            </small>
                        </div>
                        <div class="d-flex gap-2">
                            {{-- Export --}}
                            <a
                                href="{{ route('category.export') }}"
                                class="btn btn-success"
                            >
                                <i class="fa-solid fa-file-excel me-1"></i>
                                Export Excel
                            </a>
                            {{-- Add Category --}}
                            <button
                                type="button"
                                class="btn btn-primary"
                                data-bs-toggle="modal"
                                data-bs-target="#modalAddCategory"
                            >
                                <i class="fa-solid fa-plus me-1"></i>
                                Add Category
                            </button>
                        </div>
                    </div>
                    {{-- =====================================================
                         CATEGORY TABLE
                    ====================================================== --}}
                    <div class="card-body">
                        <div class="table-responsive">
                            <table
                                class="table table-hover align-middle"
                                id="table-category"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th width="5%">
                                            No
                                        </th>
                                        <th>
                                            Category Name
                                        </th>
                                        <th>
                                            Code
                                        </th>
                                        <th>
                                            Description
                                        </th>
                                        <th>
                                            Total Asset
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



{{-- ================================================================
     MODAL ADD CATEGORY
================================================================ --}}

<div
    class="modal fade"
    id="modalAddCategory"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg">
        <form id="formAddCategory">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Category
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Name
                            </label>
                            <input
                                type="text"
                                name="category_name"
                                id="category_name"
                                class="form-control"
                                placeholder="Enter category name"
                                required
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                rows="3"
                                placeholder="Enter category description"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status
                            </label>
                            <select
                                name="status"
                                class="form-select"
                                required
                            >
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
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>

                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSaveCategory"
                    >
                        Save Category
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



{{-- ================================================================
     MODAL EDIT CATEGORY
================================================================ --}}

<div
    class="modal fade"
    id="modalEditCategory"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg">
        <form id="formEditCategory">
            @csrf
            <input
                type="hidden"
                name="id"
                id="edit_id"
            >
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Category
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Name
                            </label>
                            <input
                                type="text"
                                name="category_name"
                                id="edit_category_name"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea
                                name="description"
                                id="edit_description"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status
                            </label>
                            <select
                                name="status"
                                id="edit_status"
                                class="form-select"
                                required
                            >
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
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnUpdateCategory"
                    >
                        Save Category
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



{{-- ================================================================
     MODAL ADD SUB CATEGORY
================================================================ --}}

<div
    class="modal fade"
    id="modalAddSubCategory"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg">
        <form id="formAddSubCategory">
            @csrf
            <input
                type="hidden"
                name="category_id"
                id="sub_category_parent_id"
            >
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Sub Category
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Category
                            </label>
                            <input
                                type="text"
                                id="sub_category_parent_name"
                                class="form-control"
                                readonly
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Name
                            </label>
                            <input
                                type="text"
                                name="sub_category_name"
                                id="sub_category_name"
                                class="form-control"
                                placeholder="Enter sub category name"
                                required
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea
                                name="description"
                                id="sub_category_description"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status
                            </label>
                            <select
                                name="status"
                                id="sub_category_status"
                                class="form-select"
                                required
                            >
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
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnSaveSubCategory"
                    >
                        Save Sub Category
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>



{{-- ================================================================
     MODAL EDIT SUB CATEGORY
================================================================ --}}
<div
    class="modal fade"
    id="modalEditSubCategory"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg">
        <form id="formEditSubCategory">
            @csrf
            <input
                type="hidden"
                name="id"
                id="edit_sub_category_id"
            >
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Sub Category
                    </h5>
                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                    ></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Category
                            </label>
                            <select
                                name="category_id"
                                id="edit_sub_category_parent"
                                class="form-select"
                                required
                            >
                                <option value="">
                                    Select Category
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Name
                            </label>
                            <input
                                type="text"
                                name="sub_category_name"
                                id="edit_sub_category_name"
                                class="form-control"
                                required
                            >
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Description
                            </label>
                            <textarea
                                name="description"
                                id="edit_sub_category_description"
                                class="form-control"
                                rows="3"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">
                                Status
                            </label>
                            <select
                                name="status"
                                id="edit_sub_category_status"
                                class="form-select"
                                required
                            >
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
                        data-bs-dismiss="modal"
                    >
                        Close
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        id="btnUpdateSubCategory"
                    >
                        Save Sub Category
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


    /* =========================================================
       CATEGORY DATATABLE
       ========================================================= */

    let categoryTable = $('#table-category').DataTable({

        processing: true,

        serverSide: true,

        responsive: true,

        autoWidth: false,

        ajax: "{{ route('category.data') }}",

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },

            {
                data: 'category_name',
                name: 'category_name'
            },

            {
                data: 'category_code',
                name: 'category_code'
            },

            {
                data: 'description',
                name: 'description'
            },

            {
                data: 'total_asset',
                name: 'total_asset',
                orderable: false,
                searchable: false,
                className: 'text-center'
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

        order: [[2, 'asc']]

    });



    /* =========================================================
       EXPAND CATEGORY
       ========================================================= */

    $(document).on(
        'click',
        '.btn-expand-category',
        function () {

            let button = $(this);

            let categoryId =
                button.data('id');

            let row =
                categoryTable
                    .row(button.closest('tr'));



            /* -------------------------------------------------
               Already open
            ------------------------------------------------- */

            if (row.child.isShown()) {

                row.child.hide();

                button.removeClass('open');

                return;

            }



            /* -------------------------------------------------
               Show loading
            ------------------------------------------------- */

            row.child(`
                <div class="subcategory-container">

                    <div class="subcategory-loading">

                        <i class="fa-solid fa-spinner fa-spin me-1"></i>

                        Loading Sub Category...

                    </div>

                </div>
            `).show();

            button.addClass('open');



            /* -------------------------------------------------
               Load ONLY when opened
            ------------------------------------------------- */

            loadSubCategories(
                row,
                categoryId,
                1
            );

        }
    );



    /* =========================================================
       LOAD SUB CATEGORY
       ========================================================= */

    function loadSubCategories(
        row,
        categoryId,
        page
    )
    {

        $.ajax({

            url:
                "{{ route('category.subcategories', ['categoryId' => ':id']) }}"
                    .replace(':id', categoryId),

            type: 'GET',

            data: {

                page: page,

                length: 10

            },

            success: function (response) {

                if (!response.success) {

                    row.child(`
                        <div class="subcategory-container">

                            <div class="subcategory-empty">

                                Failed to load Sub Category.

                            </div>

                        </div>
                    `).show();

                    return;

                }


                row.child(
                    buildSubCategoryHtml(
                        response.category,
                        response.data,
                        response.meta
                    )
                ).show();

            },

            error: function () {

                row.child(`
                    <div class="subcategory-container">

                        <div class="subcategory-empty">

                            Failed to load Sub Category.

                        </div>

                    </div>
                `).show();

            }

        });

    }



    /* =========================================================
       BUILD SUB CATEGORY
       ========================================================= */

    function buildSubCategoryHtml(
        category,
        subCategories,
        meta
    )
    {

        let html = `

            <div class="subcategory-container">

                <div class="subcategory-header">

                    <div class="subcategory-title">

                        <i class="fa-solid fa-layer-group me-1"></i>

                        Sub Category

                        <span class="text-muted ms-1">

                            (${meta.total})

                        </span>

                    </div>


                    <button
                        type="button"
                        class="btn-add-subcategory btn-add-sub"
                        data-category-id="${category.id}"
                        data-category-name="${escapeHtml(category.category_name)}"
                    >

                        <i class="fa-solid fa-plus me-1"></i>

                        Add Sub Category

                    </button>

                </div>

        `;



        /* =====================================================
           EMPTY
        ===================================================== */

        if (
            !subCategories ||
            subCategories.length === 0
        ) {

            html += `

                <div class="subcategory-empty">

                    <i class="fa-regular fa-folder-open me-1"></i>

                    Belum ada Sub Category

                </div>

            `;

            html += `</div>`;

            return html;

        }



        /* =====================================================
           TABLE
        ===================================================== */

        html += `

            <div class="table-responsive">

                <table class="table subcategory-table">

                    <thead>

                        <tr>

                            <th width="5%">
                                #
                            </th>

                            <th>
                                Name
                            </th>

                            <th>
                                Code
                            </th>

                            <th>
                                Description
                            </th>

                            <th>
                                Total Asset
                            </th>

                            <th>
                                Status
                            </th>

                            <th width="10%">
                                Action
                            </th>

                        </tr>

                    </thead>

                    <tbody>

        `;



        subCategories.forEach(
            function (sub, index) {

                let number =
                    (
                        (meta.current_page - 1) *
                        meta.per_page
                    ) +
                    index +
                    1;



                let statusHtml = '';



                if (sub.status == 1) {

                    statusHtml = `

                        <span class="badge-status badge-active">

                            <i class="fa-solid fa-circle-check me-1"></i>

                            Active

                        </span>

                    `;

                } else {

                    statusHtml = `

                        <span class="badge-status badge-inactive">

                            <i class="fa-solid fa-circle-xmark me-1"></i>

                            Inactive

                        </span>

                    `;

                }



                html += `

                    <tr>

                        <td>
                            ${number}
                        </td>

                        <td>

                            <div class="subcategory-name">

                                ${escapeHtml(
                                    sub.sub_category_name
                                )}

                            </div>

                        </td>

                        <td>

                            <div class="subcategory-code">

                                ${escapeHtml(
                                    sub.sub_category_code ?? ''
                                )}

                            </div>

                        </td>

                        <td>

                            ${escapeHtml(
                                sub.description ?? '-'
                            )}

                        </td>

                        <td>

                            ${sub.assets_count ?? 0}

                        </td>

                        <td>

                            ${statusHtml}

                        </td>

                        <td>

                            <div class="d-flex gap-1">

                                <button
                                    type="button"
                                    class="btn-action btn-edit-subcategory"
                                    data-id="${sub.id}"
                                    title="Edit"
                                >

                                    <i class="fa-solid fa-pen-to-square"></i>

                                </button>


                                <button
                                    type="button"
                                    class="btn-action btn-delete-subcategory"
                                    data-id="${sub.id}"
                                    title="Delete"
                                >

                                    <i class="fa-solid fa-trash"></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            }
        );



        html += `

                    </tbody>

                </table>

            </div>

        `;



        /* =====================================================
           PAGINATION
        ===================================================== */

        if (meta.last_page > 1) {

            html += `

                <div class="subcategory-pagination-wrapper">

                    <nav>

                        <ul class="pagination pagination-sm subcategory-pagination">

            `;



            /* Previous */

            html += `

                <li class="page-item
                    ${meta.current_page <= 1 ? 'disabled' : ''}"
                >

                    <button
                        type="button"
                        class="page-link subcategory-page"
                        data-page="${meta.current_page - 1}"
                        data-category-id="${category.id}"
                    >

                        <i class="fa-solid fa-chevron-left"></i>

                    </button>

                </li>

            `;



            /*
            |--------------------------------------------------------------------------
            | Page numbers
            |--------------------------------------------------------------------------
            */

            let startPage =
                Math.max(
                    1,
                    meta.current_page - 2
                );

            let endPage =
                Math.min(
                    meta.last_page,
                    meta.current_page + 2
                );



            for (
                let i = startPage;
                i <= endPage;
                i++
            ) {

                html += `

                    <li class="page-item
                        ${i === meta.current_page ? 'active' : ''}"
                    >

                        <button
                            type="button"
                            class="page-link subcategory-page"
                            data-page="${i}"
                            data-category-id="${category.id}"
                        >

                            ${i}

                        </button>

                    </li>

                `;

            }



            /* Next */

            html += `

                <li class="page-item
                    ${meta.current_page >= meta.last_page ? 'disabled' : ''}"
                >

                    <button
                        type="button"
                        class="page-link subcategory-page"
                        data-page="${meta.current_page + 1}"
                        data-category-id="${category.id}"
                    >

                        <i class="fa-solid fa-chevron-right"></i>

                    </button>

                </li>

            `;



            html += `

                        </ul>

                    </nav>

                </div>

            `;

        }



        html += `</div>`;



        return html;

    }



    /* =========================================================
       SUBCATEGORY PAGINATION
       ========================================================= */

    $(document).on(
        'click',
        '.subcategory-page',
        function () {

            let button =
                $(this);

            let page =
                parseInt(
                    button.data('page')
                );

            let categoryId =
                button.data('category-id');


            if (page < 1) {
                return;
            }


            let row =
                categoryTable
                    .row(
                        button.closest(
                            'tr'
                        )
                    );


            /*
            |--------------------------------------------------------------------------
            | Karena button berada di child row,
            | cari parent row.
            |--------------------------------------------------------------------------
            */

            let parentRow =
                button
                    .closest(
                        '.subcategory-container'
                    )
                    .closest(
                        'tr'
                    );


            /*
            |--------------------------------------------------------------------------
            | DataTables child row punya struktur sendiri.
            | Kita ambil parent berdasarkan category ID.
            |--------------------------------------------------------------------------
            */

            let parentTr =
                $('#table-category tbody tr')
                    .filter(function () {

                        let data =
                            categoryTable
                                .row(this)
                                .data();

                        return data &&
                            String(data.id) ===
                            String(categoryId);

                    });


            if (!parentTr.length) {
                return;
            }


            let parentDataRow =
                categoryTable.row(
                    parentTr
                );


            parentDataRow.child(`
                <div class="subcategory-container">

                    <div class="subcategory-loading">

                        <i class="fa-solid fa-spinner fa-spin me-1"></i>

                        Loading...

                    </div>

                </div>
            `).show();


            loadSubCategories(
                parentDataRow,
                categoryId,
                page
            );

        }
    );



    /* =========================================================
       ADD CATEGORY
       ========================================================= */

    $('#formAddCategory').submit(
        function (e) {

            e.preventDefault();


            let form = this;


            if (!form.checkValidity()) {

                form.reportValidity();

                return;

            }


            let btn =
                $('#btnSaveCategory');


            btn.prop(
                'disabled',
                true
            );


            btn.html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...'
            );


            $.ajax({

                url:
                    "{{ route('category.store') }}",

                type:
                    'POST',

                data:
                    new FormData(form),

                processData:
                    false,

                contentType:
                    false,

                headers: {

                    Accept:
                        'application/json'

                },


                success:
                    function (response) {

                        if (response.success) {

                            Swal.fire({

                                icon:
                                    'success',

                                title:
                                    'Berhasil',

                                text:
                                    response.message,

                                timer:
                                    1500,

                                showConfirmButton:
                                    false

                            });


                            bootstrap.Modal
                                .getInstance(
                                    document.getElementById(
                                        'modalAddCategory'
                                    )
                                )
                                .hide();


                            form.reset();


                            categoryTable.ajax.reload(
                                null,
                                false
                            );

                        }

                    },


                error:
                    function (xhr) {

                        showAjaxError(
                            xhr,
                            'Category gagal disimpan.'
                        );

                    },


                complete:
                    function () {

                        btn.prop(
                            'disabled',
                            false
                        );

                        btn.html(
                            'Save Category'
                        );

                    }

            });

        }
    );



    /* =========================================================
       EDIT CATEGORY
       ========================================================= */

    $(document).on(
        'click',
        '.btn-edit',
        function () {

            let id =
                $(this).data('id');


            let url =
                "{{ route('category.edit', ['id' => ':id']) }}"
                    .replace(
                        ':id',
                        id
                    );


            $.get(
                url,
                function (response) {

                    $('#edit_id')
                        .val(response.id);

                    $('#edit_category_name')
                        .val(
                            response.category_name
                        );

                    $('#edit_description')
                        .val(
                            response.description
                        );

                    $('#edit_status')
                        .val(
                            response.status
                        );


                    new bootstrap.Modal(
                        document.getElementById(
                            'modalEditCategory'
                        )
                    ).show();

                }
            );

        }
    );



    /* =========================================================
       UPDATE CATEGORY
       ========================================================= */

    $('#formEditCategory').submit(
        function (e) {

            e.preventDefault();


            let form = this;


            if (!form.checkValidity()) {

                form.reportValidity();

                return;

            }


            let btn =
                $('#btnUpdateCategory');


            btn.prop(
                'disabled',
                true
            );


            btn.html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...'
            );


            $.ajax({

                url:
                    "{{ route('category.update') }}",

                type:
                    'POST',

                data:
                    new FormData(form),

                processData:
                    false,

                contentType:
                    false,

                headers: {

                    Accept:
                        'application/json'

                },


                success:
                    function (response) {

                        if (response.success) {

                            Swal.fire({

                                icon:
                                    'success',

                                title:
                                    'Berhasil',

                                text:
                                    response.message,

                                timer:
                                    1500,

                                showConfirmButton:
                                    false

                            });


                            bootstrap.Modal
                                .getInstance(
                                    document.getElementById(
                                        'modalEditCategory'
                                    )
                                )
                                .hide();


                            categoryTable.ajax.reload(
                                null,
                                false
                            );

                        }

                    },


                error:
                    function (xhr) {

                        showAjaxError(
                            xhr,
                            'Category gagal diperbarui.'
                        );

                    },


                complete:
                    function () {

                        btn.prop(
                            'disabled',
                            false
                        );

                        btn.html(
                            'Save Category'
                        );

                    }

            });

        }
    );



    /* =========================================================
       DELETE CATEGORY
       ========================================================= */

    $(document).on(
        'click',
        '.btn-delete',
        function () {

            let id =
                $(this).data('id');


            let url =
                "{{ route('category.destroy', ['id' => ':id']) }}"
                    .replace(
                        ':id',
                        id
                    );


            Swal.fire({

                title:
                    'Hapus Category?',

                text:
                    'Data Category akan dihapus.',

                icon:
                    'warning',

                showCancelButton:
                    true,

                confirmButtonText:
                    'Ya, Hapus!',

                cancelButtonText:
                    'Batal',

                reverseButtons:
                    true

            }).then(
                function (result) {

                    if (!result.isConfirmed) {
                        return;
                    }


                    $.ajax({

                        url:
                            url,

                        type:
                            'DELETE',

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
                                        response.message,

                                    timer:
                                        1500,

                                    showConfirmButton:
                                        false

                                });


                                categoryTable.ajax.reload(
                                    null,
                                    false
                                );

                            },


                        error:
                            function (xhr) {

                                showAjaxError(
                                    xhr,
                                    'Category gagal dihapus.'
                                );

                            }

                    });

                }
            );

        }
    );



    /* =========================================================
       ADD SUB CATEGORY
       ========================================================= */

    $(document).on(
        'click',
        '.btn-add-sub',
        function () {

            let categoryId =
                $(this).data('category-id');

            let categoryName =
                $(this).data('category-name');


            $('#formAddSubCategory')[0]
                .reset();


            $('#sub_category_parent_id')
                .val(categoryId);


            $('#sub_category_parent_name')
                .val(categoryName);


            $('#sub_category_status')
                .val('1');


            new bootstrap.Modal(
                document.getElementById(
                    'modalAddSubCategory'
                )
            ).show();

        }
    );



    /* =========================================================
       SAVE SUB CATEGORY
       ========================================================= */
    $('#formAddSubCategory').submit(
        function (e) {
            e.preventDefault();
            let form = this;
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            let btn =
                $('#btnSaveSubCategory');
            btn.prop(
                'disabled',
                true
            );
            btn.html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...'
            );
            $.ajax({
                url:
                    "{{ route('category.subcategory.store') }}",
                type:
                    'POST',
                data:
                    new FormData(form),
                processData:
                    false,
                contentType:
                    false,
                headers: {
                    Accept:
                        'application/json'
                },
                success:
                    function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon:
                                    'success',
                                title:
                                    'Berhasil',
                                text:
                                    response.message,
                                timer:
                                    1500,
                                showConfirmButton:
                                    false
                            });
                            bootstrap.Modal
                                .getInstance(
                                    document.getElementById(
                                        'modalAddSubCategory'
                                    )
                                )
                                .hide();
                            form.reset();
                            /*
                            |--------------------------------------------------------------------------
                            | Reload Category table.
                            | User bisa buka Category lagi.
                            |--------------------------------------------------------------------------
                            */
                            categoryTable.ajax.reload(
                                null,
                                false
                            );
                        }
                    },
                error:
                    function (xhr) {
                        showAjaxError(
                            xhr,
                            'Sub Category gagal disimpan.'
                        );
                    },
                complete:
                    function () {
                        btn.prop(
                            'disabled',
                            false
                        );
                        btn.html(
                            'Save Sub Category'
                        );
                    }
            });
        }
    );
    /* =========================================================
       EDIT SUB CATEGORY
       ========================================================= */
    $(document).on(
        'click',
        '.btn-edit-subcategory',
        function () {
            let id =
                $(this).data('id');

            let url =
                "{{ route('category.subcategory.edit', ['id' => ':id']) }}"
                    .replace(
                        ':id',
                        id
                    );
            $.get(
                url,
                function (response) {
                    $('#edit_sub_category_id')
                        .val(response.id);
                    $('#edit_sub_category_name')
                        .val(
                            response.sub_category_name
                        );
                    $('#edit_sub_category_description')
                        .val(
                            response.description
                        );
                    $('#edit_sub_category_status')
                        .val(
                            response.status
                        );
                    loadCategoryDropdown(
                        response.category_id
                    );
                    new bootstrap.Modal(
                        document.getElementById(
                            'modalEditSubCategory'
                        )
                    ).show();
                }
            );
        }
    );
    /* =========================================================
       CATEGORY DROPDOWN
       ========================================================= */
    function loadCategoryDropdown(
        selectedId
    )
    {
        let select =
            $('#edit_sub_category_parent');
        select.html(
            '<option value="">Loading...</option>'
        );
        $.get(
            "{{ route('category.list') }}",
            function (response) {
                select.empty();
                select.append(
                    '<option value="">Select Category</option>'
                );
                if (
                    response.success &&
                    response.data
                ) {
                    response.data.forEach(
                        function (category) {
                            let option =
                                $('<option>')
                                    .val(
                                        category.id
                                    )
                                    .text(
                                        category.category_name
                                    );
                            if (
                                String(category.id) ===
                                String(selectedId)
                            ) {
                                option.prop(
                                    'selected',
                                    true
                                );
                            }
                            select.append(
                                option
                            );
                        }
                    );
                }
            }
        );
    }
    /* =========================================================
       UPDATE SUB CATEGORY
       ========================================================= */
    $('#formEditSubCategory').submit(
        function (e) {
            e.preventDefault();
            let form = this;
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            let btn =
                $('#btnUpdateSubCategory');
            btn.prop(
                'disabled',
                true
            );
            btn.html(
                '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...'
            );
            $.ajax({
                url:
                    "{{ route('category.subcategory.update') }}",
                type:
                    'POST',
                data:
                    new FormData(form),
                processData:
                    false,
                contentType:
                    false,
                headers: {
                    Accept:
                        'application/json'
                },
                success:
                    function (response) {
                        if (response.success) {
                            Swal.fire({
                                icon:
                                    'success',
                                title:
                                    'Berhasil',
                                text:
                                    response.message,
                                timer:
                                    1500,
                                showConfirmButton:
                                    false
                            });
                            bootstrap.Modal
                                .getInstance(
                                    document.getElementById(
                                        'modalEditSubCategory'
                                    )
                                )
                                .hide();
                            categoryTable.ajax.reload(
                                null,
                                false
                            );
                        }
                    },
                error:
                    function (xhr) {
                        showAjaxError(
                            xhr,
                            'Sub Category gagal diperbarui.'
                        );
                    },
                complete:
                    function () {
                        btn.prop(
                            'disabled',
                            false
                        );
                        btn.html(
                            'Save Sub Category'
                        );
                    }
            });
        }
    );
    /* =========================================================
       DELETE SUB CATEGORY
       ========================================================= */
    $(document).on(
        'click',
        '.btn-delete-subcategory',
        function () {
            let id =
                $(this).data('id');
            let url =
                "{{ route('category.subcategory.destroy', ['id' => ':id']) }}"
                    .replace(
                        ':id',
                        id
                    );
            Swal.fire({
                title:
                    'Hapus Sub Category?',
                text:
                    'Data yang sudah dihapus tidak bisa dikembalikan!',
                icon:
                    'warning',
                showCancelButton:
                    true,
                confirmButtonText:
                    'Ya, Hapus!',
                cancelButtonText:
                    'Batal',
                reverseButtons:
                    true
            }).then(
                function (result) {
                    if (!result.isConfirmed) {
                        return;
                    }
                    $.ajax({
                        url:
                            url,
                        type:
                            'DELETE',
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
                                        response.message,
                                    timer:
                                        1500,
                                    showConfirmButton:
                                        false
                                });
                                categoryTable.ajax.reload(
                                    null,
                                    false
                                );
                            },
                        error:
                            function (xhr) {
                                showAjaxError(
                                    xhr,
                                    'Sub Category gagal dihapus.'
                                );
                            }
                    });
                }
            );
        }
    );
    /* =========================================================
       ESCAPE HTML
       ========================================================= */
    function escapeHtml(value)
    {
        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }
        return $('<div>')
            .text(value)
            .html();
    }
    /* =========================================================
       AJAX ERROR
       ========================================================= */
    function showAjaxError(
        xhr,
        defaultMessage
    )
    {
        if (
            xhr.status === 422 &&
            xhr.responseJSON &&
            xhr.responseJSON.errors
        ) {
            let message = '';
            $.each(
                xhr.responseJSON.errors,
                function (
                    key,
                    value
                ) {
                    if (
                        Array.isArray(value)
                    ) {
                        message +=
                            value[0] +
                            '<br>';
                    } else {
                        message +=
                            value +
                            '<br>';
                    }
                }
            );
            Swal.fire({
                icon:
                    'error',
                title:
                    'Validasi Gagal',
                html:
                    message
            });
            return;
        }
        Swal.fire({
            icon:
                'error',
            title:
                'Gagal',
            text:
                xhr.responseJSON?.message ??
                defaultMessage
        });
    }
});
</script>
@endsection