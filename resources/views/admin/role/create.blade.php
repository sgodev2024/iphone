@extends('admin.layout.index')

@section('title', 'Thêm vai trò')

@section('content')

<div class="container">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h4 class="mb-0">Thêm vai trò</h4>

          

        </div>

        <div class="card-body">

            <form action="{{ route('admin.role.store') }}" method="POST">

                @csrf

                <div class="mb-3">

                    <label class="form-label">

                        Tên vai trò <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}"
                        placeholder="Ví dụ: Admin">

                    @error('name')

                        <div class="invalid-feedback">

                            {{ $message }}

                        </div>

                    @enderror

                </div>

                <div class="mb-3">

                    <label class="form-label">

                        Mô tả

                    </label>

                    <textarea
                        name="description"
                        rows="4"
                        class="form-control @error('description') is-invalid @enderror"
                        placeholder="Nhập mô tả...">{{ old('description') }}</textarea>

                    @error('description')

                        <div class="invalid-feedback">

                            {{ $message }}

                        </div>

                    @enderror

                </div>

                <div class="text-end">

                    <button type="submit" class="btn btn-primary">

                        Lưu

                    </button>

                    <a href="{{ route('admin.role.index') }}"
                       class="btn btn-outline-secondary">

                        Hủy

                    </a>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection