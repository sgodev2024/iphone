@extends('admin.layout.index')

@section('content')
    @php
        $selectedInventoryTracking = old('inventory_tracking', optional($product)->inventory_tracking);
        $trackingLocked = !($canChangeInventoryTracking ?? true);
    @endphp
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Sản phẩm', 'url' => route('admin.products.index')], ['label' => $title]]" />


        <form id="myForm">

            @if (!empty($product))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="row gy-4">

                                <div class="col-md-12">
                                    <label for="name" class="form-label mb-1 fw-bold">
                                        Tên sản phẩm <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ optional($product)->name }}" placeholder="Nhập tên sản phẩm">
                                </div>
                                <div class="col-md-12">
                                    <label for="inventory_tracking" class="form-label mb-1 fw-bold">
                                        Loại sản phẩm <span class="text-danger">*</span>
                                    </label>

                                    @if ($trackingLocked)
                                        {{-- Select bị disabled sẽ không gửi dữ liệu nên cần input hidden --}}
                                        <input type="hidden" name="inventory_tracking"
                                            value="{{ optional($product)->inventory_tracking }}">
                                    @endif

                                    <select name="inventory_tracking" id="inventory_tracking"
                                        class="form-select form-control" @disabled($trackingLocked)>
                                        <option value="">-- Chọn loại sản phẩm --</option>

                                        <option value="{{ \App\Models\Product::INVENTORY_TRACKING_IMEI }}"
                                            @selected($selectedInventoryTracking === \App\Models\Product::INVENTORY_TRACKING_IMEI)>
                                            Quản lý theo IMEI
                                        </option>

                                        <option value="{{ \App\Models\Product::INVENTORY_TRACKING_QUANTITY }}"
                                            @selected($selectedInventoryTracking === \App\Models\Product::INVENTORY_TRACKING_QUANTITY)>
                                            Sản phẩm thường
                                        </option>
                                    </select>

                                    @if ($trackingLocked && !empty($inventoryTrackingLockedMessage))
                                        <small class="text-muted d-block mt-2">
                                            {{ $inventoryTrackingLockedMessage }}
                                        </small>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label for="price" class="form-label mb-1 fw-bold">Giá nhập</label>
                                    <input type="text" class="form-control format-price" name="price"
                                        value="{{ formatPrice(optional($product)->price) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="price_buy" class="form-label mb-1 fw-bold">Giá bán</label>
                                    <input type="text" class="form-control format-price" name="price_buy"
                                        value="{{ formatPrice(optional($product)->price_buy) }}">
                                </div>

                                <div class="col-md-12">
                                    <label for="product_unit" class="form-label mb-1 fw-bold">Đơn vị</label>
                                    <input type="text" class="form-control" name="product_unit"
                                        value="{{ optional($product)->product_unit }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="category_id" class="form-label mb-1 fw-bold">Danh mục <span
                                            class="text-danger">*</span></label>
                                    <select name="category_id" id="category_id" class="form-control form-select">
                                        <option value="">-- Chọn danh mục --</option>
                                        @foreach ($categories as $categoryId => $categoryName)
                                            <option value="{{ $categoryId }}" @selected($categoryId == optional($product)->category_id)>
                                                {{ $categoryName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="brands_id" class="form-label mb-1 fw-bold">Thương hiệu <span
                                            class="text-danger">*</span></label>
                                    <select name="brands_id" id="brands_id" class="form-control form-select">
                                        <option value="">-- Chọn thương hiệu --</option>
                                        @foreach ($brands as $brandId => $brandName)
                                            <option value="{{ $brandId }}" @selected($brandId == optional($product)->brands_id)>
                                                {{ $brandName }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-12">
                                    <label for="description" class="form-label mb-1 fw-bold">Mô tả</label>
                                    <textarea class="form-control" name="description" rows="4">{{ optional($product)->description }}</textarea>
                                </div>

                                <div class="col-md-12">

                                    <label class="switch" data-id="">
                                        <input name="is_featured" type="checkbox" value="1"
                                            @checked(optional($product)->is_featured)>
                                        <span class="slider round"></span>
                                    </label>
                                    <label for="is_featured" class="">Sản phẩm nổi bật</label>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Xuất bản</h5>
                        </div>
                        <div class="card-body">
                            <button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Lưu</button>
                            <a href="/admin/brand" class="btn btn-outline-secondary"><i
                                    class="fa-solid fa-arrow-rotate-left"></i> Quay lại</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Trạng thái</h5>
                        </div>
                        <div class="card-body">
                            <select name="status" class="form-select form-control">
                                <option value="published" @selected((optional($product)->status ?? 'published') === 'published')>Kích hoạt</option>
                                <option value="inactive" @selected(optional($product)->status === 'inactive')>Không kích hoạt</option>
                            </select>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Ảnh đại diện</h5>
                        </div>
                        <div class="card-body">
                            <img class="img-fluid img-thumbnail w-100" id="preview-thumbnail" style="cursor: pointer"
                                src="{{ showImage(optional($product)->thumbnail) }}" alt=""
                                onclick="document.getElementById('thumbnail').click();">
                            <input type="file" name="thumbnail" id="thumbnail" class="form-control d-none"
                                accept="image/*" onchange="previewImage(event, 'preview-thumbnail')">
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>
@endsection


@push('script')
    <script>
        $(function() {

            $(document).on("input", ".format-price", function() {
                let value = $(this).val();

                // Chỉ giữ số
                value = value.replace(/\D/g, "");

                // Định dạng VNĐ
                if (value) {
                    value = new Intl.NumberFormat("vi-VN").format(value);
                }

                $(this).val(value);
            });

            const url = '/admin/products' + '{{ !empty($product) ? "/{$product->id}" : '' }}'

            handleSubmit('#myForm', function(res) {
                window.location.href = '/admin/products';
            }, url)

        })
    </script>
@endpush

@push('style')
    <style>
        .switch {
            position: relative;
            display: inline-block;
            width: 46px;
            height: 24px;
        }

        /* Ẩn input mặc định */
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        /* Thanh trượt */
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        /* Nút tròn */
        .slider::before {
            position: absolute;
            content: "";
            height: 19px;
            width: 19px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        /* Khi input được check */
        .switch input:checked+.slider {
            background-color: #f43f5e;
        }

        .switch input:focus+.slider {
            box-shadow: 0 0 1px #f43f5e;
        }

        .switch input:checked+.slider::before {
            transform: translateX(22px);
        }

        /* Bo tròn toàn bộ nếu dùng class 'round' */
        .slider.round {
            border-radius: 34px;
        }

        .slider.round::before {
            border-radius: 50%;
        }
    </style>
@endpush
