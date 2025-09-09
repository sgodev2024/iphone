@extends('Themes.layout_staff.app')

@section('content')
<style>
    .product-item1 {
        border: 1px solid #ddd;
        padding: 10px;
        height: auto;
        /* Tăng chiều cao để đảm bảo đủ không gian cho thông tin */
    }

    .product-item1 img {
        width: 100%;
        height: auto;
    }

    .product-name {
        font-size: 13px;
        margin-top: 5px;
        margin-bottom: 0px;
    }

    .product-quantity-unit {
        font-size: 13px;
        margin-top: 0px;
        margin-bottom: 5px;
    }

    .product-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .product-price {
        font-size: 13px;
        margin: 5px 0 0 0;
    }

    .add-to-cart {
        margin: 0;
        cursor: pointer;
    }

    .add-to-cart i {
        font-size: 15px;
        color: rgb(105, 97, 223);
    }

    #listproduct .product-item1 .card-body img {
        width: 145px;
        height: auto;
        object-fit: cover;
    }

    .card-body {
        max-height: 400px;
        padding: 10px;
    }

    .icon-bell:before {
        content: "\f0f3";
        font-family: "Font Awesome 5 Free";
        font-weight: 900;
    }

    .input-group {
        position: relative;
    }

    .results {
        list-style-type: none;
        padding: 0;
        margin-top: 10px;
        border: 1px solid #ccc;
        max-height: 150px;
        overflow-y: auto;
        display: none;
        position: absolute;
        width: 95%;
        background-color: white;
        z-index: 1000;
    }

    .results li {
        padding: 10px;
        border-bottom: 1px solid #ccc;

    }

    .results li:last-child {
        border-bottom: none;
    }

    .results li:hover {
        background-color: #f0f0f0;
    }

    .no-results {
        text-align: center;
        color: #888;
    }

    .product-item {
        display: flex;
        flex-wrap: wrap;

    }

    .alert {
        width: calc(33.33% - 20px);
        margin-right: 20px;
        padding: 15px;
        box-sizing: border-box;
        position: relative;
    }

    .alert:last-child {
        margin-right: 0;
    }

    .closebtn {
        margin-left: 20px;
        position: absolute;
        top: 30%;
        right: 10px;
        cursor: pointer;
    }

    .alert strong {
        font-weight: bold;
    }

    .alert span {
        margin-left: 10px;
    }

    .closebtn:hover {
        color: red;
        font-weight: bold;

    }

    .custom-input {
        width: 100px;
        padding: 5px;
        text-align: center;
        font-size: 14px;
        border-radius: 5px;
    }

    .product-name {
        font-size: 13px;
        margin-top: 5px;
        margin-bottom: 0px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        max-width: 80px;
    }

    .card-header {
        display: flex;
        justify-content: space-between;
    }

    #timkiem {
        color: rgb(5, 5, 5) !important;
        border: 1px solid;
    }
</style>
<!-- Content section -->
<div class="container-fluid mt-4">
    <div class="row" id="row">
        <!-- Left Column: Product List -->
        <div class="col-lg-8" id="row1">
            <div class="card">
                <div class="card-header">
                    <p style="font-weight: 800">Sản phẩm</p>
                    <form class="form-inline my-2 my-lg-0 search-bar" action="{{ route('staff.product.search') }}"
                        method="GET">
                        @csrf
                        <input id="search_product" class="form-control mr-sm-2" name="name" type="search"
                            placeholder="Tìm kiếm sản phẩm..." aria-label="Search">
                    </form>
                </div>
                <div class="card-body" style="overflow-x: hidden;">
                    <!-- Product items go here -->
                    <div class="row" id="productContainer">

                    </div>
                </div>
            </div>
            <div class="card" id="regular-selling-content1" style="display: none;"></div>
            <!-- thanh toán -->
            <div class="row mt-4 main_note" style="margin: 0px;">
                <div class="col-lg-8 mt-4 mb-4">
                    <div class="product-item">

                    </div>
                </div>
                <div class="col-lg-4 mt-4 mb-4">
                    <ul class="list-unstyled">
                        <li class="d-flex justify-content-between align-items-center">
                            Tổng tiền hàng
                            <span class="badge  badge-pill" id="total-amount">
                                0 {{-- {{ number_format($sum)}} --}}
                            </span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mt-2">
                            Giảm giá
                            <span class="badge  badge-pill" id="discount">0</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center mt-2">
                            Khách cần trả
                            <span class="badge badge-pill" id="total-to-pay">
                                0 {{-- {{ number_format($sum)}} --}}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Right Column: Customer Information and Payment Method -->
        @include('Themes.pages.layout_staff.delivery-selling')
    </div>
</div>

