@extends('dashboard.layouts.wrapper')

@section('title', 'Assets')

@section('content')

<style>

    .swal2-container {
        z-index: 99999 !important;
    }

    .asset-table-wrapper {
        min-height: 300px;
    }

    .asset-code {
        font-weight: 600;
        color: #696cff;
    }

    .asset-name {
        font-weight: 600;
        color: #333;
    }

    .status-badge {
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 20px;
    }

    .filter-card {
        background: #f8f9fc !important;

        border-top: 1px solid #e1e3ea !important;
        border-bottom: 1px solid #e1e3ea !important;

        border-left: 3px solid #696cff !important;
        border-right: 3px solid #696cff !important;

        border-radius: 14px !important;

        padding: 20px !important;

        margin-bottom: 20px;

        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06) !important;
    }
    .filter-title {
        font-size: 14px;
        font-weight: 600;
        color: #566a7f;
    }

    .filter-label {
        font-size: 12px;
        font-weight: 600;
        color: #566a7f;
        margin-bottom: 6px;
    }

    .btn-action {
        width: 36px;
        height: 36px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

</style>

<div class="content-wrapper">
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="row">
        <div class="col-xxl-12 mb-4">

            <div class="card">

                <!-- ===================================================== -->
                <!-- HEADER -->
                <!-- ===================================================== -->
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            Assets
                        </h5>
                        <small class="text-muted">
                            Manage company assets
                        </small>
                    </div>
                   <div class="d-flex gap-2">

    {{-- Export Asset --}}
    @if(auth()->user()->hasPermission('asset.export'))
        <a
            href="{{ route('assets.export') }}"
            class="btn btn-success"
        >
            <i class="fa-solid fa-file-excel me-1"></i>
            Export Excel
        </a>
    @endif

    {{-- Print QR --}}
    @if(auth()->user()->hasPermission('asset.print_qr'))
        <button
            type="button"
            class="btn btn-dark"
            id="btnPrintQr"
        >
            <i class="fa-solid fa-qrcode me-1"></i>
            Print QR
        </button>
    @endif

    {{-- Add Asset --}}
    @if(auth()->user()->hasPermission('asset.create'))
        <a
            href="{{ route('assets.create') }}"
            class="btn btn-primary"
        >
            <i class="fa-solid fa-plus me-1"></i>
            Add Asset
        </a>
    @endif

</div>
                </div>
                <!-- ===================================================== -->
                <!-- BODY -->
                <!-- ===================================================== -->
                <div class="card-body">
                    <!-- ================================================= -->
                    <!-- FILTER -->
                    <!-- ================================================= -->
                    <div class="filter-card p-3 mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="filter-title">
                                    <i class="fa-solid fa-filter me-1"></i>
                                    Filter Assets
                                </div>
                                <small class="text-muted">
                                    Gunakan filter untuk mencari asset
                                </small>
                            </div>
                            <button
                                type="button"
                                class="btn btn-sm btn-label-secondary"
                                id="btnResetFilter"
                            >
                                <i class="fa-solid fa-rotate-left me-1"></i>
                                Reset
                            </button>
                        </div>
                        <div class="row g-3">
                            <!-- SEARCH -->
                            <div class="col-md-4">
                                <label class="form-label filter-label">
                                    Search Asset
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">

                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input
                                        type="text"
                                        id="search_asset"
                                        class="form-control"
                                        placeholder="Asset code / asset name"
                                    >
                                </div>
                            </div>


                            <!-- CATEGORY -->

                            <div class="col-md-4">

                                <label class="form-label filter-label">

                                    Category

                                </label>

                                <select
                                    id="category_id"
                                    class="form-select"
                                >

                                    <option value="">
                                        All Categories
                                    </option>

                                    @foreach($categories ?? [] as $category)

                                        <option
                                            value="{{ $category->id }}"
                                        >

                                            {{ $category->category_name }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <!-- SUB CATEGORY -->

                            <div class="col-md-4">

                                <label class="form-label filter-label">

                                    Sub Category

                                </label>

                                <select
                                    id="sub_category_id"
                                    class="form-select"
                                >

                                    <option value="">
                                        All Sub Categories
                                    </option>

                                    @foreach($subCategories ?? [] as $subCategory)

                                        <option
                                            value="{{ $subCategory->id }}"
                                            data-category="{{ $subCategory->category_id }}"
                                        >

                                            {{ $subCategory->sub_category_name }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <!-- VENDOR -->

                            <div class="col-md-4">

                                <label class="form-label filter-label">

                                    Vendor

                                </label>

                                <select
                                    id="vendor_id"
                                    class="form-select"
                                >

                                    <option value="">
                                        All Vendors
                                    </option>

                                    @foreach($vendors ?? [] as $vendor)

                                        <option
                                            value="{{ $vendor->id }}"
                                        >

                                            {{ $vendor->vendor_name }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>


                            <!-- STATUS -->

                            <div class="col-md-4">

                                <label class="form-label filter-label">

                                    Status

                                </label>

                                <select
                                    id="status"
                                    class="form-select"
                                >

                                    <option value="">
                                        All Status
                                    </option>

                                    <option value="1">
                                        Active
                                    </option>

                                    <option value="0">
                                        Inactive
                                    </option>

                                </select>

                            </div>


                            <!-- CREATED FROM -->

                            <div class="col-md-2">

                                <label class="form-label filter-label">

                                    Created From

                                </label>

                                <input
                                    type="date"
                                    id="created_from"
                                    class="form-control"
                                >

                            </div>


                            <!-- CREATED TO -->

                            <div class="col-md-2">

                                <label class="form-label filter-label">

                                    Created To

                                </label>

                                <input
                                    type="date"
                                    id="created_to"
                                    class="form-control"
                                >

                            </div>

                        </div>


                        <!-- FILTER ACTION -->

                        <div class="d-flex justify-content-end mt-3">

                            <button
                                type="button"
                                class="btn btn-primary"
                                id="btnApplyFilter"
                            >

                                <i class="fa-solid fa-magnifying-glass me-1"></i>

                                Search

                            </button>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- SELECTION INFORMATION -->
                    <!-- ================================================= -->

                    <div
                        id="selectionInfo"
                        class="alert alert-primary d-none mb-3"
                    >

                        <div class="d-flex justify-content-between align-items-center">

                            <div>

                                <i class="fa-solid fa-circle-check me-2"></i>

                                <strong>
                                    <span id="selectedCount">0</span>
                                </strong>

                                asset dipilih.

                            </div>


                            <button
                                type="button"
                                class="btn btn-sm btn-outline-primary"
                                id="btnClearSelection"
                            >

                                Clear Selection

                            </button>

                        </div>

                    </div>


                    <!-- ================================================= -->
                    <!-- TABLE -->
                    <!-- ================================================= -->

                    <div class="table-responsive asset-table-wrapper">

                        <table
                            class="table table-hover align-middle"
                            id="table-assets"
                            width="100%"
                        >

                            <thead>

                                <tr>

                                    <!-- CHECKBOX -->

                                    <th
                                        width="4%"
                                        class="text-center"
                                    >

                                        <input
                                            type="checkbox"
                                            id="checkAllAssets"
                                            class="form-check-input"
                                            title="Select All"
                                        >

                                    </th>


                                    <!-- NO -->

                                    <th width="5%">
                                        No
                                    </th>


                                    <!-- ASSET CODE -->

                                    <th>
                                        Asset Code
                                    </th>


                                    <!-- ASSET NAME -->

                                    <th>
                                        Asset Name
                                    </th>


                                    <!-- CATEGORY -->

                                    <th>
                                        Category
                                    </th>


                                    <!-- SUB CATEGORY -->

                                    <th>
                                        Sub Category
                                    </th>


                                    <!-- VENDOR -->

                                    <th>
                                        Vendor
                                    </th>


                                    <!-- RESPONSIBLE -->

                                    <th>
                                        Responsible
                                    </th>


                                    <!-- PRICE -->

                                    <th>
                                        Price
                                    </th>


                                    <!-- PURCHASE DATE -->

                                    <th>
                                        Purchase Date
                                    </th>


                                    <!-- STATUS -->

                                    <th>
                                        Status
                                    </th>


                                    <!-- ACTION -->

                                    <th width="12%">
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
```

</div>

@endsection

@section('js')

<script>

$(function () {


    /*
    |--------------------------------------------------------------------------
    | SELECTED ASSETS
    |--------------------------------------------------------------------------
    |
    | Menyimpan ID asset yang dipilih.
    | Untuk sekarang selection tetap berjalan antar draw/page.
    |
    */

    let selectedAssets = new Set();


    /*
    |--------------------------------------------------------------------------
    | DATATABLE
    |--------------------------------------------------------------------------
    */

    let table = $('#table-assets').DataTable({

        processing: true,

        serverSide: true,

        responsive: true,

        autoWidth: false,


        ajax: {

            url: "{{ route('assets.data') }}",

            data: function (d) {

                d.search_asset =
                    $('#search_asset').val();

                d.category_id =
                    $('#category_id').val();

                d.sub_category_id =
                    $('#sub_category_id').val();

                d.vendor_id =
                    $('#vendor_id').val();

                d.status =
                    $('#status').val();

                d.created_from =
                    $('#created_from').val();

                d.created_to =
                    $('#created_to').val();

            }

        },


        columns: [


            /*
            |--------------------------------------------------------------------------
            | CHECKBOX
            |--------------------------------------------------------------------------
            */

            {

                data: 'checkbox',

                name: 'checkbox',

                orderable: false,

                searchable: false,

                className: 'text-center'

            },


            /*
            |--------------------------------------------------------------------------
            | NO
            |--------------------------------------------------------------------------
            */

            {

                data: 'DT_RowIndex',

                name: 'DT_RowIndex',

                orderable: false,

                searchable: false

            },


            /*
            |--------------------------------------------------------------------------
            | ASSET CODE
            |--------------------------------------------------------------------------
            */

            {

                data: 'asset_code',

                name: 'asset_code'

            },


            /*
            |--------------------------------------------------------------------------
            | ASSET NAME
            |--------------------------------------------------------------------------
            */

            {

                data: 'asset_name',

                name: 'asset_name'

            },


            /*
            |--------------------------------------------------------------------------
            | CATEGORY
            |--------------------------------------------------------------------------
            */

            {

                data: 'category_name',

                name: 'category_name'

            },


            /*
            |--------------------------------------------------------------------------
            | SUB CATEGORY
            |--------------------------------------------------------------------------
            */

            {

                data: 'sub_category_name',

                name: 'sub_category_name'

            },


            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            {

                data: 'vendor_name',

                name: 'vendor_name'

            },


            /*
            |--------------------------------------------------------------------------
            | RESPONSIBLE
            |--------------------------------------------------------------------------
            */

            {

                data: 'responsible_name',

                name: 'responsible_name'

            },


            /*
            |--------------------------------------------------------------------------
            | PURCHASE PRICE
            |--------------------------------------------------------------------------
            */

            {

                data: 'purchase_price',

                name: 'purchase_price'

            },


            /*
            |--------------------------------------------------------------------------
            | PURCHASE DATE
            |--------------------------------------------------------------------------
            */

            {

                data: 'purchase_date',

                name: 'purchase_date'

            },


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            {

                data: 'status',

                name: 'status',

                orderable: false

            },


            /*
            |--------------------------------------------------------------------------
            | ACTION
            |--------------------------------------------------------------------------
            */

            {

                data: 'action',

                name: 'action',

                orderable: false,

                searchable: false

            }

        ],


        /*
        |--------------------------------------------------------------------------
        | DEFAULT ORDER
        |--------------------------------------------------------------------------
        */

        order: [

            [3, 'asc']

        ]

    });


    /*
    |--------------------------------------------------------------------------
    | APPLY FILTER
    |--------------------------------------------------------------------------
    */

    $('#btnApplyFilter').on(
        'click',
        function () {

            table.ajax.reload();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | ENTER = SEARCH
    |--------------------------------------------------------------------------
    */

    $('#search_asset').on(
        'keypress',
        function (e) {

            if (e.which === 13) {

                table.ajax.reload();

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESET FILTER
    |--------------------------------------------------------------------------
    */

    $('#btnResetFilter').on(
        'click',
        function () {

            $('#search_asset').val('');

            $('#category_id').val('');

            $('#sub_category_id').val('');

            $('#vendor_id').val('');

            $('#status').val('');

            $('#created_from').val('');

            $('#created_to').val('');


            table.ajax.reload();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | SUB CATEGORY FILTER
    |--------------------------------------------------------------------------
    */

    $('#category_id').on(
        'change',
        function () {

            let categoryId =
                $(this).val();

            let subCategory =
                $('#sub_category_id');


            subCategory
                .find('option')
                .each(function () {

                    let option =
                        $(this);


                    if (!option.val()) {

                        option.show();

                        return;

                    }


                    let optionCategory =
                        option.data('category');


                    if (
                        !categoryId ||
                        String(optionCategory) ===
                        String(categoryId)
                    ) {

                        option.show();

                    } else {

                        option.hide();

                    }

                });


            /*
            |----------------------------------------------------------------------
            | Reset sub category
            |----------------------------------------------------------------------
            */

            if (
                subCategory
                    .find('option:selected')
                    .css('display') === 'none'
            ) {

                subCategory.val('');

            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CHECKBOX INDIVIDUAL
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        '.asset-checkbox',
        function () {

            let id =
                String($(this).val());


            if ($(this).is(':checked')) {

                selectedAssets.add(id);

            } else {

                selectedAssets.delete(id);

            }


            updateSelectionUI();

            updateCheckAllState();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CHECK ALL CURRENT PAGE
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'change',
        '#checkAllAssets',
        function () {

            let checked =
                $(this).is(':checked');


            $('.asset-checkbox').each(
                function () {

                    let id =
                        String($(this).val());


                    $(this).prop(
                        'checked',
                        checked
                    );


                    if (checked) {

                        selectedAssets.add(id);

                    } else {

                        selectedAssets.delete(id);

                    }

                }
            );


            updateSelectionUI();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | UPDATE CHECK ALL STATE
    |--------------------------------------------------------------------------
    */

    function updateCheckAllState() {

        let checkboxes =
            $('.asset-checkbox');


        let checked =
            $('.asset-checkbox:checked');


        $('#checkAllAssets').prop(

            'checked',

            checkboxes.length > 0 &&
            checkboxes.length === checked.length

        );

    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE SELECTION UI
    |--------------------------------------------------------------------------
    */

    function updateSelectionUI() {

        let count =
            selectedAssets.size;


        $('#selectedCount').text(
            count
        );


        if (count > 0) {

            $('#selectionInfo')
                .removeClass('d-none');

        } else {

            $('#selectionInfo')
                .addClass('d-none');

        }

    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE CHECKBOX AFTER DATATABLE DRAW
    |--------------------------------------------------------------------------
    */

    $('#table-assets').on(
        'draw.dt',
        function () {

            $('.asset-checkbox').each(
                function () {

                    let id =
                        String($(this).val());


                    $(this).prop(

                        'checked',

                        selectedAssets.has(id)

                    );

                }
            );


            updateCheckAllState();

            updateSelectionUI();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | CLEAR SELECTION
    |--------------------------------------------------------------------------
    */

    $('#btnClearSelection').on(
        'click',
        function () {

            selectedAssets.clear();


            $('.asset-checkbox')
                .prop('checked', false);


            $('#checkAllAssets')
                .prop('checked', false);


            updateSelectionUI();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | PRINT QR
    |--------------------------------------------------------------------------
    */

    $('#btnPrintQr').on(
        'click',
        function () {

            let ids =
                Array.from(selectedAssets);


            /*
            |----------------------------------------------------------------------
            | VALIDATION
            |----------------------------------------------------------------------
            */

            if (ids.length === 0) {

                Swal.fire({

                    icon: 'warning',

                    title: 'Belum ada asset dipilih',

                    text:
                        'Silakan pilih minimal satu asset untuk dicetak QR.',

                    confirmButtonText: 'OK'

                });

                return;

            }


            /*
            |--------------------------------------------------------------------------
            | UNTUK SEKARANG
            |--------------------------------------------------------------------------
            |
            | Selection dikirim menggunakan POST agar tidak membuat
            | URL menjadi panjang.
            |
            | Endpoint print-qr nantinya kita sesuaikan di Controller.
            |
            */

            let form =
                $('<form>', {

                    method: 'POST',

                    action:
                        "{{ route('assets.print-qr') }}",

                    target: '_blank'

                });


            form.append(

                $('<input>', {

                    type: 'hidden',

                    name: '_token',

                    value:
                        $('meta[name="csrf-token"]').attr('content')

                })

            );


            ids.forEach(
                function (id) {

                    form.append(

                        $('<input>', {

                            type: 'hidden',

                            name: 'ids[]',

                            value: id

                        })

                    );

                }
            );


            $('body').append(form);

            form.submit();

            form.remove();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | DROPDOWN ACTION
    |--------------------------------------------------------------------------
    */
/*
    $(document).on(
        'shown.bs.dropdown',
        '.dropdown',
        function () {

            let menu =
                $(this).find('.dropdown-menu');


            $('body').append(
                menu.detach()
            );


            let btn =
                $(this).find(
                    '[data-bs-toggle="dropdown"]'
                );


            let pos =
                btn.offset();


            menu.css({

                position: 'absolute',

                top:
                    pos.top +
                    btn.outerHeight(),

                left:
                    pos.left -
                    menu.outerWidth() +
                    btn.outerWidth(),

                display: 'block',

                zIndex: 999999

            });

        }
    );
*/

    /*
    |--------------------------------------------------------------------------
    | DELETE ASSET
    |--------------------------------------------------------------------------
    */

    $(document).on(
        'click',
        '.btn-delete',
        function () {

            let id =
                $(this).data('id');


            let url =
                "{{ route('assets.destroy', ['id' => ':id']) }}";


            url =
                url.replace(
                    ':id',
                    id
                );


            Swal.fire({

                title: 'Hapus Asset?',

                text:
                    'Data asset akan dipindahkan ke trash.',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonText:
                    'Ya, Hapus!',

                cancelButtonText:
                    'Batal',

                reverseButtons: true

            }).then(
                function (result) {

                    if (!result.isConfirmed) {

                        return;

                    }


                    $.ajax({

                        url: url,

                        type: 'DELETE',

                        data: {

                            _token:
                                $('meta[name="csrf-token"]')
                                .attr('content')

                        },

                        headers: {

                            Accept:
                                'application/json'

                        },

                        success:
                            function (response) {

                                selectedAssets.delete(
                                    String(id)
                                );


                                Swal.fire({

                                    icon: 'success',

                                    title: 'Berhasil',

                                    text:
                                        response.message ||
                                        'Asset berhasil dihapus.',

                                    timer: 1500,

                                    showConfirmButton: false

                                });


                                table.ajax.reload(
                                    null,
                                    false
                                );


                                updateSelectionUI();

                            },


                        error:
                            function (xhr) {

                                console.log(xhr);


                                Swal.fire({

                                    icon: 'error',

                                    title: 'Gagal',

                                    text:
                                        xhr.responseJSON?.message ||
                                        'Asset gagal dihapus.'

                                });

                            }

                    });

                }
            );

        }
    );

});

</script>

@endsection
