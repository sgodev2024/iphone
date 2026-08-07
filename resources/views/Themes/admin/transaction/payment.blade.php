<!DOCTYPE html>
<html lang="en">

<head>
    <title>Nạp tiền</title>
    <!-- Required meta tags -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Bootstrap CSS v5.2.1 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    .bg-gray {
        background-color: #f8f8f8 !important;
    }

    .bg-white {
        background-color: #ffffff !important;
    }

    .text-gray {
        color: gray !important;
    }

    .text-green {
        color: #5fb199 !important;
        font-weight: 600;
    }

    .form-group {
        display: flex !important;
    }

    .form-group .form-label {
        width: 26% !important;
        font-size: 14px !important;
    }

    .form-group span {
        width: 74% !important;
        font-size: 14px !important;
        font-weight: 600;
        margin-bottom: 10px;
    }
</style>

<body class="bg-gray">
    <div class="container mt-5" style="max-width: 900px">
        <div class="card">
            <div class="card-header d-flex align-items-center">
                <div class="icon me-2">
                    <i class="fas fa-donate" style="color: #74c0fc; font-size: x-large"></i>
                </div>
                <div class="title">
                    <h6 class="card-title m-0" style="color: gray">Chuyển khoản</h6>
                    <small class="m-0 text-muted">
                        Chuyển khoản trực tiếp đến tài khoản ngân hàng
                    </small>
                </div>
            </div>
            <div class="card-body px-5 bg-gray">
                {{-- <div style="background-color: rgba(131, 224, 196, 0.2)" class="d-flex align-items-center ps-2">
                    <i class="fas fa-exclamation-circle m-2" style="color: #1f7b60"></i>
                    <p class="m-0 text-muted">
                        Số tiền sẽ được cập nhật ngay vào tài khoản ZCA của khách hàng.
                    </p>
                </div> --}}
                <div class="row mt-3">
                    <div class="form-group col-6">
                        <input type="text" class="form-control" name="money" placeholder="Nhập tiền" />
                    </div>
                    <div class="form-group col-6">
                        <input type="text" class="form-control" name="amount" placeholder="Tổng tiền" />
                    </div>
                    <div class="form-group col-12 mt-3">
                        <input type="text" class="form-control" name="description"
                            placeholder="Nội dụng chuyển khoản" />
                    </div>
                </div>
                <div class="invoice mt-3">
                    <p class="fw-bold mb-2" style="color: gray">Yêu cầu hóa đơn</p>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="flexRadioDefault" id="noInvoice"
                                checked />
                            <label class="form-check-label" for="flexRadioDefault1">
                                Không xuất hóa đơn
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="flexRadioDefault"
                                id="requestInvoice" />
                            <label class="form-check-label" for="flexRadioDefault1">
                                Xuất hóa đơn
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card bg-white mt-3 d-none" id="invoiceDetails">
                    <div class="card-header">
                        <p class="fw-bold my-2" style="color: gray">
                            Thông tin xuất hóa đơn
                        </p>

                        <div class="card-body border rounded">
                            <div class="d-flex gap-3 align-items-center">
                                <h6 class="text-gray">
                                    {{ $authUser->company_name }} -
                                    MST : {{ $authUser->tax_code ?? 'Chưa có' }}
                                </h6>
                                {{-- <p class="text-green" style="margin-bottom: 0.5rem !important">
                                    Mặc định
                                </p> --}}
                            </div>
                            <div class="form-group">
                                <label for="" class="form-label">Địa chỉ</label>
                                <span class="text-muted">{{ $authUser->address }}, {{ $authUser->city->name }}</span>
                            </div>
                            <div class="form-group">
                                <label for="" class="form-label">Họ tên</label>
                                <span class="text-muted">{{ $authUser->name }}</span>
                            </div>
                            <div class="form-group">
                                <label for="" class="form-label">Số điện thoại</label>
                                <span class="text-muted">{{ $authUser->phone ?? 'Chưa có' }}</span>
                            </div>
                            <div class="form-group">
                                <label for="" class="form-label">Email</label>
                                <span class="text-muted">{{ $authUser->email ?? 'Chưa có' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="float-end">
                    <button class="text-white mt-3 border-0 continue-btn"
                        style="
                padding: 8px 15px;
                font-weight: 600;
                background-color: #122be6;
                font-size: 12px;
              ">
                        Tiếp tục
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="qrModalLabel">QR Code</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <p>Vui lòng quét mã QR dưới đây để chuyển khoản:</p>
                    <img id="qrCodeImage" src="" alt="QR Code" class="img-fluid" />
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {

            //Hiện modal


            // Hide invoice details by default
            $("#noInvoice").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#invoiceDetails").addClass("d-none");
                }
            });

            // Show invoice details when "Xuất hóa đơn" is selected
            $("#requestInvoice").on("change", function() {
                if ($(this).is(":checked")) {
                    $("#invoiceDetails").removeClass("d-none");
                }
            });
        });
    </script>
    <!-- Bootstrap JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"
        integrity="sha384-BBtl+eGJRgqQAUMxJ7pMwbEyER4l1g+O15P+16Ep7Q9Q+zqX6gSbd85u4mG4QzX+" crossorigin="anonymous">
    </script>
</body>

</html>
