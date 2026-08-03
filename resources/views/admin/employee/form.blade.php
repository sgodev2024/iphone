@extends('admin.layout.index')

@section('content')
    @php
        $isAdminAccount = ($accountType ?? null) === 'admin' || (int) optional($user)->role_id === 1;
        $requiresStorage = $requiresStorage ?? false;
        $storages = $storages ?? collect();
        $accountLabel = $isAdminAccount ? 'Admin' : ($requiresStorage ? 'nhân viên' : 'chi nhánh');
        $backUrl = $isAdminAccount || $requiresStorage ? route('admin.employees.index') : route('admin.users.index');
        $selectedStatus = old('status', optional($user)->status ?? 'active');
        $selectedStorageId = old('storage_id', optional($user)->storage_id);
    @endphp

    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Tài khoản', 'url' => $backUrl], ['label' => $title]]" />


        <form id="myForm" action="{{ $api }}" method="POST" enctype="multipart/form-data" novalidate>
            @csrf

            @if (!empty($user))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-md-9">
                    <div class="card">
                        <div class="card-body">
                            <div class="row gy-4">
                                <div class="col-md-6">
                                    <label for="name" class="form-label mb-1 fw-bold">Tên tài khoản
                                        {{ $accountLabel }}</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        name="name" value="{{ old('name', optional($user)->name) }}"
                                        placeholder="Nhập tên tài khoản {{ $accountLabel }}">
                                    @error('name')
                                        <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="email" class="form-label mb-1 fw-bold">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        name="email" value="{{ old('email', optional($user)->email) }}"
                                        placeholder="Nhập email">
                                    @error('email')
                                        <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6 position-relative">
                                    <label for="password" class="form-label mb-1 fw-bold">Mật khẩu</label>
                                    <input type="password" class="form-control @error('password') is-invalid @enderror"
                                        id="password" name="password" placeholder="Nhập mật khẩu" value="">
                                    <i class="fa-regular fa-eye position-absolute toggle-password"
                                        style="top: 38px; right: 25px; cursor: pointer;"></i>
                                    @error('password')
                                        <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                    @enderror
                                </div>


                                <div class="col-md-6">
                                    <label for="phone" class="form-label mb-1 fw-bold">Số điện thoại</label>
                                    <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                                        name="phone" value="{{ old('phone', optional($user)->phone) }}"
                                        placeholder="Nhập số điện thoại">
                                    @error('phone')
                                        <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="col-md-6">
                                    <label for="img_url" class="form-label mb-1 fw-bold">Ảnh đại diện</label>
                                    <input type="file" class="form-control @error('img_url') is-invalid @enderror"
                                        name="img_url" accept="image/jpeg,image/png,image/jpg,image/gif">
                                    @error('img_url')
                                        <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                    @enderror
                                </div>

                                @if ($requiresStorage)
                                    <div class="col-md-6">
                                        <label for="storage_id" class="form-label mb-1 fw-bold">Kho bán hàng</label>
                                        <select name="storage_id"
                                            class="form-select form-control @error('storage_id') is-invalid @enderror"
                                            @disabled($storages->isEmpty())>
                                            <option value="">
                                                {{ $storages->isEmpty() ? 'Chưa có kho bán hàng' : 'Chọn kho bán hàng' }}
                                            </option>
                                            @foreach ($storages as $storage)
                                                <option value="{{ $storage->id }}" @selected((string) $selectedStorageId === (string) $storage->id)>
                                                    {{ $storage->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('storage_id')
                                            <span
                                                class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif

                                @if ($isAdminAccount)
                                    <div class="col-md-6">
                                        <label class="form-label mb-1 fw-bold">Nơi làm việc</label>
                                        <div class="form-control bg-light">{{ $adminWorkplaceLabel ?? 'Toàn hệ thống' }}</div>
                                    </div>
                                @else
                                    <div class="col-md-12">
                                        <label for="address" class="form-label mb-1 fw-bold">Địa chỉ</label>
                                        <textarea name="address" placeholder="Nhập địa chỉ" class="form-control @error('address') is-invalid @enderror">{{ old('address', optional($user)->address) }}</textarea>
                                        @error('address')
                                            <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                        @enderror
                                    </div>
                                @endif
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
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i>
                                Lưu</button>
                            <a href="{{ $backUrl }}" class="btn btn-outline-secondary"><i
                                    class="fa-solid fa-arrow-rotate-left"></i> Quay lại</a>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">Trạng thái</h5>
                        </div>
                        <div class="card-body">
                            @if ($isAdminAccount)
                                <span class="badge bg-success">Kích hoạt</span>
                            @else
                                <select name="status"
                                    class="form-select form-control @error('status') is-invalid @enderror">
                                    <option value="active" @selected($selectedStatus === 'active')>Kích hoạt</option>
                                    <option value="inactive" @selected($selectedStatus === 'inactive')>Không kích hoạt</option>
                                    <option value="locked" @selected($selectedStatus === 'locked')>Khóa tài khoản</option>
                                </select>
                                @error('status')
                                    <span class="invalid-feedback d-block server-validation-error">{{ $message }}</span>
                                @enderror
                            @endif
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

            const url = '{{ $api }}'

            handleSubmit('#myForm', function(res) {
                window.location.href = res.data.redirect
            }, url)

            $(document).on('click', '.toggle-password', function() {
                let input = $('#password');
                let type = input.attr('type') === 'password' ? 'text' : 'password';
                input.attr('type', type);

                // đổi icon
                $(this).toggleClass('fa-eye fa-eye-slash');
            });

        })
    </script>
@endpush
