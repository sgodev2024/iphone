@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Khách hàng', 'url' => route('admin.client.index')], ['label' => 'Thêm']]" />

        <div class="card">
            <div class="card-header">
                <h4 class="card-title text-center">Thêm khách hàng</h4>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Không thể lưu khách hàng:</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('admin.client.store') }}" method="POST">
                    @csrf

                    <div class="row">
                        @if (auth()->user()->isAdministrator())
                            <div class="col-lg-6 mb-3">
                                <label for="branch_id" class="form-label">Cửa hàng <span class="text-danger">*</span></label>
                                <select id="branch_id" name="branch_id"
                                    class="form-select @error('branch_id') is-invalid @enderror" required>
                                    <option value="">-- Chọn cửa hàng --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('branch_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="col-lg-6 mb-3">
                            <label for="name" class="form-label">Họ tên <span class="text-danger">*</span></label>
                            <input type="text" id="name" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="phone" class="form-label">Số điện thoại <span class="text-danger">*</span></label>
                            <input type="text" id="phone" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone') }}" required>
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="address" class="form-label">Địa chỉ</label>
                            <input type="text" id="address" name="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address') }}">
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="{{ route('admin.client.index') }}" class="btn btn-outline-secondary">Quay lại</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Thêm khách hàng
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
