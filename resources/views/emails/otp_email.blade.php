<!-- resources/views/emails/otp_email.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Code</title>
</head>
<body>
<p>Xin chào {{ $user->name }},</p>
<p>Mã OTP của bạn là: <strong>{{ $otp }}</strong></p>
<p>Vui lòng sử dụng mã này để xác thực trong 5 phút.</p>
<p>Trân trọng,</p>
</body>
</html>
