@extends('admin.layout.index')
@section('content')
    <style>
        .form-label {
            font-weight: 500;
        }

        .form-control,
        .form-select {
            border-radius: 5px;
            box-shadow: none;
        }

        .add_product>div {
            margin-top: 20px;
        }

        .modal-footer {
            justify-content: center;
            border-top: none;
        }

        textarea.form-control {
            height: auto;
        }

        #description {
            border-radius: 5px;
        }
    </style>
    <div class="page-inner">
        <div class="page-header">
            <x-breadcrumb :items="[['label' => 'Sản phẩm'], 'url' => route('admin.product.store'), ['label' => 'thêm']]" />
            {{-- <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="{{ route('admin.dashboard') }}">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.product.store') }}">Sản phẩm</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Thêm</a>
                </li>
            </ul> --}}
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title" style="text-align: center; color:white">Thêm sản phẩm</h4>
                    </div>
                    <div class="card-body">
                        <div class="">
                            <div id="basic-datatables_wrapper" class="dataTables_wrapper container-fluid dt-bootstrap4">
                                <form action="{{ route('admin.product.update', ['id' => $products->id]) }}" method="POST"
                                    enctype="multipart/form-data" id="addproduct">
                                    @csrf
                                    <div class="row">
                                        <div class="col-lg-6 add_product">
                                            <div>
                                                <label for="placeholderInput" class="form-label">Tên sản phẩm</label>
                                                <input type="text"
                                                    class="form-control @error('name') is-invalid @enderror" name="name"
                                                    id="name" value="{{ old('name', $products->name) }}">
                                                @error('name')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="placeholderInput" class="form-label">Thương hiệu</label>
                                                <select class="form-control @error('brand_id') is-invalid @enderror"
                                                    name="brand_id" id="brand_id">
                                                    <option value="">Chọn danh mục</option>
                                                    @foreach ($brand as $item)
                                                        <option value="{{ $item->id }}"
                                                            {{ $products->brands_id . '=' . $item->id }}
                                                            @selected(old('brand_id', $products->brands_id) == $item->id)>{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('brand_id')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="example-text-input" class="form-label">Loại Danh Mục<span
                                                        class="text text-danger">*</span></label>
                                                <select class="form-control @error('category_id') is-invalid @enderror"
                                                    name="category_id" id="category_id">
                                                    <option value="">Chọn danh mục</option>
                                                    @foreach ($category as $item)
                                                        <option value="{{ $item->id }}" @selected(old('category_id', $products->category_id) == $item->id)>
                                                            {{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                                @error('category_id')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="example-search-input" class="form-label">Đơn vị<span
                                                        class="text text-danger">*</span></label>
                                                <input min='1'
                                                    class="form-control @error('product_unit') is-invalid @enderror"
                                                    name="product_unit" type="text" id="product_unit"
                                                    value="{{ old('product_unit', $products->product_unit) }}">
                                                @error('product_unit')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-lg-6 add_product">
                                            <div>
                                                <label for="example-search-input" class="form-label">Giá nhập<span
                                                        class="text text-danger">*</span></label>
                                                <input min='1'
                                                    class="form-control @error('price') is-invalid @enderror" name="price"
                                                    type="number" id="price"
                                                    value="{{ old('price', $products->price) }}">
                                                @error('price')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="example-search-input" class="form-label">Giá bán<span
                                                        class="text text-danger">*</span></label>
                                                <input min='1'
                                                    class="form-control @error('price_buy') is-invalid @enderror"
                                                    name="price_buy" type="number" id="price_buy"
                                                    value="{{ old('price_buy', $products->price_buy) }}">
                                                @error('price_buy')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                            <div>
                                                <label for="example-text-input" class="form-label">Ảnh sản phẩm<span
                                                        class="text text-danger">*</span></label>
                                                <input id="images" class="form-control" type="file" name="images[]"
                                                    multiple accept="image/*">
                                                @error('images')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror

                                                <div class="row">
                                                    @foreach ($products->images as $item)
                                                        <div class="col-lg-3 my-3 position-relative image-wrapper"
                                                            data-id="{{ $item->id }}">
                                                            <button type="button"
                                                                class=" position-absolute top-0  delete-image bg-danger border text-white rounded"
                                                                style="right: 15px">X</button>
                                                            <img class="img-fluid w-100 rounded"
                                                                src="{{ showImage($item->image_path) }}"
                                                                alt="{{ $item->image_path }}">
                                                        </div>
                                                    @endforeach
                                                    <input type="hidden" name="delete_images[]" id="delete_images">
                                                </div>
                                            </div>

                                        </div>

                                        <div class="col-lg-12 add_product">
                                            <div>
                                                <label for="">Mô tả</label>
                                                <textarea id="description" cols="30" rows="10" name="description">{{ old('description', $products->description) }}</textarea>
                                                @error('description')
                                                    <small class="text-danger">{{ $message }}</small>
                                                @enderror
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer m-2">
                                        <button type="submit" class="btn btn-primary w-md">
                                            Xác nhận
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script>
        $(document).ready(function() {
            $('.delete-image').on('click', function() {
                const imageWrapper = $(this).closest('.image-wrapper');
                const imageId = imageWrapper.data('id');

                if (imageId) { // Kiểm tra nếu có ID hợp lệ
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'delete_images[]',
                        value: imageId
                    }).appendTo('#addproduct');

                    imageWrapper.remove();
                }
            });
            CKEDITOR.replace('description');
        });
    </script>
@endsection
