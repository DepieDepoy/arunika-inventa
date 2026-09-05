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
            <h5 class="mb-0">SubCategory</h5>
            <small class="text-muted">
                Manage application subcategory
            </small>
        </div>

        <div class="d-flex gap-2">

            <a href="{{ route('subcategory.export') }}"
            class="btn btn-success">
                <i class="fa-solid fa-file-excel"></i>
                Export Excel
            </a>

            <button
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalAddSubcategory">
                <i class="fa-solid fa-plus"></i>
                Add Subcategory
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive subcategory-table-wrapper">
            <table class="table table-hover align-middle"
                   id="table-subcategory"
                   width="100%">
                <thead>
                    <tr>
                        <th width="5%">No</th>
                        <th>Category Name</th>
                        <th>Category Code</th>
                        <th>Sub Category Name</th>
                        <th>Sub Category Code</th>
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
<div class="modal fade" id="modalAddSubcategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formAddSubcategory">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Add Subcategory
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Subcategory Name</label>
                            <input type="text" name="sub_category_name" class="form-control" id="sub_category_name" required>
                        </div>
                        <div class="col-md-6 mb-6">
                            <label>Category</label>
                            <select name="category_id" id="add_category_id" class="form-select" required>
                                <option value="">-- Pilih --</option>
                                @foreach($category as $row)
                                    <option value="{{ $row->id }}">
                                        {{ $row->category_name }}
                                    </option>
                                @endforeach
                            </select>
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
                        Save Subcategory
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!--modal edit-->
<div class="modal fade" id="modalEditSubcategory" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form id="formEditSubcategory">
            @csrf
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Subcategory
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"> </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Subcategory Name</label>
                            <input type="text" name="sub_category_name" id="edit_sub_category_name" class="form-control">
                        </div>
                        <div class="col-md-6 mb-6">
                            <label>Category</label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                @foreach($category as $row)
                                    <option value="{{ $row->id }}">
                                        {{ $row->category_name }}
                                    </option>
                                @endforeach
                            </select>
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
                                placeholder="Enter category description"
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
                        Save Subcategory
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
    $('#table-subcategory').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        autoWidth: false,
        ajax: "{{ route('subcategory.data') }}",
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
                data: 'sub_category_name',
                name: 'sub_category_name'
            },
            {
                data: 'sub_category_code',
                name: 'sub_category_code'
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
$('#formAddSubcategory').submit(function(e){

    if (!this.checkValidity()) {
        this.reportValidity();
        return false;
    }

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('subcategory.store') }}",
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
                    text: 'SubCategory berhasil ditambahkan',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalAddSubcategory')
                );
                if(modal){
                    modal.hide();
                }
                $('#formAddSubcategory')[0].reset();
                $('#table-subcategory').DataTable().ajax.reload();
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
    let url = "{{ route('subcategory.edit', ['id' => ':id']) }}";
    url = url.replace(':id', id);
    $.get(url, function(response){
        console.log(response);
        $('#edit_id').val(response.id);
        $('#edit_sub_category_name').val(response.sub_category_name);
        $('#edit_category_id').val(response.category_id);
        $('#edit_description').val(response.description);
        $('#edit_status').val(response.status);
        
        let modal = new bootstrap.Modal(
            document.getElementById('modalEditSubcategory')
        );
        modal.show();
    });
});
$('#formEditSubcategory').submit(function(e){

    e.preventDefault();

    let formData = new FormData(this);

    $.ajax({
        url: "{{ route('subcategory.update') }}",
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
                    text: 'Subcategory berhasil diupdate',
                    timer: 1500,
                    showConfirmButton: false
                });
                let modal = bootstrap.Modal.getInstance(
                    document.getElementById('modalEditSubcategory')
                );
                if(modal){
                    modal.hide();
                }
                $('#table-subcategory').DataTable().ajax.reload();
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

    let url = "{{ route('subcategory.destroy', ['id' => ':id']) }}";
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

                    $('#table-subcategory').DataTable().ajax.reload();
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
$('#modalAddSubcategory').on('shown.bs.modal', function () {

    $('#name').trigger('focus');

});

$('#modalEditSubcategory').on('shown.bs.modal', function () {

    $('#edit_sub_category_name')
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