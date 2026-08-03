@extends('admin.layout.index')

@section('title','Cập nhật vai trò')

@section('content')

<div class="container">

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">

            <h4 class="mb-0">

                Cập nhật vai trò

            </h4>

           

        </div>

        <div class="card-body">

            <form
                action="{{ route('admin.role.update',$role->id) }}"
                method="POST">

                @csrf

                @method('PUT')

                <div class="mb-3">

                    <label class="form-label">

                        Tên vai trò <span class="text-danger">*</span>

                    </label>

                    <input
                        type="text"
                        name="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name',$role->name) }}">

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
                        class="form-control @error('description') is-invalid @enderror">{{ old('description',$role->description) }}</textarea>

                    @error('description')

                        <div class="invalid-feedback">

                            {{ $message }}

                        </div>

                    @enderror

                </div>

                <div class="text-end">

                    <button
                        class="btn btn-primary">

                        Cập nhật

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