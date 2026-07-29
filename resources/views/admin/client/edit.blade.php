@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => 'Khách hàng', 'url' => route('admin.client.index')], ['label' => 'Sửa']]" />

        <div class="card">
            <div class="card-header">
                <h4 class="card-title text-center">
                    Thông tin khách hàng #{{ $client->id }}
                </h4>
            </div>

            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <strong>Không thể cập nhật khách hàng:</strong>

                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $selectedGender = old('gender', $client->gender);

                    $dobValue = old('dob', $client->dob ? \Carbon\Carbon::parse($client->dob)->format('Y-m-d') : '');
                @endphp

                <form action="{{ route('admin.client.update', ['id' => $client->id]) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label for="name" class="form-label">
                                Tên khách hàng
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" id="name" name="name"
                                class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $client->name) }}" required>

                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="phone" class="form-label">
                                Số điện thoại
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" id="phone" name="phone"
                                class="form-control @error('phone') is-invalid @enderror"
                                value="{{ old('phone', $client->phone) }}" required>

                            @error('phone')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="email" class="form-label">
                                Email
                                <span class="text-danger">*</span>
                            </label>

                            <input type="email" id="email" name="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email', $client->email) }}" required>

                            @error('email')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="gender" class="form-label">
                                Giới tính
                            </label>

                            <select id="gender" name="gender" class="form-select @error('gender') is-invalid @enderror">
                                <option value="">-- Chưa chọn --</option>

                                <option value="Male" @selected(old('gender', $client->gender) === 'Male')>
                                    Nam
                                </option>

                                <option value="Female" @selected(old('gender', $client->gender) === 'Female')>
                                    Nữ
                                </option>
                            </select>

                            @error('gender')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="dob" class="form-label">
                                Ngày sinh
                            </label>

                            <input type="date" id="dob" name="dob"
                                class="form-control @error('dob') is-invalid @enderror" value="{{ $dobValue }}">

                            @error('dob')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="address" class="form-label">
                                Địa chỉ
                            </label>

                            <input type="text" id="address" name="address"
                                class="form-control @error('address') is-invalid @enderror"
                                value="{{ old('address', $client->address) }}">

                            @error('address')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="zip_code" class="form-label">
                                Mã bưu điện
                            </label>

                            <input type="text" id="zip_code" name="zip_code"
                                class="form-control @error('zip_code') is-invalid @enderror"
                                value="{{ old('zip_code', $client->zip_code) }}">

                            @error('zip_code')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="col-lg-6 mb-3">
                            <label for="clientgroup_id" class="form-label">
                                Nhóm khách hàng
                            </label>

                            <select id="clientgroup_id" name="clientgroup_id"
                                class="form-select @error('clientgroup_id') is-invalid @enderror">
                                <option value="">-- Chọn nhóm khách hàng --</option>

                                @foreach ($clientgroups as $item)
                                    <option value="{{ $item->id }}" @selected((string) old('clientgroup_id', $client->clientgroup_id) === (string) $item->id)>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('clientgroup_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-center gap-2 mt-3">
                        <a href="{{ route('admin.client.index') }}" class="btn btn-outline-secondary">
                            Quay lại
                        </a>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            Xác nhận
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
