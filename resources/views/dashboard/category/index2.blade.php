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
            <h5 class="mb-0">Asset Category</h5>
            <small class="text-muted">
                Manage application Category
            </small>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('category.export') }}"
            class="btn btn-success">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>

            <button
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalAddCategory">
                <i class="fa-solid fa-plus"></i>
                Add Category
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive category-table-wrapper">
            <table class="table table-hover align-middle"
                   id="table-category"
                   width="100%">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Category Name</th>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Total Asset</th>
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
<div class="modal fade" id="modalAddCategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formAddCategory">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" name="category_name" class="form-control" id="category_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Description</label>
                            <textarea
                                name="description"
                                id="description"
                                class="form-control"
                                rows="3"
                                placeholder="Enter category description">
                            </textarea>
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
                        Save Category
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--modal edit-->
<div class="modal fade" id="modalEditCategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEditCategory">
            @csrf
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Name</label>
                            <input type="text" name="category_name" id="edit_category_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Description</label>
                            <textarea
                                name="description"
                                id="edit_description"
                                class="form-control"
                                rows="3"
                                placeholder="Enter category description">
                            </textarea>
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
                        Save Category
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
    $('#table-category').DataTable({
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
                className: 'text-center',
                defaultContent: '0'
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
let isSubmitting = false;

$('#formAddCategory').submit(function(e){

    if (!this.checkValidity()) {
        this.reportValidity();
        return false;
    }

    e.preventDefault();
    // Cegah double submit
    if(isSubmitting){
        return;
    }
    isSubmitting = true;

    let form = this;
    let formData = new FormData(form);
    let btnSave = $('#btnSaveCategory');

    // Disable tombol
    btnSave.prop('disabled', true);
    btnSave.html(
        '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...'
    );

    $.ajax({
        url: "{{ route('category.store') }}",
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
                    text: 'Category berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalAddCategory')
                );
                if(modal){
                    modal.hide();
                }
                $('#formAddCategory')[0].reset();
                $('#table-category').DataTable().ajax.reload();
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
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: xhr.responseJSON?.message
                        ?? 'Terjadi kesalahan saat menyimpan Category.',
                    allowOutsideClick: false,
                    backdrop: true
                });
            }
        },
        complete: function(){

            isSubmitting = false;

            btnSave.prop('disabled', false);

            btnSave.html('Save');
        }

    });
});

//edit
$(document).on('click', '.btn-edit', function() {
    let id = $(this).data('id');
    let url = "{{ route('category.edit', ['id' => ':id']) }}";
    url = url.replace(':id', id);
    $.get(url, function(response){
        console.log(response);
        $('#edit_id').val(response.id);
        $('#edit_category_name').val(response.category_name);
        $('#edit_description').val(response.description);
        $('#edit_status').val(response.status);
        
        let modal = new bootstrap.Modal(
            document.getElementById('modalEditCategory')
        );
        modal.show();
    });
});
$('#formEditCategory').submit(function(e){

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('category.update') }}",
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
                    text: 'Category berhasil diupdate',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalEditCategory')
                );
                if(modal){
                    modal.hide();
                }
                $('#table-category').DataTable().ajax.reload();
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

    let url = "{{ route('category.destroy', ['id' => ':id']) }}";
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

                    $('#table-category').DataTable().ajax.reload();
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
$('#modalAddCategory').on('shown.bs.modal', function () {

    $('#category_name').trigger('focus');

});

$('#modalEditCategory').on('shown.bs.modal', function () {

    $('#edit_category_name')
        .trigger('focus')
        .select();

});
</script>

@endsection