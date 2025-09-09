<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style scoped>
        body, html {
            width: 100%;
            /*height: 100%;*/
            /*margin: 0;*/
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 50%;
            text-align: center;
        }

        .container form {
            display: inline-block;
            margin-left: 20px;
        }

        .product-list {
            margin-top: 20px;
            text-align: left;
        }
        .content{
            display: flex;
            justify-content: space-around;
            align-items: center;
        }
        .product-list {
            width: 400px;
            margin: 0 auto;
        }

        .product-list h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .product-list form {
            width: 100%;
        }

        .product-list label {
            display: block;
            margin-bottom: 5px;
        }

        .product-list input[type="text"],
        .product-list input[type="number"] {
            width: calc(100% - 10px);
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }

        .product-list button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .product-list button:hover {
            background-color: #0056b3;
        }
        .product-list1 button {

            padding: 10px;
            background-color: darkred;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .product-list1 button:hover {
            background-color: red;
        }
    </style>
</head>
<body>
<div class="container">
  <div class="content">
      <h1>Welcome, {{ Auth::user()->name }}</h1>
      <form action="{{ route('logout') }}" method="POST">
          @csrf
          <div class="product-list1">
              <button  type="submit">Logout</button>
          </div>
      </form>
  </div>

    <div class="product-list">
        <h2>Nhập thông tin</h2>
        <form action="{{route("payment.process")}}" method="POST">
            @csrf
            <label for="name">Name:</label>
            <input type="text" id="name" name="name" value="{{ Auth::user()->name }}" >
            <label for="address">Address:</label>
            <input type="text" id="address" name="address" >
            <label for="amount">Amount:</label>
            <input type="number" id="amount" name="amount" min="0" step="0.01" required>
            <button type="submit" >Pay Now</button>
        </form>
    </div>
</div>
</body>
</html>