<!-- Right Column: Customer Information and Payment Method 123-->
{{-- @include('Themes.pages.layout_staff.delivery-selling') --}}

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
    var $j = jQuery.noConflict();

        $j(document).ready(function() {
            $j.ajax({
                url: '{{ route('staff.product.get') }}',
                type: 'GET',
                success: function(data) {
                    var productContainer = $j('#productContainer');
                    productContainer.empty();
                    data.forEach(function(item) {
                        console.log(item);
                        var product = item.product; // Thông tin sản phẩm từ quan hệ Eloquent
                        var images = product.images && product.images.length > 0 ? product
                            .images[0].image_path : 'public/images/1.jpg';
                        var imageUrl = "{{ asset('/storage/') }}" + images.replace('public/', '');

                        var productHtml = `
                        <div class="col-md-2 mb-3" style="cursor: pointer;">
                            <div class="product-item1" title="${product.name}">
                                <div class="card-body listproduct" data-id="${product.id}">
                                    <div>
                                        <img src="${imageUrl}" alt="" style="max-width: 75px; height: 50px;">
                                    </div>
                                    <p class="card-title product-name">${product.name}</p>
                                    <p class="product-quantity-unit">còn ${item.quantity} ${product.product_unit}</p>
                                    <div class="product-info">
                                        <p class="card-title product-price">${numberFormat(product.price_buy)}đ</p>
                                        <p class="add-to-cart"><i class="fas fa-shopping-cart fa-lg"></i></p>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                        productContainer.append(productHtml);
                    });
                },
                error: function(xhr, status, error) {
                    console.log('AJAX Error: ' + status + error);
                }
            });

            function numberFormat(number) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(number).replace('₫', '');
            }

            $j("#search_product").on("keyup", function() {
                var name = $(this).val();

                $j.ajax({
                    url: '{{ route('staff.product.search') }}',
                    type: 'GET',
                    data: {
                        name: name
                    },
                    success: function(data) {
                        var productContainer = $('#productContainer');
                        productContainer.empty(); // Clear previous products

                        if (data.length > 0) {
                            data.forEach(function(item) {
                                var images = item.images && item.images.length > 0 ?
                                    item.images[0].image_path : 'public/images/1.jpg';
                                var imageUrl = "{{ asset('/storage/') }}" + images.replace(
                                    'public/', '');
                                var productHtml = `
                                        <div class="col-md-2 mb-3" style="cursor: pointer;">
                                            <div class="product-item1" title="${item.name}">
                                                <div class="card-body listproduct" data-id="${item.id}">
                                                    <div>
                                                        <img src="${imageUrl}" alt="" style="max-width: 75px; height: 50px;">
                                                    </div>
                                                    <p class="card-title product-name">${item.name}</p>
                                                    <p class="product-quantity-unit">${item.quantity} ${item.product_unit}</p>
                                                    <div class="product-info">
                                                        <p class="card-title product-price">${numberFormat(item.price_buy)}đ</p>
                                                        <p class="add-to-cart"><i class="fas fa-shopping-cart fa-lg"></i></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>`;
                                productContainer.append(productHtml);
                            });
                        } else {
                            productContainer.append(
                                '<p style="padding: 30px;">Không tìm thấy sản phẩm.</p>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('AJAX Error: ' + status + ' ' + error);
                    }
                });


            });


            $j("#search").on("keyup", function() {
                var query = $j(this).val().toLowerCase();
                var hasResults = false;

                if (query.length > 0) {
                    $j("#results").show();
                    $j("#results li").each(function() {
                        var name = $j(this).text().toLowerCase();
                        if (name.includes(query)) {
                            $j(this).show();
                            hasResults = true;
                        } else if (!$j(this).hasClass("no-results")) {
                            $j(this).hide();
                        }
                    });

                    if (hasResults) {
                        $j(".no-results").hide();
                    } else {
                        $j(".no-results").show();
                    }
                } else {
                    $j("#results").hide();
                }
            });



            $j(document).on('click', '.listproduct', function(e) {
                e.preventDefault();
                var productId = $j(this).data('id');
                $j.ajax({
                    url: '{{ route('staff.cart.add') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: productId,
                        amount: 1
                    },
                    success: function(response) {
                        updateCart(response.cart);
                        updateCartBill(response.cart);
                        var submitBuyOrderBill = document.getElementById('submitBuyOrderBill');
                        if (response.cart.length <= 0) {
                            submitBuyOrderBill.setAttribute('disabled', 'disabled');
                        } else {
                            submitBuyOrderBill.removeAttribute('disabled');
                        }

                        var total_amount = $j('#total-amount');
                        var totalBill = $j('.totalBill');
                        var totalPay = $j('.totalPay');
                        var totalDue = $j('.totalDue');
                        var dangchu = $j('#dangchu');
                        dangchu.text(convertNumberToWords(convertTextToNumber(response.sum)));
                        totalBill.text(response.sum + " VND");
                        totalPay.text(response.sum + " VND");
                        totalDue.text(response.sum + " VND");
                        total_amount.text(response.sum);
                        var total_amount_to_pay = $j('#total-to-pay');
                        total_amount_to_pay.text(response.sum);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            });

            $j(document).on('input', '.custom-input', function(e) {
                e.preventDefault();
                var input = $j(this);
                var productId = input.data('id');
                var max = input.attr('max');
                var amount = input.val();

                if (parseInt(amount) > parseInt(max)) {
                    amount = max;
                    input.val(amount);
                }

                $j.ajax({
                    url: '{{ route('staff.cart.update') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        product_id: productId,
                        amount: amount
                    },
                    success: function(response) {
                        updateCart(response.cart);
                        updateCartBill(response.cart);
                        var submitBuyOrderBill = document.getElementById('submitBuyOrderBill');
                        if (response.cart.length <= 0) {
                            submitBuyOrderBill.setAttribute('disabled', 'disabled');
                        } else {
                            submitBuyOrderBill.removeAttribute('disabled');
                        }
                        var total_amount = $j('#total-amount');
                        var totalBill = $j('.totalBill');
                        var totalPay = $j('.totalPay');
                        var totalDue = $j('.totalDue');
                        var dangchu = $j('#dangchu');
                        dangchu.text(convertNumberToWords(convertTextToNumber(response.sum)));
                        totalBill.text(response.sum + " VND");
                        totalPay.text(response.sum + " VND");
                        totalDue.text(response.sum + " VND");
                        total_amount.text(response.sum);
                        var total_amount_to_pay = $j('#total-to-pay');
                        total_amount_to_pay.text(response.sum);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            });

            $j(document).on('click', '.closebtn', function(e) {
                e.preventDefault();
                var cart = $j(this).data('id');
                $j.ajax({
                    url: '{{ route('staff.cart.remove') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        cart: cart,
                    },
                    success: function(response) {
                        updateCart(response.cart);
                        updateCartBill(response.cart);
                        var submitBuyOrderBill = document.getElementById('submitBuyOrderBill');
                        if (response.cart.length <= 0) {
                            submitBuyOrderBill.setAttribute('disabled', 'disabled');
                        } else {
                            submitBuyOrderBill.removeAttribute('disabled');
                        }

                        var total_amount = $j('#total-amount');
                        var totalBill = $j('.totalBill');
                        var totalPay = $j('.totalPay');
                        var totalDue = $j('.totalDue');
                        var dangchu = $j('#dangchu');
                        dangchu.text(convertNumberToWords(convertTextToNumber(response.sum)));
                        totalBill.text(response.sum + " VND");
                        totalPay.text(response.sum + " VND");
                        totalDue.text(response.sum + " VND");
                        total_amount.text(response.sum);
                        var total_amount_to_pay = $j('#total-to-pay');
                        total_amount_to_pay.text(response.sum);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });
            });

            $j('#paymentbill').submit(function(event) {
                event.preventDefault();

                var actionUrl = $(this).attr('action');
                var method = $(this).attr('method');

                $j.ajax({
                    url: actionUrl,
                    method: method,
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {

                        var downloadLink = document.createElement('a');
                        downloadLink.href = response.pdf_url;
                        downloadLink.download = 'order.pdf';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        alert('Đã xảy ra lỗi khi xử lý thanh toán và tải xuống.');
                    }
                });
            });


            $j(document).on('blur', '.change_price', function() {
                var price = parseInt($(this).text(), 10);
                var cart = $j(this).data('id');

                $j.ajax({
                    url: '{{ route('staff.cart.update.price') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        price: price,
                        cart: cart
                    },
                    success: function(response) {
                        updateCart(response.cart);
                        updateCartBill(response.cart);
                        var submitBuyOrderBill = document.getElementById('submitBuyOrderBill');
                        if (response.cart.length <= 0) {
                            submitBuyOrderBill.setAttribute('disabled', 'disabled');
                        } else {
                            submitBuyOrderBill.removeAttribute('disabled');
                        }
                        var total_amount = $j('#total-amount');
                        var totalBill = $j('.totalBill');
                        var totalPay = $j('.totalPay');
                        var totalDue = $j('.totalDue');
                        var dangchu = $j('#dangchu');
                        dangchu.text(convertNumberToWords(convertTextToNumber(response.sum)));
                        totalBill.text(response.sum + " VND");
                        totalPay.text(response.sum + " VND");
                        totalDue.text(response.sum + " VND");
                        total_amount.text(response.sum);
                        var total_amount_to_pay = $j('#total-to-pay');
                        total_amount_to_pay.text(response.sum);
                    },
                    error: function(xhr) {
                        alert(xhr.responseJSON.error);
                    }
                });

            })

            function updateCartBill(cart) {
                // Cập nhật nội dung giỏ hàng trong HTML
                var orderBill = $j('#orderBill');
                orderBill.empty();

                cart.forEach(function(item) {
                    var itemTotal = item.amount * item.price_buy;
                    var orderItem = `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.amount}</td>
                            <td>${item.price_buy.toLocaleString('en-US')}</td>
                            <td>${itemTotal.toLocaleString('en-US')}</td>
                        </tr>
                    `;
                    orderBill.append(orderItem);
                });
            }


            $j("#results").on("click", "li", function() {
                if (!$j(this).hasClass("no-results")) {
                    var fullName = $j(this).data("fullname");
                    var email = $j(this).data("email");
                    var phone = $j(this).data("phone");
                    var address = $j(this).data("address");
                    $j("#name").val(fullName);
                    $j("#email").val(email);
                    $j("#phoneNumber").val(phone);
                    $j("#address").val(address);
                    $j("#results").hide();
                }
            });

            function updateCart(cart) {
                var cartItems = $j('.product-item');
                cartItems.empty();
                if (cart.length === 0) {
                    cartItems.append('<p>Your cart is empty.</p>');
                } else {
                    $.each(cart, function(id, details) {
                        var cartItem =
                            '<div class="col-12 alert d-flex" style="justify-content: space-between; margin: 0px; padding-bottom: 0px;">' +
                            '<div ' + 'data-id = "' + details.id + '"' + ' class="closebtn">&times;</div>' +
                            '<div class="d-flex" style="margin-right: 109px; width: 100%; justify-content: space-between;">' +
                            '<strong style="width: 130px;">' + details.product_name +
                            '<div style="margin: 0; font-size: 13px; color: #888;"  contenteditable="true" class="change_price" ' +
                            'data-id = "' + details.id + '"' + ' >' + details.price_buy + '</div>' +
                            '</strong>' +
                            '<span><input type="number"' + 'data-id = "' + details.product_id + '"' +
                            '  min="1" max="' + details.quantity + '"  class="custom-input" value="' + details.amount + '"></span>' +
                            '<span style="width: 80px;">' + numberFormat(details.price_buy * details.amount) +
                            'đ</span>' +
                            '</div>' +
                            '</div>';
                        cartItems.append(cartItem);
                    });
                }


            }
        });

        function convertNumberToWords(num) {
            const units = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
            const teens = ["mười", "mười một", "mười hai", "mười ba", "mười bốn", "mười lăm", "mười sáu", "mười bảy",
                "mười tám", "mười chín"
            ];
            const tens = ["", "", "hai mươi", "ba mươi", "bốn mươi", "năm mươi", "sáu mươi", "bảy mươi", "tám mươi",
                "chín mươi"
            ];
            const scales = ["", "nghìn", "triệu", "tỷ"];

            if (num === 0) return "không đồng";

            let words = [];

            function convertChunk(num) {
                let chunk = [];

                if (num >= 100) {
                    chunk.push(units[Math.floor(num / 100)] + " trăm");
                    num %= 100;
                }

                if (num >= 20) {
                    chunk.push(tens[Math.floor(num / 10)]);
                    num %= 10;
                } else if (num >= 10) {
                    chunk.push(teens[num - 10]);
                    num = 0;
                }

                if (num > 0) {
                    chunk.push(units[num]);
                }

                return chunk.join(" ");
            }

            let scaleIndex = 0;
            while (num > 0) {
                let chunk = num % 1000;
                if (chunk > 0) {
                    words.unshift(convertChunk(chunk) + " " + scales[scaleIndex]);
                }
                num = Math.floor(num / 1000);
                scaleIndex++;
            }

            return words.join(" ").trim() + " việt nam đồng";
        }

        function convertTextToNumber(text) {

            const cleanedText = text.replace(/,/g, '').replace(/\s/g, '');

            const number = parseInt(cleanedText, 10);

            if (isNaN(number)) {
                throw new Error("Input không phải là số hợp lệ.");
            }

            return number;
        }

        function numberFormat(number) {
            return new Intl.NumberFormat('en-US', {
                style: 'decimal',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(number);
        }


        document.querySelectorAll('.change_price').forEach(function(element) {
            element.addEventListener('input', function(e) {
                const selection = window.getSelection();
                const range = document.createRange();
                let caretPos = selection.getRangeAt(0).startOffset;

                this.innerText = this.innerText.replace(/[^0-9]/g, '');

                range.setStart(this.firstChild, Math.min(caretPos, this.innerText.length));
                range.setEnd(this.firstChild, Math.min(caretPos, this.innerText.length));
                selection.removeAllRanges();
                // selection.addRange(range);
            });
        });

</script>
@endsection
