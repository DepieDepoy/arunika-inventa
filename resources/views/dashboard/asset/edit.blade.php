@extends('dashboard.layouts.wrapper')

@section('title', 'Assets')

@section('content')

<div class="container-fluid">

    <div class="card">

        <div class="card-header">
            <h4>Edit Asset</h4>
        </div>

        <div class="card-body">

            <form action="{{ route('assets.update', $asset->id) }}"
                  method="POST"
                  enctype="multipart/form-data">

                @csrf
                @method('PUT')

                <div class="row">

                    {{-- Asset Name --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Asset Name
                        </label>

                        <input type="text"
                               name="asset_name"
                               class="form-control"
                               value="{{ old('asset_name', $asset->asset_name) }}"
                               required>
                    </div>


                    {{-- Asset Code --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Asset Code
                        </label>

                        <input type="text"
                               name="asset_code"
                               class="form-control"
                               value="{{ old('asset_code', $asset->asset_code) }}"
                               required>
                    </div>


                    {{-- Category --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Category
                        </label>

                        <select name="category_id"
                                class="form-control">

                            <option value="">
                                -- Select Category --
                            </option>

                            @foreach($categories ?? [] as $category)

                                <option value="{{ $category->id }}"
                                    {{ old('category_id', $asset->category_id) == $category->id ? 'selected' : '' }}>

                                    {{ $category->category_name }}

                                </option>

                            @endforeach

                        </select>
                    </div>


                    {{-- Sub Category --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Sub Category
                        </label>

                        <select name="sub_category_id"
                                class="form-control">

                            <option value="">
                                -- Select Sub Category --
                            </option>

                            @foreach($subCategories ?? [] as $subCategory)

                                <option value="{{ $subCategory->id }}"
                                    {{ old('sub_category_id', $asset->sub_category_id) == $subCategory->id ? 'selected' : '' }}>

                                    {{ $subCategory->sub_category_name }}

                                </option>

                            @endforeach

                        </select>
                    </div>


                    {{-- Vendor --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Vendor
                        </label>

                        <select name="vendor_id"
                                class="form-control">

                            <option value="">
                                -- Select Vendor --
                            </option>

                            @foreach($vendors ?? [] as $vendor)

                                <option value="{{ $vendor->id }}"
                                    {{ old('vendor_id', $asset->vendor_id) == $vendor->id ? 'selected' : '' }}>

                                    {{ $vendor->vendor_name }}

                                </option>

                            @endforeach

                        </select>
                    </div>


                    {{-- Responsible User --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Responsible User
                        </label>

                        <select name="responsible_user_id"
                                class="form-control">

                            <option value="">
                                -- Select User --
                            </option>

                            @foreach($users ?? [] as $user)

                                <option value="{{ $user->id }}"
                                    {{ old('responsible_user_id', $asset->responsible_user_id) == $user->id ? 'selected' : '' }}>

                                    {{ $user->name }}

                                </option>

                            @endforeach

                        </select>
                    </div>


                    {{-- Price --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Price
                        </label>

                        <input type="text"
                               name="price"
                               id="edit_price"
                               class="form-control"
                               value="{{ old('price', $asset->price ? number_format($asset->price, 0, ',', '.') : '') }}"
                               placeholder="0">
                    </div>


                    {{-- Purchase Date --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Purchase Date
                        </label>

                        <input type="date"
                               name="purchase_date"
                               class="form-control"
                               value="{{ old('purchase_date', $asset->purchase_date) }}">
                    </div>


                    {{-- Photo --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Photo
                        </label>

                        @if($asset->photo)

                            <div class="mb-2">

                                <img src="{{ asset('uploads/assets/' . $asset->photo) }}"
                                     width="120"
                                     class="img-thumbnail">

                            </div>

                        @endif

                        <input type="file"
                               name="photo"
                               class="form-control"
                               accept="image/*">

                        <small class="text-muted">
                            Kosongkan jika tidak ingin mengganti foto.
                        </small>

                    </div>

                </div>


                <div class="mt-3">

                    <a href="{{ route('assets.index') }}"
                       class="btn btn-secondary">

                        Back

                    </a>

                    <button type="submit"
                            class="btn btn-primary">

                        Update Asset

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection