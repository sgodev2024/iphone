@extends('admin.layout.index')

@section('content')
    @php
        $selectedInventoryTracking = old('inventory_tracking', optional($product)->inventory_tracking);
        $trackingLocked = !($canChangeInventoryTracking ?? true);
        $thumbnailPath = normalizePublicImagePath(optional($product)->thumbnail);
        $thumbnailIsRemote = $thumbnailPath && (preg_match('/^(https?:)?\\/\\//i', $thumbnailPath) || str_starts_with($thumbnailPath, 'data:'));
        $thumbnailUrl = $thumbnailPath
            ? ($thumbnailIsRemote
                ? $thumbnailPath
                : ($thumbnailPath === defaultProductImagePath()
                    ? asset($thumbnailPath)
                    : asset('storage/' . ltrim($thumbnailPath, '/'))))
            : asset(defaultProductImagePath());
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

                                <div class="col-md-4">
                                    <label for="price" class="form-label mb-1 fw-bold">Giá nhập</label>
                                    <input type="text" id="price" class="form-control format-price" name="price"
                                        value="{{ formatPrice(optional($product)->price) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="price_buy" class="form-label mb-1 fw-bold">Giá bán</label>
                                    <input type="text" id="price_buy" class="form-control format-price" name="price_buy"
                                        value="{{ formatPrice(optional($product)->price_buy) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="product_unit" class="form-label mb-1 fw-bold">Đơn vị</label>
                                    <input type="text" id="product_unit" class="form-control" name="product_unit"
                                        value="{{ optional($product)->product_unit }}" placeholder="Ví dụ: Chiếc, Bộ, Hộp">
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
                                    <label for="brands_id" class="form-label mb-1 fw-bold">Thương hiệu</label>
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
                            <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">
                                <i class="fa-solid fa-arrow-rotate-left"></i>
                                Quay lại
                            </a>
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
                            <div class="product-thumbnail-uploader">
                                <input type="file" name="thumbnail" id="thumbnail"
                                    class="product-thumbnail-input" accept="image/*">
                                <label class="product-thumbnail-dropzone" for="thumbnail">
                                    <span class="product-thumbnail-preview-frame">
                                        <img id="preview-thumbnail" class="product-thumbnail-preview"
                                            src="{{ $thumbnailUrl }}"
                                            data-fallback-src="{{ asset(defaultProductImagePath()) }}"
                                            alt="{{ optional($product)->name ? 'Ảnh đại diện ' . optional($product)->name : 'Ảnh mặc định sản phẩm' }}">
                                    </span>
                                    <span class="product-thumbnail-copy">
                                        <span class="product-thumbnail-title">Chọn ảnh đại diện</span>
                                        <span class="product-thumbnail-hint">Nhấn để tải ảnh lên</span>
                                    </span>
                                </label>
                            </div>
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

            const thumbnailInput = document.getElementById('thumbnail');
            const thumbnailPreview = document.getElementById('preview-thumbnail');

            thumbnailInput?.addEventListener('change', function(event) {
                const file = event.target.files?.[0];

                if (!file) {
                    return;
                }

                if (!file.type.startsWith('image/')) {
                    event.target.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    thumbnailPreview.src = reader.result;
                });
                reader.readAsDataURL(file);
            });

            thumbnailPreview?.addEventListener('error', function() {
                const fallbackSrc = thumbnailPreview.dataset.fallbackSrc;

                if (fallbackSrc && thumbnailPreview.src !== fallbackSrc) {
                    thumbnailPreview.src = fallbackSrc;
                }
            });

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

        .product-thumbnail-uploader {
            position: relative;
            width: 100%;
            min-height: 180px;
        }

        .product-thumbnail-dropzone {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 180px;
            padding: 16px;
            border: 1px dashed #d9dee8;
            border-radius: 8px;
            background: #f8fafc;
            color: #2a2f5b;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s ease, background-color 0.2s ease;
        }

        .product-thumbnail-dropzone:hover {
            border-color: #3e93ff;
            background: #f0f6ff;
        }

        .product-thumbnail-input {
            position: absolute;
            inset: 0;
            z-index: 2;
            width: 100%;
            height: 100%;
            margin: 0;
            cursor: pointer;
            opacity: 0;
        }

        .product-thumbnail-input:focus-visible + .product-thumbnail-dropzone {
            outline: 3px solid rgba(62, 147, 255, 0.35);
            outline-offset: 2px;
        }

        .product-thumbnail-preview-frame {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 160px;
            height: 160px;
            max-width: 100%;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
            background: #fff;
            pointer-events: none;
        }

        .product-thumbnail-preview {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .product-thumbnail-copy {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-top: 10px;
            pointer-events: none;
        }

        .product-thumbnail-title {
            font-weight: 600;
        }

        .product-thumbnail-hint {
            color: #6b7280;
            font-size: 0.875rem;
        }

        @media (max-width: 767.98px) {
            .product-thumbnail-dropzone,
            .product-thumbnail-uploader {
                width: 100%;
            }

            .product-thumbnail-preview-frame {
                width: min(160px, 70vw);
                height: min(160px, 70vw);
            }
        }
    </style>
@endpush
