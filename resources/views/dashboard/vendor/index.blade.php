@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')
<style>
.swal2-container {
    z-index: 99999 !important;
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
            <h5 class="mb-0">Vendor</h5>
            <small class="text-muted">
                Manage application vendor
            </small>
        </div>

        <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('vendor.export'))
            <a href="{{ route('vendor.export') }}"
            class="btn btn-success">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>
            @endif
            @if(auth()->user()->hasPermission('vendor.create'))
            <button
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalAddVendor">
                <i class="fa-solid fa-plus"></i>
                Add Vendor
            </button>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive vendor-table-wrapper">
            <table class="table table-hover align-middle"
                   id="table-vendor"
                   width="100%">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Vendor Name</th>
                        <th>Vendor Code</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>PIC</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th width="10%">Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
    </div>
</div>
</div>
<!--modal -->
<div class="modal fade" id="modalAddVendor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formAddVendor">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Vendor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Vendor Name</label>
                            <input type="text" name="vendor_name" class="form-control" id="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Address</label>
                             <textarea
                                name="address"
                                id="address"
                                class="form-control"
                                rows="3" required
                                placeholder="Enter address"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="number" name="phone" id="phone" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>PIC</label>
                            <input type="text" name="pic_name" id="pic" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="text" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Description</label>
                             <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                rows="3" 
                                placeholder="Enter description"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="1"> Active </option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Vendor
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--modal edit-->
<div class="modal fade" id="modalEditVendor" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEditVendor">
            @csrf
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Vendor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Vendor Name</label>
                            <input type="text" name="vendor_name" id="edit_vendor_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Address</label>
                            <textarea
                                name="address"
                                id="edit_address"
                                class="form-control"
                                rows="3" required
                                placeholder="Enter address"
                            ></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Phone</label>
                            <input type="number" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6 mb-6">
                            <label>PIC</label>
                            <input type="text" name="pic_name" id="edit_pic_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Description</label>
                            <textarea
                                name="description"
                                id="edit_description"
                                class="form-control"
                                rows="3" 
                                placeholder="Enter description"
                            ></textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="1"> Active </option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save Vendor
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@section('js')

<script>

$(function() {
    $('#table-vendor').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('vendor.data') }}",
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'vendor_name',
                name: 'vendor_name'
            },
            {
                data: 'vendor_code',
                name: 'vendor_code'
            },
            {
                data: 'address',
                name: 'address'
            },
            {
                data: 'phone',
                name: 'phone',
                className: 'text-start'
            },
            {
                data: 'pic',
                name: 'pic'
            },
            {
                data: 'email',
                name: 'email'
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
});

//submit
$('#formAddVendor').submit(function(e){

    if (!this.checkValidity()) {
        this.reportValidity();
        return false;
    }

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('vendor.store') }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'Accept': 'application/json'
        },
        success: function(response){
            if(response.success){
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'User berhasil diupdate',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalAddVendor')
                );
                if(modal){
                    modal.hide();
                }
                $('#formAddVendor')[0].reset();
                $('#table-vendor').DataTable().ajax.reload();
            }
        },
        error: function(xhr){
            if(xhr.status == 422){
                let errors = xhr.responseJSON.errors;
                let message = '';

                $.each(errors, function(key, value){
                    message += value[0] + '<br>';
                });
                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    html: message,
                    allowOutsideClick: false,
                    backdrop: true
                });
            }
        }
    });
});

//edit
$(document).on('click', '.btn-edit', function() {
    let id = $(this).data('id');
    let url = "{{ route('vendor.edit', ['id' => ':id']) }}";
    url = url.replace(':id', id);
    $.get(url, function(response){
        console.log(response);
        $('#edit_id').val(response.id);
        $('#edit_vendor_name').val(response.vendor_name);
        $('#edit_address').val(response.address);
        $('#edit_phone').val(response.phone);
        $('#edit_pic_name').val(response.pic_name);
        $('#edit_email').val(response.email);
        $('#edit_description').val(response.description);
        $('#edit_status').val(response.status);
        
        let modal = new bootstrap.Modal(
            document.getElementById('modalEditVendor')
        );
        modal.show();
    });
});
$('#formEditVendor').submit(function(e){

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('vendor.update') }}",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'Accept': 'application/json'
        },
        success: function(response){
            if(response.success){
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: 'Vendor berhasil diupdate',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalEditVendor')
                );
                if(modal){
                    modal.hide();
                }
                $('#table-vendor').DataTable().ajax.reload();
            }
        },

        error: function(xhr){

            if(xhr.status == 422){

                let errors = xhr.responseJSON.errors;
                let message = '';

                $.each(errors, function(key, value){
                    message += value[0] + '<br>';
                });

                Swal.fire({
                    icon: 'error',
                    title: 'Validasi Gagal',
                    html: message
                });
            }
        }
    });
});

//delete
$(document).on('click', '.btn-delete', function () {

    let id = $(this).data('id');

    let url = "{{ route('vendor.destroy', ['id' => ':id']) }}";
    url = url.replace(':id', id);

    Swal.fire({
        title: 'Hapus Data?',
        text: 'Data yang sudah dihapus tidak bisa dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {

        if (result.isConfirmed) {

            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {

                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message
                    });

                    $('#table-vendor').DataTable().ajax.reload();
                },
                error: function(xhr) {

                    console.log(xhr);

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: xhr.responseJSON?.message || 'Data gagal dihapus'
                    });

                }
            });

        }

    });

});
//focus
$('#modalAddVendor').on('shown.bs.modal', function () {

    $('#name').trigger('focus');

});

$('#modalEditVendor').on('shown.bs.modal', function () {

    $('#edit_name')
        .trigger('focus')
        .select();

});
function copyPassword(){

    let input = document.getElementById('tempPassword');

    navigator.clipboard.writeText(input.value);

    Swal.fire({
        icon: 'success',
        title: 'Password berhasil disalin',
        timer: 1000,
        showConfirmButton: false
    });

}
</script>

@endsection