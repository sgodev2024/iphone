<div class="col-lg-4" id="delivery-selling-content" style="display: none;">
    <div class="card">
        <div class="card-header">Thông tin khách hàng</div>
        <div class="card-body" style="max-height: 700px; overflow-y: auto;">
            <!-- Customer information form -->
            <form action="">
                <div class="input-group">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Tìm kiếm khách hàng" name="search"
                        id="search" />
                    <i class="fas fa-plus" data-toggle="modal" data-target="#customerModal"></i>
                </div>
                <ul class="results" id="results">
                    @if ($clients)
                        @foreach ($clients as $item)
                            <li data-fullname="{{ $item->name }}" data-email="{{ $item->email }}"
                                data-phone="{{ $item->phone }}" data-address="{{ $item->address }}">
                                {{ $item->name . '(' . $item->phone . ')' }}</li>
                        @endforeach
                    @endif
                    <li class="no-results">Không có kết quả</li>
                    <!-- Thêm phần tử này -->
                </ul>
            </form>
            <form id="ordersubmit" method="GET">
                @csrf
                <div class="form-group">
                    <label for="fullName">Tên</label>
                    <input type="text" class="form-control" id="name" placeholder="Nhập họ tên">
                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                            id="orderName_error"></span> </div>
                </div>
                <div class="form-group">
                    <label for="email">Email </label>
                    <input type="email" class="form-control" id="email" placeholder="Nhập email">
                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                            id="orderEmail_error"></span> </div>
                </div>
                <div class="form-group">
                    <label for="phoneNumber">Số điện thoại</label></label>
                    <input type="tel" class="form-control" id="phoneNumber" placeholder="Nhập số điện thoại">
                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                            id="orderPhone_error"></span> </div>
                </div>

                <div class="form-group">
                    <label for="address">Địa chỉ</label>
                    <input type="address" class="form-control" id="address" placeholder="Nhập địa chỉ">
                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                            id="orderAddress_error"></span> </div>
                </div>
                {{-- <div class="form-group">
                    <label for="clientgroup">Nhóm khách hàng</label></label>
                    <select class="form-control" id="clientgroup_id" name="clientgroup_id">
                        <option value="">----- Nhóm khách hàng ----- </option>
                        @foreach ($clientgroup as $item)
                        <option value="{{ $item->id }}"> {{ $item->name }}</option>
                        @endforeach
                    </select>

                </div> --}}
                <div class="form-group">
                    <label for="paymentMethod">Phương thức thanh toán</label></label>
                    <select class="form-control" id="paymentMethod" name="status">
                        <option value="">----- Chọn phương thức ----- </option>
                        <option value="1">Tiền mặt</option>
                        <option value="2">Mã QR</option>
                        <option value="3">Chuyển khoản ngân hàng</option>
                        <option value="4">Công nợ</option>
                    </select>
                    <div class="col-lg-9"><span class="invalid-feedback d-block" style="font-weight: 500"
                            id="orderPay_error"></span> </div>
                </div>

                <button type="button" id="submitBuyOrderBill" disabled onclick="submitorder(event)"
                    class="btn btn-primary btn-block">Tạo hóa đơn</button>

            </form>
        </div>
    </div>

    <div class="modal fade" id="orderModal" tabindex="-1" role="dialog" aria-labelledby="orderModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel" style="text-align: center">Hóa đơn</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalContent" style="padding: 0px;">
                    <div class="receipt">
                        <div class="receipt-title">
                            <h2>HÓA ĐƠN THANH TOÁN</h2>
                        </div>
                        <div class="receipt-header">
                            <h3 style="font-size: 20px">{{ isset($config) ? $config->user->store_name : '' }}</h3>
                            <table style="width: 40%; margin: 0px auto" id="thongtinnhahang">
                                <tr>
                                    <td
                                        style="
                                    display: flex;
                                    justify-content: start;
                                ">
                                        <strong>Địa chỉ:</strong></td>
                                    <td style="text-align: left;">{{ isset($config) ? $config->user->address : '' }}
                                    </td>
                                </tr>
                                <tr>
                                    <td
                                        style="
                                    display: flex;
                                    justify-content: start;
                                ">
                                        <strong>Điện thoại:</strong></td>
                                    <td style="text-align: left;">{{ isset($config) ? $config->user->phone : '' }}</td>
                                </tr>
                                <tr>
                                    <td
                                        style="
                                    display: flex;
                                    justify-content: start;
                                ">
                                        <strong>Email:</strong></td>
                                    <td style="text-align: left;">{{ isset($config) ? $config->user->email : '' }}</td>
                                </tr>
                            </table>

                        </div>
                        <div class="receipt-info">
                            <p>Ngày tạo: ${getCurrentDate()}</p>
                            <p>Tên khách: </p>
                            <p>Số điện thoại: </p>
                            <p>Tên thu ngân: Trần Thị B</p>
                        </div>
                        <div class="receipt-items">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Số lượng</th>
                                        <th>Đơn giá</th>
                                        <th>Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody id="orderBill">
                                    @foreach ($cart as $item)
                                        <tr>
                                            <td>{{ $item->product->name }}</td>
                                            <td>{{ $item->amount }}</td>
                                            <td>{{ number_format($item->product->price) }}</td>
                                            <td>{{ number_format($item->product->price_buy * $item->amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="receipt-totals">
                            <div class="total">
                                <span>Tổng cộng</span>
                                <span class='totalBill'>{{ number_format($sum) }} VND</span>
                            </div>
                            <div id="dangchu" class="dangchu"></div>
                            <hr>
                            <div class="total">
                                <span>Tổng tiền phải trả</span>
                                <span class='totalPay'>{{ number_format($sum) }} VND</span>
                            </div>
                            {{-- <hr>
                            <div class="total">
                                <span>Còn phải trả</span>
                                <span class='totalDue'>{{number_format( $sum) }} VND</span>
                            </div> --}}
                        </div>
                        <div class="receipt-footer">
                            <p style='margin: 0px;'>Cảm ơn quý khách!</p>
                            @if (isset($config))
                                <img style="width: 200px;" src="{{ $config->qr }}" alt="QR Code">
                                <div>
                                    <p style="margin: 0; padding-bottom: 5px ">{{ $config->bank->name }}
                                        :{{ $config->bank_account }} </p>
                                    <p>{{ $config->receiver }}</p>
                                </div>
                            @else
                                <img style="width: 200px;" src="" alt="QR Code">
                            @endif

                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="justify-content: center">
                    <form action="{{ route('staff.pay') }}" method="POST" id="paymentbill">
                        @csrf
                        <div id="info_client"></div>
                        <button type="submit" class="btn btn-secondary">Thanh toán</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>



<style>
    #orderModal .modal-dialog {
        max-width: 50%;
        margin: 1.75rem auto;
    }

    #orderModal .modal-content {
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }

    #orderModal .modal-body {
        padding: 2rem;
    }

    #orderModal .receipt {
        font-family: Arial, sans-serif;
        background-color: #fff;
        padding: 1.5rem;
    }

    #orderModal .receipt-header h2 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }

    #orderModal .receipt-title h3 {
        font-size: 1.25rem;
        margin-bottom: 1.5rem;
    }

    #orderModal .receipt-info p {
        margin-bottom: 0.5rem;
    }

    #orderModal .receipt-items table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1.5rem;
    }

    #orderModal .receipt-items th,
    #orderModal .receipt-items td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #dee2e6;
    }

    #orderModal .receipt-totals {
        margin-top: 1.5rem;
        margin-bottom: 1rem;
    }

    odal .receipt-totals .total {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
    }

    #orderModal .receipt-totals hr {
        margin: 0.75rem 0;
    }

    #orderModal .receipt-footer {
        text-align: center;
    }

    #orderModal .receipt-footer img {}


    #bill {
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        margin-top: 100px;
        max-width: 900px;
        position: relative;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .close-btn {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }

    .close-btn:hover,
    .close-btn:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    .receipt-header,
    .receipt-footer {
        text-align: center;
    }

    .receipt-header h2 {
        margin: 0;
    }

    .receipt-title {
        text-align: center;
        margin: 20px 0;
    }

    .receipt-info p {
        margin: 5px 0;
    }

    .receipt-items table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .receipt-items th,
    .receipt-items td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    .receipt-totals {
        margin: 20px 0;
    }

    .total {
        display: flex;
        justify-content: space-between;
        font-weight: bold;
    }

    .qrcode {
        width: 250px;
        height: auto;
    }

    .dangchu {
        text-align: end;
        font-size: 13px;
        color: gray;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    var validateorder = {
        'name': {
            'element': document.getElementById('name'),
            'error': document.getElementById('orderName_error'),
            'validations': [{
                'func': function(value) {
                    return checkRequired(value);
                },
                'message': generateErrorMessage('E032')
            }, ]
        },
        // 'email': {
        //     'element': document.getElementById('email'),
        //     'error': document.getElementById('orderEmail_error'),
        //     'validations': [
        //         {
        //             'func': function(value){
        //                 return checkRequired(value);
        //             },
        //             'message': generateErrorMessage('E033')
        //         },
        //     ]
        // },
        'phoneNumber': {
            'element': document.getElementById('phoneNumber'),
            'error': document.getElementById('orderPhone_error'),
            'validations': [{
                'func': function(value) {
                    return checkRequired(value);
                },
                'message': generateErrorMessage('E034')
            }, ]
        },
        'address': {
            'element': document.getElementById('address'),
            'error': document.getElementById('orderAddress_error'),
            'validations': [{
                'func': function(value) {
                    return checkRequired(value);
                },
                'message': generateErrorMessage('E035')
            }, ]
        },
        'paymentMethod': {
            'element': document.getElementById('paymentMethod'),
            'error': document.getElementById('orderPay_error'),
            'validations': [{
                'func': function(value) {
                    return checkRequired(value);
                },
                'message': generateErrorMessage('E036')
            }, ]
        },

    }

    function getCurrentDate() {
        var today = new Date();
        var dd = String(today.getDate()).padStart(2, '0');
        var mm = String(today.getMonth() + 1).padStart(2, '0');
        var yyyy = today.getFullYear();

        return dd + '/' + mm + '/' + yyyy;
    }

    function submitorder(event) {
        event.preventDefault();
        if (validateAllFields(validateorder)) {
            var name = $('#name').val();
            var email = $('#email').val();
            var phoneNumber = $('#phoneNumber').val();
            var address = $('#address').val();
            var paymentMethod = $('#paymentMethod').val();


            var modalContent = `
                        <p>Ngày tạo: ${getCurrentDate()}</p>
                        <p>Tên khách: ${name}</p>
                        <p>Số điện thoại: ${phoneNumber}</p>
                        <p>Tên thu ngân: {{ Auth::user()->name }} </p>
                `;

            var info_client = `
                    <input type="hidden" name="name" value="${name}">
                    <input type="hidden" name="email" value="${email}">
                    <input type="hidden" name="phone" value="${phoneNumber}">
                    <input type="hidden" name="address" value="${address}">
                    <input type="hidden" name="status" value="${paymentMethod}">

                `;

            $('.receipt-info').html(modalContent);
            $('#info_client').html(info_client);
            $('#orderModal').modal('show');

        }
    }


    // Hàm giả định để lấy ngày hiện tại
    function getCurrentDate() {
        var today = new Date();
        return today.getDate() + '/' + (today.getMonth() + 1) + '/' + today.getFullYear();
    }
</script>
