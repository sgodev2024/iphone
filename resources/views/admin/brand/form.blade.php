@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Thương hiệu', 'url' => route('admin.brand.index')], ['label' => $title]]" />

        <form id="myForm">

            @if (!empty($brand))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="row gy-4">
                                @if ($hasBranchBrands && auth()->user()->isAdministrator())
                                    <div class="col-md-12">
                                        <label for="branch_id" class="form-label mb-1 fw-bold">
                                            Cửa hàng <span class="text-danger">*</span>
                                        </label>
                                        <select name="branch_id" id="branch_id" class="form-select form-control"
                                            @disabled(!empty($brand))>
                                            <option value="">-- Chọn cửa hàng --</option>
                                            @foreach ($branches as $branchOption)
                                                <option value="{{ $branchOption->id }}" @selected((int) old('branch_id', optional($brand)->branch_id) === (int) $branchOption->id)>
                                                    {{ $branchOption->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (!empty($brand))
                                            <small class="text-muted">Cửa hàng của thương hiệu không thể thay đổi sau khi tạo.</small>
                                        @endif
                                    </div>
                                @endif
                                <div class="col-md-12">
                                    <label for="name" class="form-label mb-1 fw-bold">Tên thương hiệu</label>
                                    <input type="text" class="form-control" name="name"
                                        value="{{ optional($brand)->name }}" placeholder="Nhập tên thương hiệu">
                                </div>
                                <div class="col-md-12">
                                    <label for="description" class="form-label mb-1 fw-bold">Mô tả</label>
                                    <textarea name="description" placeholder="Nhập mô tả" class="form-control">{{ optional($brand)->description }}</textarea>
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
                                <option value="1" @selected(optional($brand)->status == 1)>Kích hoạt</option>
                                <option value="0" @selected(optional($brand)->status == 0)>Không kích hoạt</option>
                            </select>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Logo</h5>
                        </div>
                        <div class="card-body">
                            <img class="img-fluid img-thumbnail w-100" id="preview-logo" style="cursor: pointer"
                                src="{{ showImage(optional($brand)->logo) }}" alt=""
                                onclick="document.getElementById('logo').click();">
                            <input type="file" name="logo" id="logo" class="form-control d-none" accept="image/*"
                                onchange="previewImage(event, 'preview-logo')">
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

            const url = @json(empty($brand) ? route('admin.brand.store') : route('admin.brand.update', $brand->id))

            handleSubmit('#myForm', function(res) {
                window.location.href = '/admin/brand';
            }, url)

        })
    </script>
@endpush
