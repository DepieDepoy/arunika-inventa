@extends('dashboard.layouts.wrapper')

@section('title', 'Dashboard')

@section('content')
<style>
.swal2-container {
    z-index: 99999 !important;
 
}
   .table-responsive{
    overflow: visible !important;
}

.dropdown-menu{
    z-index:99999 !important;
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
            <h5 class="mb-0">Role User</h5>
            <small class="text-muted">
                Manage application role
            </small>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('roles.export') }}"
            class="btn btn-success">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>

            <button
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalAddRole">
                <i class="fa-solid fa-plus"></i>
                Add Role
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle"
                   id="table-users"
                   width="100%">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Role Name</th>
                        <th>Role Code</th>
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
<div class="modal fade" id="modalAddRole" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formAddRole">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Role Name</label>
                            <input type="text" name="role_name" class="form-control" id="role_name" required>
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
                        Save Role
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--modal edit-->
<div class="modal fade" id="modalEditRole" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEditRole">
            @csrf
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Role
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Role Name</label>
                            <input type="text" name="role_name" id="edit_role_name" class="form-control" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Status</label>
                            <select name="status" id="edit_role_status" class="form-select">
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
                        Save Role
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
    $('#table-users').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('roles.data') }}",
        columns: [
            {
                data: 'DT_RowIndex',
                name: 'DT_RowIndex',
                orderable: false,
                searchable: false
            },
            {
                data: 'role_name',
                name: 'role_name'
            },
            {
                data: 'role_code',
                name: 'role_code'
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

$(document).on('shown.bs.dropdown', '.dropdown', function () {

    let menu = $(this).find('.dropdown-menu');

    $('body').append(menu.detach());

    let btn = $(this).find('[data-bs-toggle="dropdown"]');

    let pos = btn.offset();

    menu.css({
        position: 'absolute',
        top: pos.top + btn.outerHeight(),
        left: pos.left - menu.outerWidth() + btn.outerWidth(),
        display: 'block',
        zIndex: 999999
    });
});
//submit
$('#formAddRole').submit(function(e){

    if (!this.checkValidity()) {
        this.reportValidity();
        return false;
    }

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('roles.store') }}",
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
                    text: 'Role berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalAddRole')
                );
                if(modal){
                    modal.hide();
                }
                $('#formAddRole')[0].reset();
                $('#table-users').DataTable().ajax.reload();
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
    let url = "{{ route('roles.edit', ['id' => ':id']) }}";
    url = url.replace(':id', id);
    $.get(url, function(response){
        console.log(response);
        $('#edit_id').val(response.id);
        $('#edit_role_name').val(response.role_name);
        $('#edit_role_status').val(response.status);
        
        let modal = new bootstrap.Modal(
            document.getElementById('modalEditRole')
        );
        modal.show();
    });
});
$('#formEditRole').submit(function(e){

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('roles.update') }}",
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
                    document.getElementById('modalEditRole')
                );
                if(modal){
                    modal.hide();
                }
                $('#table-users').DataTable().ajax.reload();
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

    let url = "{{ route('roles.destroy', ['id' => ':id']) }}";
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

                    $('#table-users').DataTable().ajax.reload();
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
$('#modalAddRole').on('shown.bs.modal', function () {

    $('#role_name').trigger('focus');

});

$('#modalEditRole').on('shown.bs.modal', function () {

    $('#edit_role_name')
        .trigger('focus')
        .select();

});
</script>

@endsection