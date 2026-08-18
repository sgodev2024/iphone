@if (!$isFullyReturned)
    <div class="card mt-3" id="exchangeCard">

        <div class="card-header py-2 px-3">
            <div class="d-flex align-items-center justify-content-between">
                <h6 class="card-title mb-0">
                    Hàng khách lấy mới
                </h6>

                <span class="text-muted small">
                    Không bắt buộc nếu chỉ trả hàng
                </span>
            </div>
        </div>

        <div class="card-body p-3">

            {{-- Khu vực tìm / quét hàng mới --}}
            <div class="row g-2 mb-3">

                <div class="col-md-6">
                    <label
                        for="exchangeProductSearch"
                        class="form-label small fw-semibold mb-1">
                        Tìm sản phẩm
                    </label>

                    <div class="position-relative">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>

                            <input
                                id="exchangeProductSearch"
                                type="text"
                                class="form-control"
                                placeholder="Tìm sản phẩm"
                                autocomplete="off">
                        </div>

                        <div
                            id="exchangeProductPopup"
                            class="exchange-search-popup">
                            <div
                                id="exchangeProductList"
                                class="list-group list-group-flush"></div>
                        </div>
                    </div>
                </div>


                <div class="col-md-6">
                    <label
                        for="exchangeBarcodeInput"
                        class="form-label small fw-semibold mb-1">
                        Quét barcode / nhập IMEI
                    </label>

                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i class="fa-solid fa-barcode"></i>
                        </span>

                        <input
                            id="exchangeBarcodeInput"
                            type="text"
                            class="form-control"
                            placeholder="Barcode hoặc IMEI"
                            autocomplete="off">

                        <button
                            type="button"
                            id="exchangeBarcodeAddBtn"
                            class="btn btn-primary">
                            Thêm
                        </button>
                    </div>

                    <div
                        id="exchangeBarcodeFeedback"
                        class="small text-muted mt-1">
                    </div>
                </div>

            </div>


            {{-- Danh sách hàng đổi mới --}}
            <div class="border-top pt-2">

                <div class="d-flex align-items-center justify-content-between mb-2">

                    <h6 class="mb-0">
                        Sản phẩm đổi mới
                    </h6>

                    <button
                        type="button"
                        id="clearExchangeCartBtn"
                        class="btn btn-outline-danger btn-sm">
                        Xóa
                    </button>
                </div>


                <div id="exchangeCartBody"></div>

                <div
                    id="exchangeCartEmpty"
                    class="text-center text-muted py-3 small">
                    Chưa có sản phẩm khách lấy mới.
                </div>


                <!-- <div class="border-top pt-2 mt-2">
                    <div class="d-flex justify-content-between small">

                        <span class="fw-semibold">
                            Giá trị hàng mới
                        </span>

                        <span
                            id="exchangeAmountPreview"
                            class="fw-bold">
                            0 VND
                        </span>

                    </div>
                </div> -->

            </div>

        </div>
    </div>
@endif
