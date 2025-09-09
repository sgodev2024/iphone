<!DOCTYPE html>
<html>
<head>
    <title>Verify OTP</title>
    <style scoped>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
        }

        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        div {
            margin-bottom: 15px;
            width: 100%;
        }

        label {
            margin-bottom: 5px;
            display: block;
        }

        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        ul {
            list-style-type: none;
            padding: 0;
            color: #dc3545;
        }

        ul li {
            margin-top: 5px;
        }
    </style>
</head>
<body>
<form action="{{ url('/verify-otp') }}" method="post">
    @csrf
    <div>
        <label for="otp">OTP:</label>
        <input type="text" id="otp" name="otp">
    </div>
    <div>
        <button type="submit">Verify OTP</button>
    </div>
    @if ($errors->any())
        <div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</form>

</body>
</html>
