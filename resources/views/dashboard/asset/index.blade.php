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

    .asset-description {
        max-width: 250px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .asset-thumb {
        width: 42px;
        height: 42px;
        object-fit: cover;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }

    .status-badge {
        font-size: 12px;
        padding: 5px 10px;
        border-radius: 20px;
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
    <!-- Content -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="row">
            <div class="col-xxl-12 mb-4 order-0">
                <div class="card">
                    <!-- Header -->
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
                            <!-- Export -->
                            <a href="{{ route('assets.export') }}"
                               class="btn btn-success">
                                <i class="fa-solid fa-file-excel me-1"></i>
                                Export Excel
                            </a>
                            <!-- Add -->
                            <a href="{{ route('assets.create') }}"
                               class="btn btn-primary">
                                <i class="fa-solid fa-plus me-1"></i>
                                Add Asset
                            </a>
                        </div>
                    </div>
                    <!-- Body -->
                    <div class="card-body">
                        <div class="table-responsive asset-table-wrapper">
                            <table
                                class="table table-hover align-middle"
                                id="table-assets"
                                width="100%"
                            >
                                <thead>
                                    <tr>
                                        <th width="5%">
                                            No
                                        </th>
                                        <!--<th width="8%">
                                            Photo
                                        </th>-->
                                        <th>
                                            Asset Code
                                        </th>
                                        <th>
                                            Asset Name
                                        </th>
                                        <th>
                                            Category
                                        </th>
                                        <th>
                                            Sub Category
                                        </th>
                                        <th>
                                            Vendor
                                        </th>
                                        <th>
                                            Responsible
                                        </th>
                                        <th>
                                            Price
                                        </th>
                                        <th>
                                            Purchase Date
                                        </th>
                                        <th>
                                            Status
                                        </th>
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
</div>
<!-- ========================================================= -->
<!-- PRINT QR MODAL -->
<!-- ========================================================= -->
<div
    class="modal fade"
    id="modalPrintQr"
    tabindex="-1"
    aria-hidden="true"
>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-1">
                        <i class="fa-solid fa-qrcode me-2"></i>
                        Print Asset QR
                    </h5>
                    <small class="text-muted">
                        Configure QR label before printing
                    </small>
                </div>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>
            <div class="modal-body">
                <input
                    type="hidden"
                    id="qr_asset_id"
                >
                <div class="row">
                    <!-- QR Preview -->
                    <div class="col-md-5 text-center">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="mb-3">
                                    QR Preview
                                </h6>
                                <div
                                    id="qr_preview"
                                    class="d-flex justify-content-center align-items-center"
                                    style="min-height:220px;"
                                >
                                    <div class="text-muted">
                                        <i class="fa-solid fa-qrcode fa-4x mb-3"></i>
                                        <p class="mb-0">
                                            QR Preview
                                        </p>
                                    </div>
                                </div>
                                <div
                                    id="qr_asset_name"
                                    class="fw-bold mt-3"
                                >
                                </div>
                                <div
                                    id="qr_asset_code"
                                    class="text-muted"
                                >
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Settings -->
                    <div class="col-md-7">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Label Size
                                </label>
                                <select
                                    id="qr_size"
                                    class="form-select"
                                >
                                    <option value="small">
                                        Small
                                    </option>
                                    <option value="medium" selected>
                                        Medium
                                    </option>
                                    <option value="large">
                                        Large
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Paper Size
                                </label>
                                <select
                                    id="qr_paper"
                                    class="form-select"
                                >
                                    <option value="a4">
                                        A4
                                    </option>
                                    <option value="a5">
                                        A5
                                    </option>
                                    <option value="label">
                                        Label / Sticker
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">
                                    QR Content
                                </label>
                                <select
                                    id="qr_content"
                                    class="form-select"
                                >
                                    <option value="asset">
                                        Asset Information
                                    </option>
                                    <option value="url">
                                        Asset Detail URL
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="alert alert-info">
                                    <div class="d-flex">
                                        <i class="fa-solid fa-circle-info me-2 mt-1"></i>
                                        <div>
                                            <strong>QR Information</strong>
                                            <div class="small mt-1">
                                                QR akan digunakan untuk
                                                mengidentifikasi asset.
                                                Saat discan, user dapat
                                                melihat informasi detail
                                                asset sesuai permission.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-label-secondary"
                    data-bs-dismiss="modal"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="btn btn-primary"
                    id="btnPrintQr"
                >
                    <i class="fa-solid fa-print me-1"></i>
                    Print QR
                </button>
            </div>
        </div>
    </div>
</div>
@endsection


@section('js')

<script>

$(function () {
    $('#table-assets').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,

        ajax: "{{ route('assets.data') }}",

        columns: [

            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },

            /*{
                data: 'photo',
                name: 'photo',
                orderable: false,
                searchable: false
            },*/

            {
                data: 'asset_code',
                name: 'asset_code'
            },

            {
                data: 'asset_name',
                name: 'asset_name'
            },

            {
                data: 'category_name',
                name: 'category_name'
            },

            {
                data: 'sub_category_name',
                name: 'sub_category_name'
            },

            {
                data: 'vendor_name',
                name: 'vendor_name'
            },

            {
                data: 'responsible_name',
                name: 'responsible_name'
            },
            { 
                data: 'purchase_price', 
                name: 'purchase_price'
            },
            { 
                data: 'purchase_date', 
                name: 'purchase_date'
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
            [3, 'asc']
        ]

    });
});


