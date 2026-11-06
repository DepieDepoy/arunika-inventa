@extends('dashboard.layouts.wrapper')

@section('title', 'Data Aset')

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
        background: var(--bs-tertiary-bg, #f6f9fd) !important;
        border: 1px solid rgba(15, 27, 45, .08) !important;
        border-radius: 14px !important;
        padding: 18px 20px !important;
        margin-bottom: 20px;
    }

    /* Header & tabel — seragam dengan tampilan di landing page */
    .al-head { display:flex; justify-content:space-between; align-items:flex-start; gap:16px; flex-wrap:wrap; }
    .al-sub { display:flex; flex-wrap:wrap; gap:4px 6px; font-size:.84rem; color:#8592a6; }
    .al-sub b { color:inherit; font-weight:700; }
    .al-toolbar { display:flex; gap:8px; flex-wrap:wrap; }
    .al-tbtn { display:inline-flex; align-items:center; gap:6px; font-size:.85rem; font-weight:700; padding:8px 14px; border-radius:10px; border:1px solid rgba(15,27,45,.12); background:var(--bs-body-bg); color:inherit; }
    .al-tbtn:hover { border-color:#1f5fbf; color:#1f5fbf; }
    .al-tbtn-primary, .al-tbtn-primary:hover { background:linear-gradient(125deg,#0b2447 0%,#163f86 38%,#1f5fbf 64%,#14b8a6 118%); color:#fff; border-color:transparent; }
    #table-assets thead th { font-size:.68rem; letter-spacing:.08em; text-transform:uppercase; color:#8592a6; font-weight:800; white-space:nowrap; }
    #table-assets td { font-size:.86rem; }
    .al-code { font-weight:800; color:#1f5fbf; white-space:nowrap; }
    .al-name { font-weight:700; }
    .al-meta { font-size:.75rem; color:#8592a6; }
    .al-muted { color:#8592a6; }
    .al-holder { display:inline-flex; align-items:center; gap:8px; white-space:nowrap; }
    .al-ava { width:26px; height:26px; border-radius:50%; display:inline-grid; place-items:center; color:#fff; font-size:.62rem; font-weight:800; flex:none; }
    .al-chip { display:inline-block; font-size:.7rem; font-weight:800; padding:3px 9px; border-radius:999px; white-space:nowrap; }
    .al-chip-teal { background:#ccfbf1; color:#0f766e; } .al-chip-gray { background:#eef2f7; color:#4b5a6e; } .al-chip-blue { background:#dbeafe; color:#1d4ed8; }
    .al-actions { display:flex; gap:4px; }
    .al-btn { width:32px; height:32px; border-radius:9px; display:inline-grid; place-items:center; border:1px solid rgba(15,27,45,.1); background:var(--bs-body-bg); color:#4b5a6e; }
    .al-btn:hover { border-color:#1f5fbf; color:#1f5fbf; }
    .al-btn-danger:hover { border-color:#dc2626; color:#dc2626; }
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
                <div class="card-header al-head">
                    <div>
                        <h5 class="mb-1">Data Aset</h5>
                        <div class="al-sub">
                            <span><b>{{ number_format((int) $stats->total, 0, ',', '.') }}</b> aset</span>
                            <span>· <b>{{ number_format((int) $stats->active, 0, ',', '.') }}</b> aktif</span>
                            <span>· <b>{{ number_format((int) $stats->assigned, 0, ',', '.') }}</b> dipegang karyawan</span>
                            <span>· <b>{{ $categories->count() }}</b> kategori</span>
                            <span>· nilai beli <b>Rp{{ number_format((float) $stats->value, 0, ',', '.') }}</b></span>
                        </div>
                    </div>
                    <div class="al-toolbar">
                        @if(auth()->user()->hasPermission('asset.import'))
                            <a href="{{ route('assets.import') }}" class="al-tbtn"><i class="bi bi-upload"></i> Import Excel</a>
                        @endif
                        @if(auth()->user()->hasPermission('asset.export'))
                            <a href="{{ route('assets.export') }}" class="al-tbtn"><i class="bi bi-file-earmark-spreadsheet"></i> Export</a>
                        @endif
                        @if(auth()->user()->hasPermission('asset.print_qr'))
                            <button type="button" class="al-tbtn" id="btnPrintQr" title="Centang aset di tabel, lalu cetak"><i class="bi bi-qr-code"></i> Cetak QR</button>
                        @endif
                        @if(auth()->user()->hasPermission('asset.create'))
                            <a href="{{ route('assets.create') }}" class="al-tbtn al-tbtn-primary"><i class="bi bi-plus-lg"></i> Tambah Aset</a>
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
                                    Filter Aset
                                </div>
                                <small class="text-muted">
                                    Saring aset berdasarkan kategori, vendor, status, atau tanggal dibuat
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
                                    Cari aset
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">

                                        <i class="fa-solid fa-magnifying-glass"></i>
                                    </span>
                                    <input
                                        type="text"
                                        id="search_asset"
                                        class="form-control"
                                        placeholder="Kode atau nama aset"
                                    >
                                </div>
                            </div>


                            <!-- CATEGORY -->

                            <div class="col-md-4">

                                <label class="form-label filter-label">

                                    Kategori

                                </label>

                                <select
                                    id="category_id"
                                    class="form-select"
                                >

                                    <option value="">
                                        Semua kategori
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

                                    Subkategori

                                </label>

                                <select
                                    id="sub_category_id"
                                    class="form-select"
                                >

                                    <option value="">
                                        Semua subkategori
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
                                        Semua vendor
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
                                        Semua status
                                    </option>

                                    <option value="1">
                                        Aktif
                                    </option>

                                    <option value="0">
                                        Tidak aktif
                                    </option>

                                </select>

                            </div>


                            <!-- CREATED FROM -->

                            <div class="col-md-2">

                                <label class="form-label filter-label">

                                    Dibuat dari

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

                                    Sampai

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

                                Terapkan

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

                                    <th>Kode</th>
                                    <th>Nama aset</th>
                                    <th>Pemegang</th>
                                    <th>Lokasi</th>
                                    <th>Vendor</th>
                                    <th>Harga beli</th>
                                    <th>Status</th>
                                    <th width="10%">Aksi</th>

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


            // Pemegang, lokasi, vendor, harga.
            { data: 'responsible_name', name: 'responsible_name', orderable: false, searchable: false },
            { data: 'location', name: 'location' },
            { data: 'vendor_name', name: 'vendor_name', orderable: false, searchable: false },
            { data: 'purchase_price', name: 'purchase_price' },


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
