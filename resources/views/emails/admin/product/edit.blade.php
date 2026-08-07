@extends('admin.layout.index')
@section('content')
    <script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap');

        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .icon-bell:before {
            content: "\f0f3";
            font-family: FontAwesome;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: #fff;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(135deg, #6f42c1, #007bff);
            color: white;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
        }

        .breadcrumbs {
            background: #fff;
            padding: 0.75rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .breadcrumbs a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }

        .breadcrumbs i {
            color: #6c757d;
        }

        .form-label {
            font-weight: 500;
        }

        .form-control,
        .form-select {
            border-radius: 5px;
            box-shadow: none;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
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

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <div class="page-inner">
        <div class="page-header">
            <ul class="breadcrumbs mb-3">
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
            </ul>
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
                                                                alt="{{ $item }}">
                                                        </div>
                                                    @endforeach
                                                    <input type="hidden" name="delete_images[]" id="delete_images">
                                                </div>
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

                                        <div class="col-lg-12">
                                            <label for="">Mô tả</label>
                                            <textarea id="description" cols="30" rows="10" name="description">{{ old('description') }}</textarea>
                                            @error('description')
                                                <small class="text-danger">{{ $message }}</small>
                                            @enderror
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

    <script>
        CKEDITOR.replace('description');

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
        });
    </script>


@endsection
