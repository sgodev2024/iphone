<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <title>Tài khoản quản trị mới</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border:1px solid #e5e5e5; border-radius: 8px;">
        <h2 style="color: #0d6efd; text-align: center;">Chào {{ $user->name }},</h2>

        <p>Tài khoản quản trị của bạn đã được tạo thành công bởi Super Admin.</p>

        <h3>Thông tin đăng nhập:</h3>
        <ul style="list-style: none; padding: 0;">
            <li><strong>Email:</strong> {{ $user->email }}</li>
            <li><strong>Mật khẩu:</strong> {{ $password }}</li>
        </ul>

        <p>Bạn có thể đăng nhập vào hệ thống bằng cách nhấp vào nút dưới đây:</p>

        <p style="text-align: center;">
            <a href="{{ route('auth.login') }}"
                style="display: inline-block; padding: 10px 20px; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 6px;">
                Đăng nhập ngay
            </a>
        </p>

        {{-- <p style="margin-top: 20px;">
            Vì lý do bảo mật, bạn nên <strong>đổi mật khẩu</strong> ngay sau lần đăng nhập đầu tiên.
        </p> --}}

        <p>Trân trọng,<br>Đội ngũ quản trị hệ thống</p>
    </div>
</body>

</html>
