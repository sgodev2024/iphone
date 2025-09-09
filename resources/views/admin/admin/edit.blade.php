@extends('admin.layout.index')

@section('content')
    <div class="page-inner">
        <x-breadcrumb :items="[['label' => $title]]" />

        <form id="myForm">
            <div class="row mt-4">
                {{-- Cột trái --}}
                <div class="col-md-3">
                    <div class="card text-center p-4">
                        <div class="position-relative d-inline-block mx-auto">
                            {{-- Avatar hình tròn --}}
                            <img src="{{ showImage($user->img_url) }}" class="rounded-circle img-thumbnail"
                                style="width: 150px; height: 150px; object-fit: cover;" id="preview-avatar">

                            {{-- Nút upload ảnh --}}
                            <label for="avatar"
                                class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2"
                                style="cursor: pointer;">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="avatar" name="img_url" class="d-none" accept="image/*">
                        </div>

                        <div class="mt-3">
                            <h5 id="profile-name">{{ $user->name }}</h5>
                            <p class="mb-1 text-muted" id="profile-email">{{ $user->email }}</p>
                            <p class="mb-1" id="profile-phone">{{ $user->phone ?? 'Chưa có số điện thoại' }}</p>
                            <p id="profile-address">{{ $user->address ?? 'Chưa có địa chỉ' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Cột phải --}}
                <div class="col-md-9">
                    <div class="card p-4">

                        <div class="row gy-4">
                            <div class="col-md-12">
                                <label class="form-label mb-1 fw-bold">Tên</label>
                                <input type="text" name="name" value="{{ $user->name }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-bold">Email</label>
                                <input type="email" name="email" value="{{ $user->email }}" class="form-control">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label mb-1 fw-bold">Số điện thoại</label>
                                <input type="text" name="phone" value="{{ $user->phone }}" class="form-control">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label mb-1 fw-bold">Địa chỉ</label>
                                <textarea name="address" class="form-control ">{{ $user->address }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary">Cập nhật</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('script')
    <script>
        document.getElementById('avatar').addEventListener('change', function(e) {
            const [file] = e.target.files;
            if (file) {
                document.getElementById('preview-avatar').src = URL.createObjectURL(file);
            }
        });

        $(function() {
            handleSubmit('#myForm', function(res) {
                window.location.reload();
            })
        })
    </script>
@endpush
