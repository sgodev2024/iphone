@extends('admin.layout.index')

@section('content')
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        header {
            text-align: center;
            padding: 20px 0;
            background: linear-gradient(135deg, #6f42c1, #007bff);
            color: #fff;
        }

        h1 {
            margin: 0;
        }

        .contact-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .contact-item {
            flex: 1 1 22%;
            margin: 10px;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
        }

        .contact-item h2 {
            margin-top: 0;
            font-size: 1.2em;
            color: #5c2cc0;
        }

        .contact-item p {
            margin: 5px 0;
            color: #333;
        }

        .zalo-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .zalo-link:hover {
            background-color: #0056b3;
        }

        .btn {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            color: #fff;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(90deg, #007bff, #0056b3);
        }

        .btn-primary:hover {
            background: linear-gradient(90deg, #0056b3, #003f7f);
        }

        .message {
            margin: 10px 0;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }
    </style>
    <header>
        <h1>Trang Hỗ Trợ</h1>
    </header>

    <div class="container">
        <form action="{{ route('admin.support.feedback') }}" method="post">
            @csrf
            <div class="contact-info">
                <div class="contact-item">
                    <h2>Địa chỉ</h2>
                    <p>123 Đường ABC, Quận XYZ, Thành phố Hồ Chí Minh</p>
                </div>
                <div class="contact-item">
                    <h2>Số điện thoại</h2>
                    <p>+84 123 456 789</p>
                </div>
                <div class="contact-item">
                    <h2>Email</h2>
                    <p>support@example.com</p>
                </div>
                <div class="contact-item">
                    <h2>Zalo</h2>
                    <p><a class="zalo-link" href="https://zalo.me/tentkhoan">Zalo của tôi</a></p>
                </div>
            </div>
            <div style="margin: 10px 10px;">
                <div class="message">
                    <h2>Góp ý</h2>
                    <textarea name="message" id="message" rows="5" class="form-control"></textarea>
                </div>
                <input class="btn btn-primary" type="submit" value="Gửi">
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-notify/0.2.0/js/bootstrap-notify.min.js"></script>
    @if (session('success'))
        <script>
            $(document).ready(function() {
                $.notify({
                    icon: 'icon-bell',
                    title: 'Đánh giá',
                    message: '{{ session('success') }}',
                }, {
                    type: 'secondary',
                    placement: {
                        from: "bottom",
                        align: "right"
                    },
                    time: 1000,
                });
            });
        </script>
    @endif
@endsection
