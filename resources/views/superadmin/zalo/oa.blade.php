@extends('superadmin.layout.index')

@section('content')
    <div class="container">
        <!-- Hiển thị thông báo thành công và thất bại -->
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <h2>OA Đang Kích Hoạt:</h2>
        <div class="card mb-3">
            <div class="card-body">
                <p id="activeOaName">
                    @php
                        $activeOa = $connectedApps->firstWhere('is_active', 1);
                        if ($activeOa) {
                            echo $activeOa->name;
                        } else {
                            echo 'Chưa có OA nào được kích hoạt';
                        }
                    @endphp
                </p>

                @if ($activeOa)
                    <div class="mt-3">
                        <h4>Thông tin OA hiện tại</h4>
                        <p><strong>Access Token:</strong>
                            <span id="accessTokenDisplay" data-token="{{ $activeOa->access_token }}">
                                {{ substr($activeOa->access_token, 0, 20) }}...{{ substr($activeOa->access_token, -10) }}
                            </span>
                            <button class="btn btn-secondary btn-sm" onclick="copyToClipboard('accessTokenDisplay')">Sao
                                chép</button>
                        </p>
                        <p><strong>Refresh Token:</strong>
                            <span id="refreshTokenDisplay" data-token="{{ $activeOa->refresh_token }}">
                                {{ substr($activeOa->refresh_token, 0, 20) }}...{{ substr($activeOa->refresh_token, -10) }}
                            </span>
                            <button class="btn btn-secondary btn-sm" onclick="copyToClipboard('refreshTokenDisplay')">Sao
                                chép</button>
                        </p>
                        <button class="btn btn-secondary" id="refreshTokenBtn">Làm mới Access Token</button>
                    </div>
                @endif
            </div>
        </div>

        <h2>Kết nối Zalo OA</h2>
        <div class="card mb-3">
            <div class="card-body">
                <div class="form-group">
                    <label for="zaloOaInfo">Zalo OA</label>
                    <select class="form-control" id="zaloOaInfo">
                        <option value="" disabled selected>Chọn OA</option>
                        @forelse ($connectedApps as $app)
                            <option value="{{ $app->oa_id }}" data-access-token="{{ $app->access_token }}"
                                data-refresh-token="{{ $app->refresh_token }}" data-is-active="{{ $app->is_active }}">
                                {{ $app->name }}
                            </option>
                        @empty
                            <option value="">Không có ứng dụng nào</option>
                        @endforelse
                    </select>
                </div>
                <!-- Thêm nút kết nối -->
                <button class="btn btn-primary" id="connectOaBtn" disabled>Kết nối Zalo OA</button>
            </div>
        </div>

        @if ($connectedApps->isEmpty())
            <div class="alert alert-danger" role="alert">
                Không có dữ liệu Zalo OA. Vui lòng kiểm tra token hoặc địa chỉ API.
            </div>
        @endif
    </div>

    <!-- Thêm thẻ meta CSRF Token nếu chưa có -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        const connectOaBtn = document.getElementById('connectOaBtn');
        const zaloOaInfo = document.getElementById('zaloOaInfo');
        const refreshTokenBtn = document.getElementById('refreshTokenBtn');

        zaloOaInfo.addEventListener('change', function() {
            const oaId = this.value;
            connectOaBtn.disabled = !oaId; // Chỉ bật nút khi có OA được chọn
        });

        connectOaBtn.addEventListener('click', function() {
            const oaId = zaloOaInfo.value;

            if (oaId) {
                const url = `{{ route('super.zalo.updateOaStatus', ['oaId' => '__oaId__']) }}`.replace('__oaId__',
                    oaId);

                // Lấy token CSRF
                const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
                const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateActiveOaInfo(data.activeOaName, data.accessToken, data.refreshToken);
                            Swal.fire({
                                icon: 'success',
                                title: 'Thành công',
                                text: 'OA đã được kết nối thành công!',
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Lỗi',
                                text: data.message,
                            });
                        }
                    })
                    .catch(error => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: 'Đã xảy ra lỗi khi kết nối OA!',
                        });
                        console.error('Fetch Error:', error);
                    });
            }
        });

        refreshTokenBtn.addEventListener('click', function() {
            const url = `{{ route('super.zalo.refreshAccessToken') }}`;

            // Lấy token CSRF
            const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('accessTokenDisplay').textContent = data.new_access_token.slice(
                            0, 20) + '...' + data.new_access_token.slice(-10);
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: data.message,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: data.message,
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Đã xảy ra lỗi khi làm mới access token!',
                    });
                    console.error('Fetch Error:', error);
                });
        });

        function copyToClipboard(id) {
            const element = document.getElementById(id);
            const text = element.dataset.token; // Lấy giá trị đầy đủ từ data-token
            navigator.clipboard.writeText(text)
                .then(() => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Sao chép thành công',
                        text: 'Giá trị đã được sao chép vào clipboard.',
                    });
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: 'Đã xảy ra lỗi khi sao chép.',
                    });
                    console.error('Copy Error:', err);
                });
        }

        function updateActiveOaInfo(name, accessToken, refreshToken) {
            document.getElementById('activeOaName').textContent = name || 'Chưa có OA nào được kích hoạt';
            const accessTokenDisplay = document.getElementById('accessTokenDisplay');
            const refreshTokenDisplay = document.getElementById('refreshTokenDisplay');
            accessTokenDisplay.dataset.token = accessToken;
            accessTokenDisplay.textContent = accessToken.slice(0, 20) + '...' + accessToken.slice(-10);
            refreshTokenDisplay.dataset.token = refreshToken;
            refreshTokenDisplay.textContent = refreshToken.slice(0, 20) + '...' + refreshToken.slice(-10);
        }
    </script>
@endsection