/*
|--------------------------------------------------------------------------
| Dropdown Action
|--------------------------------------------------------------------------
*/

$(document).on(
    'shown.bs.dropdown',
    '.dropdown',
    function () {

        let menu = $(this).find('.dropdown-menu');

        $('body').append(menu.detach());

        let btn = $(this)
            .find('[data-bs-toggle="dropdown"]');

        let pos = btn.offset();

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


/*
|--------------------------------------------------------------------------
| PRINT QR
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.btn-print-qr',
    function () {

        let id = $(this).data('id');

        let assetName = $(this).data('name');

        let assetCode = $(this).data('code');


        $('#qr_asset_id').val(id);

        $('#qr_asset_name').text(
            assetName || '-'
        );

        $('#qr_asset_code').text(
            assetCode || '-'
        );


        /*
        |--------------------------------------------------------------------------
        | QR Preview
        |--------------------------------------------------------------------------
        */

        $('#qr_preview').html(`

            <div class="text-center">

                <div
                    class="border rounded p-4 mb-2"
                    style="display:inline-block;"
                >

                    <i
                        class="fa-solid fa-qrcode"
                        style="font-size:120px;"
                    ></i>

                </div>

                <div class="small text-muted">
                    QR Preview
                </div>

            </div>

        `);


        let modal = new bootstrap.Modal(
            document.getElementById(
                'modalPrintQr'
            )
        );

        modal.show();

    }
);


/*
|--------------------------------------------------------------------------
| PRINT BUTTON
|--------------------------------------------------------------------------
*/

$('#btnPrintQr').click(function () {

    let id = $('#qr_asset_id').val();

    let size = $('#qr_size').val();

    let paper = $('#qr_paper').val();

    let content = $('#qr_content').val();


    /*
    |--------------------------------------------------------------------------
    | Untuk sementara
    |--------------------------------------------------------------------------
    | Nanti diarahkan ke halaman print QR.
    |--------------------------------------------------------------------------
    */

    let url =
        "{{ url('/cms/assets/print-qr') }}"
        + '/' + id
        + '?size=' + size
        + '&paper=' + paper
        + '&content=' + content;


    window.open(
        url,
        '_blank'
    );

});


/*
|--------------------------------------------------------------------------
| DELETE ASSET
|--------------------------------------------------------------------------
*/

$(document).on(
    'click',
    '.btn-delete',
    function () {

        let id = $(this).data('id');


        let url =
            "{{ route('assets.destroy', ['id' => ':id']) }}";

        url = url.replace(
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
            (result) => {

                if (
                    result.isConfirmed
                ) {

                    $.ajax({

                        url: url,

                        type: 'DELETE',

                        data: {

                            _token:
                                $('meta[name="csrf-token"]')
                                .attr('content')

                        },

                        headers: {

                            'Accept':
                                'application/json'

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
                                        'Asset berhasil dihapus.',

                                    timer:
                                        1500,

                                    showConfirmButton:
                                        false

                                });


                                $(
                                    '#table-assets'
                                )
                                .DataTable()
                                .ajax
                                .reload(
                                    null,
                                    false
                                );

                            },


                        error:
                            function (xhr) {

                                console.log(xhr);


                                Swal.fire({

                                    icon:
                                        'error',

                                    title:
                                        'Gagal',

                                    text:
                                        xhr.responseJSON?.message ||
                                        'Asset gagal dihapus.'

                                });

                            }

                    });

                }

            }
        );

    }
);
/*
|--------------------------------------------------------------------------
| FOCUS
|--------------------------------------------------------------------------
*/
$('#modalPrintQr').on(
    'shown.bs.modal',
    function () {

        $('#qr_size').trigger('focus');

    }
);
</script>
@endsection