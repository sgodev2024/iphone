@extends('Themes.layout_staff.app')

@section('content')
    <style>
        body {
            background: #f5f7fd;
        }

        .search-wrapper {
            position: relative;
        }

        .search-popup {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: #fff;
            border: 1px solid rgba(0, 0, 0, .1);
            border-radius: .25rem;
            margin-top: .25rem;
            max-height: 320px;
            overflow: auto;
            display: none;
            /* show via JS */
        }

        .product-row {
            cursor: pointer;
        }

        .product-row:hover {
            background: #f6f9ff;
        }

        .product-thumb {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: .5rem;
        }

        .product-thumb-placeholder {
            flex: 0 0 auto;
            background: #f1f5f9;
            border: 1px solid #e5e7eb;
            color: #64748b;
        }

        .sticky-summary {
            position: sticky;
            bottom: 0;
            background: #fff;
            border-top: 1px dashed #e5e7eb;
        }

        .table> :not(caption)>*>* {
            vertical-align: middle;
        }

        .badge-stock {
            font-weight: 500;
        }

        .barcode-feedback {
            min-height: 0;
        }

        .barcode-feedback:empty {
            display: none;
        }

        .barcode-feedback:not(:empty) {
            display: block;
            margin-top: .35rem !important;
        }

        .cart-empty {
            padding: 2rem;
            text-align: center;
            color: #6b7280;
        }

        .cart-row {
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }

        .cart-thumb {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 4px;
        }

        .cart-info {
            flex: 1;
        }

        .cart-actions {
            display: contents;
        }

        .cart-controls {
            display: grid;
            grid-template-columns: minmax(140px, 1fr) 80px 32px;
            align-items: end;
            gap: 8px;
            flex: 0 0 300px;
            min-width: 0;
        }

        .cart-field {
            display: flex;
            min-width: 0;
            flex-direction: column;
            gap: 4px;
        }

        .cart-field-label {
            color: #6b7280;
            font-size: .75rem;
            line-height: 1.2;
            white-space: nowrap;
        }

        .unit-price-input,
        .cart-controls .qty-input,
        .cart-controls .imei-quantity {
            height: 32px;
        }

        .unit-price-input {
            text-align: right;
        }

        .cart-controls .qty-input {
            width: 80px !important;
            text-align: center;
        }

        .cart-controls .imei-quantity {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
        }

        .cart-subtotal {
            min-width: 100px;
            text-align: right;
        }

        .add-customer-btn {
            font-size: 1.5rem;
            color: #0d6efd;
            cursor: pointer;
            transition: transform 0.2s ease, color 0.2s ease;
        }

        .add-customer-btn:hover {
            color: #0a58ca;
            /* xanh đậm hơn */
            transform: scale(1.2);
            /* hơi phóng to khi hover */
        }

        @media (max-width: 576px) {
            .product-thumb {
                width: 40px;
                height: 40px;
            }
        }

        .sale-product-add-stack {
            display: grid;
            gap: .625rem;
        }

        .sales-page .sale-product-search-heading,
        .sales-page .sale-barcode-heading,
        .sales-page .product-search-hint {
            display: none !important;
        }

        .sale-product-add-stack .search-wrapper,
        .sale-product-add-stack .input-group {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        .sale-product-add-stack .input-group {
            position: relative;
            flex-wrap: nowrap;
        }

        .sale-product-add-stack .input-group-text {
            position: absolute;
            top: 50%;
            left: .8rem;
            z-index: 4;
            width: auto;
            padding: 0;
            border: 0;
            background: transparent;
            color: #64748b;
            transform: translateY(-50%);
            pointer-events: none;
        }

        .sale-product-add-stack .input-group>.form-control {
            min-width: 0;
            border-radius: .45rem !important;
            padding-left: 2.35rem;
        }

        .sale-product-search-group #productSearch {
            padding-right: .75rem;
        }

        .sale-barcode-pane .input-group {
            align-items: stretch;
            gap: .5rem;
        }

        .sale-barcode-pane .input-group>.form-control {
            flex: 1 1 0;
            width: 1%;
        }

        .sale-barcode-add-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
            min-width: 72px;
            min-height: 38px;
            border-radius: .45rem !important;
            font-weight: 700;
        }

        @media (min-width: 768px) {
            .sales-page .sale-product-add-stack {
                grid-template-columns: repeat(2, minmax(0, 1fr));
                align-items: start;
                gap: 1rem;
            }

            .sales-page .sale-product-search-heading,
            .sales-page .sale-barcode-heading {
                display: flex !important;
            }

            .sales-page .product-search-hint {
                display: block !important;
            }

            .sales-page .sale-barcode-pane .input-group {
                gap: 0;
            }

            .sales-page .sale-barcode-pane .input-group>.form-control {
                width: 100%;
            }

            .sales-page .sale-barcode-add-btn {
                display: none !important;
            }
        }

        .sales-page select,
        .sales-page .form-select {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            text-overflow: ellipsis;
        }

        .sales-page select option {
            max-width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .sales-page .dropdown-menu {
            max-width: 100%;
            overflow-x: hidden;
        }

        .select2-hidden-accessible {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0 0 0 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }

        .select2-container {
            box-sizing: border-box;
            display: inline-block;
            position: relative;
            vertical-align: middle;
        }

        .select2-container .select2-selection--single {
            box-sizing: border-box;
            cursor: pointer;
            display: block;
            user-select: none;
        }

        .select2-container .select2-selection--single .select2-selection__rendered {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .select2-dropdown {
            position: absolute;
            left: -100000px;
            width: 100%;
            background: #fff;
            border: 1px solid #aaa;
            border-radius: .375rem;
            box-sizing: border-box;
            display: block;
        }

        .select2-container--open .select2-dropdown {
            left: 0;
        }

        .select2-results {
            display: block;
        }

        .select2-results__options {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .select2-results__option {
            cursor: pointer;
            user-select: none;
        }

        .select2-search--dropdown {
            display: none;
        }

        .select2-container--default .select2-results__option--highlighted[aria-selected] {
            background: #0d6efd;
            color: #fff;
        }

        .sale-select2-wrap {
            display: block;
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        .sale-discount-type-wrap {
            flex: 0 0 120px;
            max-width: 120px;
            min-width: 0;
        }

        .sales-page .select2-container {
            display: block;
            width: 100% !important;
            max-width: 100%;
            min-width: 0;
        }

        .sales-page .select2-container--default .select2-selection--single {
            height: 38px;
            border: 1px solid #ced4da;
            border-radius: .375rem;
            background-color: #fff;
        }

        .sales-page .select2-container--default .select2-selection--single .select2-selection__rendered {
            min-width: 0;
            padding-left: .75rem;
            padding-right: 2rem;
            color: #212529;
            font-size: .875rem;
            line-height: 36px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sales-page .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 36px;
            right: .45rem;
        }

        .sales-select2-dropdown {
            max-width: calc(100vw - 24px);
            border-color: #ced4da;
            overflow-x: hidden;
            box-sizing: border-box;
            z-index: 2055;
        }

        .sales-select2-dropdown .select2-results__options {
            max-width: 100%;
            overflow-x: hidden;
        }

        .sales-select2-dropdown .select2-results__option {
            max-width: 100%;
            padding: .45rem .65rem;
            font-size: .875rem;
            line-height: 1.25;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .sale-save-action {
            margin-top: .625rem;
            background: transparent;
            border: 0;
            box-shadow: none;
        }

        #saveOrderBtn {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            min-height: 42px;
            font-weight: 700;
        }

        /* Ẩn mũi tên tăng/giảm trong ô Khuyến mãi */
        #discountInput::-webkit-outer-spin-button,
        #discountInput::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        #discountInput {
            -moz-appearance: textfield;
        }

        @media (max-width: 767.98px) {
            body {
                overflow-x: hidden;
            }

            .sales-page {
                padding: 4.75rem .75rem .75rem !important;
                padding-bottom: calc(5.5rem + env(safe-area-inset-bottom, 0px)) !important;
            }

            .sales-page .card {
                margin-bottom: .75rem !important;
            }

            .sales-page .card-header {
                padding: .65rem .75rem;
            }

            .sales-page .card-body {
                padding: .75rem;
            }

            .sale-layout {
                --bs-gutter-x: 0;
                --bs-gutter-y: .75rem;
            }

            .sale-main,
            .sale-side,
            .sale-search-row>div,
            .sale-summary-row>div {
                width: 100%;
                max-width: 100%;
            }

            .sale-search-card .input-group,
            .sale-search-card .form-control,
            .sale-search-card .search-wrapper {
                width: 100%;
                min-width: 0;
            }

            .sale-search-card .d-flex {
                align-items: flex-start !important;
                gap: .25rem;
            }

            .sale-search-card .text-muted.small {
                display: none;
            }

            .form-text {
                display: none;
            }

            .barcode-feedback {
                margin-top: 0;
                font-size: .78rem;
                line-height: 1.3;
                min-height: 0;
            }

            .sale-search-card .input-group {
                position: relative;
            }

            .sale-search-card .input-group-text {
                position: absolute;
                top: 50%;
                left: .8rem;
                z-index: 4;
                width: auto;
                padding: 0;
                border: 0;
                background: transparent;
                color: #64748b;
                transform: translateY(-50%);
                pointer-events: none;
            }

            .sale-search-card .input-group>.form-control {
                border-radius: .45rem !important;
                padding-left: 2.35rem;
            }

            .sale-barcode-pane .input-group {
                align-items: stretch;
                gap: .5rem;
                flex-wrap: nowrap;
                margin-bottom: 0;
            }

            .sale-barcode-pane .input-group-text {
                top: 19px;
            }

            .sale-barcode-pane .form-control {
                flex: 1 1 auto;
                width: 1%;
                min-width: 0;
            }

            #barcodeFeedback {
                display: block;
                min-height: 0;
                margin-top: 0 !important;
            }

            #barcodeFeedback:empty {
                display: none;
            }

            #barcodeFeedback:not(:empty) {
                display: block;
                margin-top: .35rem !important;
            }

            @media (max-width: 359.98px) {
                .sale-barcode-pane .input-group {
                    flex-wrap: nowrap;
                }

                .sale-barcode-add-btn {
                    width: auto;
                }
            }

            .cart-empty {
                padding: 1rem .5rem;
            }

            .cart-row {
                display: grid;
                grid-template-columns: 44px minmax(0, 1fr) auto;
                align-items: center;
                column-gap: .5rem;
                row-gap: .25rem;
                padding: .65rem 0;
                flex-wrap: nowrap;
            }

            .cart-thumb {
                width: 44px;
                height: 44px;
                grid-column: 1;
                align-self: center;
            }

            .cart-info {
                min-width: 0;
                grid-column: 2;
                align-self: center;
                overflow-wrap: anywhere;
            }

            .cart-info .fw-semibold {
                line-height: 1.25;
                overflow-wrap: anywhere;
                word-break: break-word;
            }

            .cart-actions {
                grid-column: 3;
                grid-row: 2;
                align-self: center;
                width: auto;
                min-width: 0;
                justify-content: flex-end;
                padding-left: 0;
            }

            .cart-controls {
                grid-column: 2 / 4;
                grid-row: 2;
                grid-template-columns: minmax(0, 1fr) 60px 32px;
                align-items: end;
                min-width: 0;
                width: 100%;
                flex-basis: auto;
                gap: 6px;
            }

            .cart-controls .qty-input,
            .cart-controls .imei-quantity {
                width: 60px !important;
                max-width: 60px;
                min-width: 55px;
                padding-left: .25rem;
                padding-right: .25rem;
            }

            .cart-actions .remove-btn {
                flex: 0 0 32px;
                width: 32px;
                min-width: 32px;
                height: 32px;
                padding: 0;
                line-height: 1;
            }

            .sticky-summary {
                position: static;
                padding: .75rem !important;
                margin-top: .5rem;
            }

            .sale-summary-row {
                --bs-gutter-y: .75rem;
            }

            .sale-summary-spacer {
                display: none;
            }

            .sale-summary-totals .row {
                row-gap: .25rem;
            }

            #discountInput,
            #discountType {
                min-width: 0;
            }

            .sale-discount-field {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }

            .sale-discount-field>.form-label {
                display: block;
                width: calc(100% + 1.5rem);
                margin-left: -.75rem;
                margin-right: -.75rem;
            }

            .sale-discount-field .input-group {
                display: flex;
                width: calc(100% + 1.5rem);
                max-width: none;
                min-width: 0;
                margin-left: -.75rem;
                margin-right: -.75rem;
                flex-wrap: nowrap;
            }

            #discountInput {
                flex: 1 1 0;
                width: 100%;
                max-width: none;
            }

            #discountType {
                width: auto;
                max-width: none !important;
            }

            .sale-discount-type-wrap {
                flex: 0 0 auto;
                width: auto;
                max-width: none;
                min-width: 72px;
            }

            .sale-discount-type-wrap .select2-container {
                width: auto !important;
                max-width: none;
                min-width: 72px;
            }

            .sale-discount-help {
                display: block;
                width: calc(100% + 1.5rem);
                margin-top: .25rem;
                margin-left: -.75rem;
                margin-right: -.75rem;
            }

            .sale-customer-card .row {
                --bs-gutter-y: .65rem;
            }

            .sale-save-action {
                position: static;
                display: grid !important;
                justify-items: stretch;
                margin: .625rem 0 0;
                padding-top: 0;
                padding-right: calc(var(--bs-gutter-x) * .5);
                padding-bottom: 0;
                padding-left: calc(var(--bs-gutter-x) * .5);
                background: transparent;
                border: 0;
                box-shadow: none;
            }

            .sales-page~.custom-list-item {
                width: 100%;
                max-width: 100%;
                margin-left: 0 !important;
                margin-right: 0 !important;
            }

            .sales-page~.custom-list-item>[class*="col-"] {
                max-width: 100%;
                padding-left: 0;
                padding-right: 0;
            }

            #saveOrderBtn {
                width: 100%;
                max-width: 100%;
                min-width: 0;
                height: 42px;
                padding: .5rem 1rem;
                box-shadow: 0 8px 18px rgba(25, 135, 84, .28);
            }
        }
    </style>

    @php
        $configBank = $config?->bank;
        $bankName = $configBank?->name ?? 'Chưa cấu hình ngân hàng';
        $bankCode = $configBank?->code ?? '';
        $bankAccount = $config?->bank_account ?? 'Chưa cấu hình số tài khoản';
        $bankAccountForQr = $config?->bank_account ?? '';
        $receiver = $config?->receiver ?? 'Chưa cấu hình người nhận';
    @endphp

    <div class="container-fluid py-4 sales-page">
        @if (!$saleStorage && $saleStorageMessage)
            <div id="saleStorageMessage" class="alert alert-warning py-2 px-3 mb-3">
                {{ $saleStorageMessage }}
            </div>
        @endif

        <div class="row g-4 sale-layout">
            <!-- LEFT 9 cols -->
            <div class="col-lg-9 sale-main">
                <!-- Section: Sản phẩm + search -->
                <div class="card mb-4 sale-search-card">
                    <div class="card-body">
                        <div class="sale-product-add-stack">
                            {{-- Tìm kiếm sản phẩm --}}
                            <div class="sale-product-search-pane">
                                <div
                                    class="d-flex align-items-center justify-content-between mb-2 sale-product-search-heading">
                                    <label for="productSearch" class="form-label fw-semibold mb-0">Tìm &amp; chọn sản
                                        phẩm</label>
                                </div>

                                <div class="search-wrapper">
                                    <div class="input-group sale-product-search-group">
                                        <span class="input-group-text">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>

                                        <input id="productSearch" type="text" class="form-control"
                                            data-sale-storage-control @disabled(!$saleStorage)
                                            placeholder="Tìm sản phẩm" autocomplete="off" />
                                    </div>

                                    <div id="productPopup" class="search-popup">
                                        <div id="productList" class="list-group list-group-flush"></div>
                                    </div>
                                </div>

                                <div class="form-text product-search-hint">
                                    Gợi ý sẽ xuất hiện khi bạn nhập — bấm vào dòng sản phẩm để thêm vào giỏ.
                                </div>
                            </div>

                            {{-- Quét hoặc nhập barcode --}}
                            <div class="sale-barcode-pane">
                                <div class="d-flex align-items-center justify-content-between mb-2 sale-barcode-heading">
                                    <label for="barcodeInput" class="form-label fw-semibold mb-0">Quét hoặc nhập
                                        barcode</label>
                                    <span class="text-muted small">Nhấn Enter để thêm vào giỏ</span>
                                </div>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-barcode"></i>
                                    </span>

                                    <input id="barcodeInput" type="text" class="form-control" data-sale-storage-control
                                        @disabled(!$saleStorage) placeholder="Nhập hoặc quét barcode"
                                        autocomplete="off" />

                                    <button type="button" class="btn btn-primary sale-barcode-add-btn"
                                        data-sale-storage-control @disabled(!$saleStorage)>
                                        Thêm
                                    </button>
                                </div>

                                <div id="barcodeFeedback" class="barcode-feedback small text-muted mt-1"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section: Giỏ hàng / tính tiền -->
                <div class="card sale-cart-card">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Giỏ hàng</h5>
                            <button id="clearCartBtn" class="btn btn-outline-danger btn-sm">Xóa giỏ</button>
                        </div>
                    </div>
                    <div class="card-body">

                        <div id="cartBody" class="cart-body"></div>
                        <div id="cartEmptyRow" class="cart-empty text-muted p-3">
                            Chưa có sản phẩm nào. Tìm và chọn ở ô phía trên.
                        </div>

                        <!-- Summary -->
                        <div class="sticky-summary p-3 rounded-bottom">
                            <div class="row g-3 align-items-end sale-summary-row">
                                <div class="col-md-4 sale-discount-field">
                                    <label class="form-label mb-1">Khuyến mãi</label>
                                    <div class="input-group">
                                        <input id="discountInput" type="number" class="form-control" min="0"
                                            step="1000" placeholder="Số tiền hoặc %" />
                                        <span class="sale-select2-wrap sale-discount-type-wrap">
                                            <select id="discountType" class="form-select">
                                                <option value="amount">VND</option>
                                                <option value="percent">%</option>
                                            </select>
                                        </span>
                                    </div>
                                    <div class="form-text sale-discount-help">Để trống nếu không áp dụng.</div>
                                </div>
                                <div class="col-md-4 sale-summary-spacer">
                                </div>
                                <div class="col-md-4 sale-summary-totals">
                                    <div class="row">
                                        <!-- Cột text -->
                                        <div class="col-6 col-md-6 text-start">
                                            <div class="fw-medium">Tạm tính</div>
                                            <div class="fw-medium">Giảm giá</div>
                                            <div class="fw-bold">Tổng cuối</div>
                                        </div>
                                        <!-- Cột giá -->
                                        <div class="col-6 col-md-6 text-end">
                                            <div id="subtotal">0 VND</div>
                                            <div id="discountValue">-0 VND</div>
                                            <div id="grandTotal" class="fw-bold">0 VND</div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- RIGHT 3 cols -->
            <div class="col-lg-3 sale-side">

                <div class="card mb-4 sale-customer-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Khách hàng</h5>
                        <i class="fa-solid fa-circle-plus add-customer-btn" role="button" data-bs-toggle="modal"
                            data-bs-target="#addCustomerModal"></i>
                    </div>

                    <div class="card-body">

                        <div class="mb-2 position-relative border-bottom pb-3">
                            <input id="customerSearch" type="text" class="form-control" placeholder="Tìm khách hàng…"
                                autocomplete="off" />
                            <div id="customerPopup" class="search-popup" style="max-height: 240px;">
                                <div id="customerList" class="list-group list-group-flush"></div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Họ tên</label>
                                <input id="custName" type="text" class="form-control" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input id="custEmail" type="email" class="form-control" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">SĐT</label>
                                <input id="custPhone" type="tel" class="form-control" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Địa chỉ</label>
                                <textarea id="custAddress" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Phương thức thanh toán</label>
                                <span class="sale-select2-wrap">
                                    <select id="paymentMethod" class="form-select">
                                        <option value="cash">Tiền mặt</option>
                                        <option value="bank_transfer">Chuyển khoản</option>
                                        <option value="debt">Công nợ</option>
                                    </select>
                                </span>
                            </div>
                            <input type="hidden" id="custId">
                            <div class="col-12 d-grid sale-save-action">
                                <button class="btn btn-success" id="saveOrderBtn" data-sale-storage-control
                                    @disabled(!$saleStorage)>Lưu đơn</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card sale-note-card">
                    <div class="card-body">
                        <h6 class="mb-2">Ghi chú</h6>
                        <textarea id="orderNote" class="form-control" rows="3" placeholder="Nhập ghi chú cho đơn hàng…"></textarea>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal thêm khách hàng -->
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCustomerLabel">Thêm khách hàng mới</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <form id="addCustomerForm">
                        <div class="mb-3">
                            <label class="form-label">
                                Họ tên
                                <span class="text-danger fst-italic">(*) Không được để trống</span>
                            </label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">
                                Số điện thoại
                                <span class="text-danger fst-italic">(*) Không được để trống</span>
                            </label>
                            <input type="tel" class="form-control" name="phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <textarea class="form-control" name="address" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" form="addCustomerForm" class="btn btn-primary">Lưu</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="invoiceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Hóa Đơn Thanh Toán</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="invoice-container">

                        <!-- Header -->
                        <div class="text-center invoice-header mb-4">
                            <h2 class="fw-bold text-uppercase">HÓA ĐƠN THANH TOÁN</h2>
                            <h5 class="mt-2">Công ty: {{ $config?->user?->company_name ?? '' }} </h5>
                            <h5 class="mt-2">Cửa hàng: {{ $config?->user?->store_name ?? '' }} </h5>
                            <h5 class="mt-2">Địa chỉ: {{ $config?->user?->address ?? '' }} </h5>
                            <h5 class="mt-2">Điện thoại: {{ $config?->user?->phone ?? '' }} &emsp;&emsp;Email:
                                {{ $config?->user?->email ?? '' }}</h5>
                        </div>

                        <hr>

                        <!-- Customer Info -->
                        <div class="mb-4">
                            <div class="row">
                                <!-- Cột trái -->
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="fw-bolder">Ngày tạo:</span> {{ now()->format('d/m/Y') }}
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bolder">Tên khách:</span> <span id="client-name"></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bolder">SĐT:</span> <span id="client-phone"></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bolder">Email:</span> <span id="client-email"></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bolder">Địa chỉ:</span> <span id="client-address"></span>
                                    </div>
                                </div>

                                <!-- Cột phải -->
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="fw-bolder">Thu ngân: </span> {{ Auth::user()->name }}
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bolder">Thanh toán: </span> <span id="payment-method"></span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="fw-bolder">Ghi chú: </span><span id="invoice-note"></span>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- Product Table -->
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Sản phẩm</th>
                                    <th>Số lượng</th>
                                    <th>Đơn giá</th>
                                    <th>Thành tiền</th>
                                </tr>
                            </thead>
                            <tbody id="invoice-body">

                            </tbody>
                        </table>

                        <!-- Total -->
                        <div class="mt-3">
                            <div class="row mb-1">
                                <div class="col-6 text-start"><strong>Tạm tính:</strong></div>
                                <div class="col-6 text-end"><span id="invoice-subtotal">0 VND</span></div>
                            </div>
                            <div class="row mb-1">
                                <div class="col-6 text-start"><strong>Giảm giá:</strong></div>
                                <div class="col-6 text-end"><span id="invoice-discount">-0 VND</span></div>
                            </div>
                            <div class="row mb-1">
                                <div class="col-6 text-start fw-bold"><strong>Tổng tiền:</strong></div>
                                <div class="col-6 text-end fw-bold">
                                    <span id="invoice-total">0 VND</span>
                                </div>
                            </div>
                            <div class="row mb-1">
                                <div class="col-6 text-start"></div>
                                <div class="col-6 text-end">
                                    <em id="total-text">ba mươi triệu việt nam đồng</em>
                                </div>
                            </div>
                        </div>

                        <!-- QR Section -->
                        <div id="bank-transfer-info" class="qr-section text-center my-4 d-none">
                            <p>Cảm ơn quý khách!</p>
                            @if (!empty($missingConfigMessage))
                                <p class="text-warning mb-2">{{ $missingConfigMessage }}</p>
                            @endif
                            <img src="" alt="QR Code" width="180" id="qr-code">
                            <p class="mt-2 mb-0"><strong>{{ $bankName }}:</strong> {{ $bankAccount }}</p>
                            <p class="mb-0">{{ $receiver }}</p>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-dark" id="pay-button" data-sale-storage-control
                        @disabled(!$saleStorage)>Thanh toán</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script')
    <script src="{{ asset('assets/js/plugin/select2/select2.full.min.js') }}"></script>
    <script>
        $(function() {
            // --------- Helpers ---------
            const money = n => (parseFloat(n) || 0).toLocaleString('vi-VN');
            const qs = (s, el = document) => el.querySelector(s);
            const qsa = (s, el = document) => [...el.querySelectorAll(s)];
            const appBaseUrl = @json(rtrim(config('app.url'), '/'));
            const storageBaseUrl = `${appBaseUrl}/storage`;
            const defaultProductImageUrl = @json(productImage(null));
            const paymentMethodLabels = {
                cash: 'Tiền mặt',
                bank_transfer: 'Chuyển khoản',
                debt: 'Công nợ',
            };
            const bankCode = @json($bankCode);
            const bankAccount = @json($bankAccountForQr);
            let currentInvoiceGrand = 0;
            let orderSaved = false;
            let hasSaleStorage = @json((bool) $saleStorage);
            const saleStorageMessage = qs('#saleStorageMessage');

            function setSaleStorageControlsEnabled(enabled) {
                qsa('[data-sale-storage-control]').forEach((control) => {
                    control.disabled = !enabled;
                });
            }

            function saleStorageRequiredMessage() {
                return saleStorageMessage?.textContent.trim() || 'Vui lòng chọn kho bán hàng.';
            }

            function showSaleStorageRequired(message = saleStorageRequiredMessage()) {
                const feedback = qs('#barcodeFeedback');

                if (feedback) {
                    feedback.textContent = message;
                    feedback.classList.remove('text-muted');
                    feedback.classList.add('text-warning');
                }

                Toast.fire({
                    icon: 'warning',
                    title: message
                });
            }

            function ensureSaleStorageReady() {
                if (hasSaleStorage) {
                    return true;
                }

                showSaleStorageRequired();
                return false;
            }

            setSaleStorageControlsEnabled(hasSaleStorage);

            function sizeOpenSalesSelect2Dropdown(selectElement) {
                const select2Container = $(selectElement).next('.select2');
                const dropdown = $('.select2-container--open .sales-select2-dropdown');
                const width = Math.ceil(select2Container.outerWidth());

                if (!width || dropdown.length === 0) {
                    return;
                }

                dropdown.css({
                    width: `${width}px`,
                    minWidth: `${width}px`,
                    maxWidth: `${width}px`
                });
            }

            function initSalesSelect2() {
                if (!$.fn.select2) {
                    return;
                }

                $('#paymentMethod, #discountType').each(function() {
                    const selectElement = this;
                    const select = $(selectElement);

                    if (select.data('select2')) {
                        return;
                    }

                    select.select2({
                        width: '100%',
                        dropdownAutoWidth: false,
                        dropdownParent: $(document.body),
                        dropdownCssClass: 'sales-select2-dropdown',
                        minimumResultsForSearch: Infinity
                    });

                    select.on('select2:open', function() {
                        requestAnimationFrame(() => sizeOpenSalesSelect2Dropdown(selectElement));
                    });

                    select.on('select2:select select2:clear', function() {
                        selectElement.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    });
                });
            }

            initSalesSelect2();

            function escapeHtml(value) {
                return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                } [char]));
            }

            function safeText(value, fallback = '') {
                const text = String(value ?? '').trim();

                return text === '' ? fallback : text;
            }

            function selectedPaymentMethod() {
                return qs('#paymentMethod')?.value || '';
            }

            function resetBankTransferInfo() {
                $('#bank-transfer-info').addClass('d-none');
                $('#qr-code').attr('src', '').attr('alt', 'QR Code');
            }

            function renderInvoicePayment(paymentMethod, grand) {
                $('#payment-method').text(paymentMethodLabels[paymentMethod] || 'Không xác định');
                resetBankTransferInfo();

                if (paymentMethod !== 'bank_transfer') {
                    return;
                }

                $('#bank-transfer-info').removeClass('d-none');

                if (bankCode && bankAccount) {
                    $('#qr-code')
                        .attr('src',
                            `https://img.vietqr.io/image/${bankCode}-${bankAccount}-compact.png?amount=${grand}&addInfo=ThanhToanDonHang`
                        )
                        .attr('alt', 'QR Code chuyển khoản');
                } else {
                    $('#qr-code').attr('alt', 'Chưa cấu hình QR chuyển khoản');
                }
            }

            function renderSavedInvoice(order) {
                const rows = (order.items || []).map((item, index) => {
                    const imei = item.imei ?
                        `<div class="small text-muted">IMEI: ${escapeHtml(item.imei)}</div>` : '';

                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td class="text-start">${escapeHtml(item.name || '')}${imei}</td>
                            <td>${Number(item.quantity || 0)}</td>
                            <td>${money(item.unit_price)}</td>
                            <td>${money(item.line_total)}</td>
                        </tr>`;
                }).join('');

                currentInvoiceGrand = Number(order.total || 0);
                $('#invoice-body').html(rows);
                $('#invoice-subtotal').html(money(order.subtotal) + ' VND');
                $('#invoice-discount').html('-' + money(order.discount) + ' VND');
                $('#invoice-total').html(money(order.total) + ' VND');
                $('#total-text').html(convertNumberToWords(order.total));
                $('#invoice-note').text(order.note || '');
                renderInvoicePayment(order.payment_method, currentInvoiceGrand);
            }

            function productThumbHtml(product, className = 'product-thumb') {
                const name = escapeHtml(safeText(product?.name, 'Sản phẩm'));
                const thumbnailUrl = safeText(product?.thumbnail_url);
                const thumbnail = safeText(product?.thumbnail);
                const imageUrl = thumbnailUrl || (thumbnail ? `${storageBaseUrl}/${encodeURI(thumbnail)}` :
                    defaultProductImageUrl);

                return `<img class="${className}" src="${imageUrl}" alt="${name}" onerror="this.onerror=null;this.src='${defaultProductImageUrl}'" />`;
            }

            function focusBarcodeInput() {
                setTimeout(() => barcodeInput?.focus(), 0);
            }

            function debounce(fn, delay = 500) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            function convertNumberToWords(num) {
                const units = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
                const teens = ["mười", "mười một", "mười hai", "mười ba", "mười bốn", "mười lăm", "mười sáu",
                    "mười bảy",
                    "mười tám", "mười chín"
                ];
                const tens = ["", "", "hai mươi", "ba mươi", "bốn mươi", "năm mươi", "sáu mươi", "bảy mươi",
                    "tám mươi",
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

            // --------- Product Search ---------
            const productSearch = qs('#productSearch');
            const productPopup = qs('#productPopup');
            const productList = qs('#productList');
            const barcodeInput = qs('#barcodeInput');
            const barcodeFeedback = qs('#barcodeFeedback');
            const barcodeAddBtn = qs('.sale-barcode-add-btn');
            let isCallApiProducts = true;
            let isCallApiClients = true;
            let barcodeResolving = false;
            let lastBarcode = '';
            let lastBarcodeAt = 0;

            let searchTimer = null;

            setTimeout(() => barcodeInput?.focus(), 100);

            barcodeInput?.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') return;

                event.preventDefault();
                resolveBarcode(barcodeInput.value.trim());
            });

            barcodeAddBtn?.addEventListener('click', () => {
                resolveBarcode(barcodeInput.value.trim());
            });

            function resolveBarcode(barcode) {
                if (!ensureSaleStorageReady()) return;
                if (!barcode || barcodeResolving) return;

                const now = Date.now();
                if (barcode === lastBarcode && now - lastBarcodeAt < 800) {
                    barcodeInput.value = '';
                    barcodeInput.focus();
                    return;
                }

                barcodeResolving = true;
                lastBarcode = barcode;
                lastBarcodeAt = now;
                barcodeFeedback.classList.remove('text-warning', 'text-danger');
                barcodeFeedback.classList.add('text-muted');
                barcodeFeedback.textContent = 'Đang xử lý barcode...';

                $.ajax({
                    url: '/ban-hang/barcode/resolve',
                    method: 'POST',
                    data: {
                        barcode,
                        cart_imei_ids: currentCartImeiIds(),
                        cart_product_quantities: currentCartProductQuantities(),
                    },
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: (product) => {
                        if (!addToCart(product)) {
                            barcodeFeedback.classList.remove('text-muted', 'text-warning');
                            barcodeFeedback.classList.add('text-danger');
                            barcodeFeedback.textContent =
                                'Không thể thêm sản phẩm vào giỏ hàng. Vui lòng thử lại.';
                            return;
                        }

                        productPopup.style.display = 'none';
                        barcodeFeedback.classList.remove('text-warning', 'text-danger');
                        barcodeFeedback.classList.add('text-muted');
                        barcodeFeedback.textContent = 'Đã thêm vào giỏ.';
                        Toast.fire({
                            icon: "success",
                            title: "Đã thêm barcode vào giỏ."
                        });
                    },
                    error: (xhr) => {
                        const message = xhr.responseJSON?.message || 'Không thể xử lý barcode.';
                        barcodeFeedback.classList.remove('text-muted', 'text-warning');
                        barcodeFeedback.classList.add('text-danger');
                        barcodeFeedback.textContent = message;
                        Toast.fire({
                            icon: "error",
                            title: message
                        });
                    },
                    complete: () => {
                        barcodeResolving = false;
                        barcodeInput.value = '';
                        barcodeInput.focus();
                    }
                });
            }

            function currentCartImeiIds() {
                return [...cart.values()]
                    .filter(({
                        product
                    }) => product.tracking_type === 'imei')
                    .map(({
                        product
                    }) => product.product_imei_id);
            }

            function currentCartProductQuantities() {
                const quantities = {};

                for (const {
                        product,
                        qty
                    }
                    of cart.values()) {
                    if (product.tracking_type === 'quantity') {
                        quantities[product.product_id] = qty;
                    }
                }

                return quantities;
            }

            productSearch.addEventListener('input', debounce((e) => {
                if (!ensureSaleStorageReady()) return;

                const keyword = e.target.value.trim();

                productPopup.style.display = 'block';
                fetchProducts(keyword);
            }, 300));

            productSearch.addEventListener('focus', async () => {
                if (!ensureSaleStorageReady()) return;

                if (productList.children.length === 0 || isCallApiProducts) {
                    await fetchProducts('');
                }

                productPopup.style.display = 'block';
            });

            document.addEventListener('click', (e) => {
                if (!productPopup.contains(e.target) && e.target !== productSearch) {
                    productPopup.style.display = 'none';
                }
            });

            const productResultState = new Map();

            productList.addEventListener('click', (event) => {
                const row = event.target.closest('button.product-row[data-product-result-key]');

                if (!row || !productList.contains(row)) {
                    return;
                }

                const product = productResultState.get(row.dataset.productResultKey);

                if (product) {
                    handleProductResultSelection(product);
                }
            });

            function normalizeProductResponse(response) {
                if (Array.isArray(response)) {
                    return response;
                }

                if (Array.isArray(response?.data)) {
                    return response.data;
                }

                return [];
            }

            function handleProductResultSelection(product) {
                const trackingType = product.tracking_type || product.inventory_tracking || 'quantity';
                const isImeiDevice = trackingType === 'imei' || product.result_type === 'imei_device';
                const isImeiProduct = trackingType === 'imei_product';
                const availableQuantity = Number(product.available_quantity ?? product.quantity ?? 0);

                if (isImeiDevice) {
                    verifyAndAddImeiDevice(product);
                    return;
                }

                if (isImeiProduct) {
                    Toast.fire({
                        icon: "info",
                        title: "Sản phẩm này quản lý theo IMEI. Hãy nhập IMEI của thiết bị hoặc quét Barcode."
                    });
                    productPopup.style.display = 'none';
                    productSearch.value = '';
                    focusBarcodeInput();
                    return;
                }

                if (availableQuantity <= 0) {
                    Toast.fire({
                        icon: "error",
                        title: "Số lượng tồn kho không đủ!"
                    });
                    return;
                }

                if (addToCart({
                        ...product,
                        tracking_type: 'quantity',
                        quantity: availableQuantity,
                        available_quantity: availableQuantity
                    })) {
                    productPopup.style.display = 'none';
                    productSearch.value = '';
                }
            }

            function verifyAndAddImeiDevice(product) {
                const productId = Number(product?.product_id || product?.id || 0);
                const productImeiId = Number(product?.product_imei_id || 0);
                const storageId = Number(product?.storage_id || 0);
                const imei = safeText(product?.imei);

                if (!productId || !productImeiId || !storageId || !imei) {
                    Toast.fire({
                        icon: "error",
                        title: "Dữ liệu IMEI không hợp lệ. Vui lòng tìm hoặc quét lại thiết bị."
                    });
                    return false;
                }

                const added = addToCart({
                    ...product,
                    id: productId,
                    product_id: productId,
                    product_imei_id: productImeiId,
                    storage_id: storageId,
                    tracking_type: 'imei',
                    type: 'imei',
                    imei,
                    quantity: 1,
                    available_quantity: 1
                });

                if (added) {
                    productPopup.style.display = 'none';
                    productSearch.value = '';
                    Toast.fire({
                        icon: "success",
                        title: "Đã thêm thiết bị IMEI vào giỏ."
                    });
                }

                return added;
            }

            function renderProductResults(response) {
                const products = normalizeProductResponse(response).filter(Boolean);
                productList.innerHTML = '';
                productResultState.clear();

                if (products.length === 0) {
                    productList.innerHTML =
                        '<div class="list-group-item text-center text-muted">Không tìm thấy sản phẩm</div>';
                    return;
                }

                products.forEach((product, index) => {
                    const p = product || {};
                    const resultKey = String(index);
                    const trackingType = p.tracking_type || p.inventory_tracking || 'quantity';
                    const isImeiDevice = trackingType === 'imei' || p.result_type === 'imei_device';
                    const isImeiProduct = trackingType === 'imei_product';
                    const availableQuantity = Number(p.available_quantity ?? p.quantity ?? 0);
                    const productName = safeText(p.name, 'Sản phẩm chưa có tên');
                    const productCode = safeText(p.code);
                    const productBarcode = safeText(p.barcode);
                    const productImei = safeText(p.imei);
                    const badgeText = isImeiProduct ? 'Quản lý theo IMEI' : 'Sản phẩm thường';
                    const stockText = isImeiProduct ?
                        `Thiết bị còn tồn: ${availableQuantity}` :
                        `Tồn: ${availableQuantity}`;
                    const badgeClass = isImeiProduct ? 'bg-warning text-dark' : 'bg-light text-dark border';

                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'list-group-item list-group-item-action product-row';
                    row.dataset.productResultKey = resultKey;
                    row.innerHTML = `
                    <div class="d-flex align-items-center gap-3">
                        ${productThumbHtml(p)}
                        <div class="flex-grow-1">
                        <div class="fw-semibold">${escapeHtml(productName)}</div>
                        <div class="small text-muted">${money(p.price)} VND</div>
                        ${isImeiDevice ? `<div class="small text-muted">IMEI: ${escapeHtml(productImei)}</div>` : ''}
                        <div class="small text-muted">
                            ${productCode ? `Mã: ${escapeHtml(productCode)}` : ''}
                            ${productCode && productBarcode ? ' · ' : ''}
                            ${productBarcode ? `Barcode: ${escapeHtml(productBarcode)}` : ''}
                        </div>
                        </div>
                        <div class="text-end d-flex flex-column align-items-end gap-1">
                        <span class="badge ${isImeiDevice ? 'bg-info text-dark' : badgeClass}">${isImeiDevice ? 'Thiết bị IMEI' : badgeText}</span>
                        <span class="badge border badge-stock text-dark">${isImeiDevice ? 'Số lượng: 1' : stockText}</span>
                        </div>
                    </div>`;

                    productResultState.set(resultKey, p);
                    productList.appendChild(row);
                });
            }

            // --------- Cart Logic ---------
            const cart = new Map(); // key: quantity:{productId} hoặc imei:{productImeiId}
            const cartBody = qs('#cartBody');
            const cartEmptyRow = qs('#cartEmptyRow');

            function addToCart(product) {
                const trackingType = product.tracking_type || product.type || 'quantity';

                if (trackingType === 'imei') {
                    const productImeiId = Number(product.product_imei_id || 0);

                    if (!productImeiId) {
                        Toast.fire({
                            icon: "error",
                            title: "Thiết bị IMEI không hợp lệ."
                        });
                        return false;
                    }

                    const key = `imei:${productImeiId}`;

                    if (cart.has(key)) {
                        Toast.fire({
                            icon: "error",
                            title: "Thiết bị đã có trong giỏ."
                        });
                        return false;
                    }

                    cart.set(key, {
                        product: normalizeCartProduct({
                            ...product,
                            product_imei_id: productImeiId
                        }, 'imei'),
                        qty: 1
                    });
                    renderCart();
                    return true;
                }

                const normalized = normalizeCartProduct(product, 'quantity');
                const key = `quantity:${normalized.product_id}`;
                const item = cart.get(key) || {
                    product: normalized,
                    qty: 0
                };
                const maxQty = Number(normalized.available_quantity || normalized.quantity || 0);

                if (item.qty + 1 > maxQty) {
                    Toast.fire({
                        icon: "error",
                        title: "Số lượng yêu cầu vượt tồn kho."
                    });
                    return false;
                }

                item.qty += 1;
                cart.set(key, item);
                renderCart();
                return true;
            }

            function normalizeCartProduct(product, trackingType) {
                const productId = Number(product.product_id || product.id);
                const unitPrice = Number(product.unit_price ?? product.price ?? 0);

                return {
                    ...product,
                    id: productId,
                    product_id: productId,
                    tracking_type: trackingType,
                    quantity: Number(product.available_quantity || product.quantity || 1),
                    available_quantity: Number(product.available_quantity || product.quantity || 1),
                    price_buy: Number(product.price_buy || 0),
                    price: unitPrice,
                    unit_price: unitPrice,
                };
            }

            function removeFromCart(key) {
                cart.delete(key);
                renderCart();
            }

            function updateQty(key, qty) {

                const item = cart.get(key);
                if (!item) return;
                if (item.product.tracking_type === 'imei') return;

                const q = Math.max(1, Math.min(Number(qty) || 1, item.product.available_quantity));
                item.qty = q;
                cart.set(key, item);
                renderCartTotals();
            }

            function parseMoneyInput(value) {
                const digits = String(value ?? '').replace(/[^0-9]/g, '');

                if (digits === '') {
                    return 0;
                }

                const parsed = Number(digits);
                return Number.isSafeInteger(parsed) && parsed >= 0 ? parsed : null;
            }

            function updateUnitPrice(key, input) {
                const item = cart.get(key);
                if (!item) return;

                const unitPrice = parseMoneyInput(input.value);
                if (unitPrice === null) {
                    input.setCustomValidity('Giá bán không hợp lệ.');
                    return;
                }

                input.setCustomValidity('');
                item.product.unit_price = unitPrice;
                item.product.price = unitPrice;
                cart.set(key, item);
                input.value = money(unitPrice);
                renderCartTotals();
            }

            function renderCart() {
                cartBody.innerHTML = '';
                if (cart.size === 0) {
                    cartEmptyRow.style.display = '';
                } else {
                    cartEmptyRow.style.display = 'none';

                    for (const [key, {
                            product,
                            qty
                        }] of cart.entries()) {

                        const isImei = product.tracking_type === 'imei';
                        const productName = escapeHtml(safeText(product.name, 'Sản phẩm chưa có tên'));
                        const productBarcode = escapeHtml(safeText(product.barcode));
                        const stockText = isImei ? `IMEI: ${escapeHtml(safeText(product.imei))}` :
                            `Tồn kho: ${Number(product.available_quantity || 0)}`;
                        const quantityControl = isImei ?
                            '<span class="badge bg-secondary imei-quantity">1</span>' :
                            `<input type="number" min="1" max="${product.available_quantity}" value="${qty}"
                                class="form-control form-control-sm text-center qty-input" />`;

                        const row = document.createElement('div');
                        row.className = 'cart-row';
                        row.dataset.rowId = key;
                        row.innerHTML = `
                        ${productThumbHtml(product, 'cart-thumb')}
                        <div class="cart-info">
                        <div class="fw-semibold">${productName}</div>
                        <div class="small text-muted">${stockText}</div>
                        ${isImei && productBarcode ? `<div class="small text-muted">Barcode: ${productBarcode}</div>` : ''}
                        </div>
                        <div class="cart-controls">
                            <div class="cart-field">
                            <label class="cart-field-label">Giá bán</label>
                            <input type="text" inputmode="numeric" autocomplete="off"
                                class="form-control form-control-sm unit-price-input"
                                value="${money(product.unit_price)}" aria-label="Giá bán ${productName}" />
                            </div>
                            <div class="cart-field cart-quantity-field">
                            <label class="cart-field-label">Số lượng</label>
                            ${quantityControl}
                            </div>
                            <div class="cart-actions">
                            <button class="btn btn-sm btn-outline-danger remove-btn" aria-label="Xóa ${productName}">&times;</button>
                            </div>
                    </div>
                    `;
                        const qtyInput = qs('.qty-input', row);
                        if (qtyInput) {
                            qtyInput.addEventListener('input', (e) => updateQty(key, e.target.value));
                        }
                        const unitPriceInput = qs('.unit-price-input', row);
                        unitPriceInput.addEventListener('input', () => updateUnitPrice(
                            key,
                            unitPriceInput
                        ));
                        qs('.remove-btn', row).addEventListener('click', () => removeFromCart(key));
                        cartBody.appendChild(row);
                    }
                }
                renderCartTotals();
            }


            // Totals
            const discountInput = qs('#discountInput');
            const discountType = qs('#discountType');
            const subtotalEl = qs('#subtotal');
            const discountValueEl = qs('#discountValue');
            const grandTotalEl = qs('#grandTotal');

            discountInput.addEventListener('input', renderCartTotals);
            discountType.addEventListener('change', renderCartTotals);

            function calcSubtotal() {
                let sum = 0;
                for (const {
                        product,
                        qty
                    }
                    of cart.values()) {

                    sum += product.unit_price * qty;
                }
                return sum;
            }

            function renderCartTotals() {
                const sub = calcSubtotal();

                let discount = Number(discountInput.value) || 0;
                if (discountType.value === 'percent') {
                    discount = Math.min(100, Math.max(0, discount));
                    discount = Math.round(sub * discount / 100);
                } else {
                    discount = Math.min(discount, sub);
                }
                const grand = Math.max(0, sub - discount);
                subtotalEl.textContent = money(sub) + ' VND';
                discountValueEl.textContent = '-' + money(discount) + ' VND';
                grandTotalEl.textContent = money(grand) + ' VND';
            }

            // Clear cart
            qs('#clearCartBtn').addEventListener('click', () => {
                cart.clear();
                renderCart();
            });

            // --------- Customer search & autofill ---------
            const customerSearch = qs('#customerSearch');
            const customerPopup = qs('#customerPopup');
            const customerList = qs('#customerList');

            customerSearch.addEventListener('input', debounce((e) => {
                fetchClients(e.target.value.trim());
            }, 300));

            customerSearch.addEventListener('focus', () => {
                isCallApiClients && fetchClients();
                customerPopup.style.display = 'block';
            });

            document.addEventListener('click', (e) => {
                if (!customerPopup.contains(e.target) && e.target !== customerSearch) {
                    customerPopup.style.display = 'none';
                }
            });

            function renderCustomerResults(clients) {

                customerList.innerHTML = '';

                if (clients.length === 0) {
                    customerList.innerHTML =
                        '<div class="list-group-item text-center text-muted">Không tìm thấy khách hàng</div>';
                    return;
                }

                clients.forEach(c => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action';
                    item.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <div>
                        <div class="fw-semibold">${c.name}</div>
                        <div class="small text-muted">${c.email}</div>
                        </div>
                        <div class="text-nowrap small">${c.phone}</div>
                    </div>`;
                    item.addEventListener('click', () => fillCustomer(c));
                    customerList.appendChild(item);
                });
            }

            function fillCustomer(c) {
                qs('#custName').value = c.name;
                qs('#custEmail').value = c.email;
                qs('#custPhone').value = c.phone;
                qs('#custAddress').value = c.address;
                qs('#custId').value = c.id;
                customerPopup.style.display = 'none';
                customerSearch.value = '';

                $('#client-name').text(c.name);
                $('#client-email').text(c.email);
                $('#client-phone').text(c.phone);
                $('#client-address').text(c.address);
            }

            qs('#saveOrderBtn').addEventListener('click', () => {
                if (cart.size === 0) {
                    Toast.fire({
                        icon: "error",
                        title: "Giỏ hàng đang trống! Vui lòng thêm ít nhất 1 sản phẩm!"
                    });
                    return;
                }

                let _html = '';
                const sub = calcSubtotal();

                Array.from(cart.entries()).forEach(([id, {
                    product,
                    qty
                }], index) => {
                    const itemName = product.tracking_type === 'imei' ?
                        `${product.name}<div class="small text-muted">IMEI: ${product.imei}</div>` :
                        product.name;

                    _html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td class="text-start">${itemName}</td>
                            <td>${qty}</td>
                            <td> ${ money(product.unit_price) }</td>
                            <td>${ money(product.unit_price * qty) }</td>
                        </tr>`;
                })

                let discount = Number(discountInput.value) || 0;
                if (discountType.value === 'percent') {
                    discount = Math.min(100, Math.max(0, discount));
                    discount = Math.round(sub * discount / 100);
                } else {
                    discount = Math.min(discount, sub);
                }
                const grand = Math.max(0, sub - discount);
                const paymentMethod = selectedPaymentMethod();
                currentInvoiceGrand = grand;

                $('#invoice-subtotal').html(money(sub) + ' VND');
                $('#invoice-discount').html('-' + money(discount) + ' VND');
                $('#invoice-total').html(money(grand) + ' VND');
                $('#total-text').html(convertNumberToWords(grand));
                $('#invoice-note').html($('#orderNote').val());
                $('#invoice-body').html(_html);

                const name = qs('#custName').value.trim();
                const phone = qs('#custPhone').value.trim();
                const email = qs('#custEmail').value.trim();

                // Tạo dữ liệu order
                const order = {
                    items: [...cart.values()].map(({
                        product,
                        qty
                    }) => ({
                        id: product.id,
                        product_id: product.product_id,
                        tracking_type: product.tracking_type,
                        product_imei_id: product.product_imei_id || null,
                        imei: product.imei || null,
                        barcode: product.barcode || null,
                        name: product.name,
                        unit_price: product.unit_price,
                        qty,
                        quantity: qty
                    })),
                    subtotal: calcSubtotal(),
                    discountType: discountType.value,
                    discountInput: Number(discountInput.value) || 0,
                    grand: (function() {
                        const sub = calcSubtotal();
                        let d = Number(discountInput.value) || 0;
                        if (discountType.value === 'percent') {
                            d = Math.round(sub * Math.min(100, Math.max(0, d)) / 100);
                        } else {
                            d = Math.min(d, sub);
                        }
                        return sub - d;
                    })(),
                    customer: {
                        id: qs('#custId').value || null,
                        name,
                        email: qs('#custEmail').value.trim(),
                        phone,
                        address: qs('#custAddress').value.trim(),
                        payment: paymentMethod,
                        note: qs('#orderNote')?.value || ''
                    }
                };

                renderInvoicePayment(paymentMethod, order.grand);

                $('#invoiceModal').modal('show')
            });

            function fetchProducts(search = '') {
                if (!ensureSaleStorageReady()) {
                    productPopup.style.display = 'block';
                    productList.innerHTML = `
        <div class="list-group-item text-center text-warning">
            ${escapeHtml(saleStorageRequiredMessage())}
        </div>
    `;
                    return Promise.resolve([]);
                }

                productList.innerHTML = `
        <div class="list-group-item text-center text-muted">
            Đang tìm kiếm...
        </div>
    `;

                productPopup.style.display = 'block';

                return $.ajax({
                    url: "{{ url('/ban-hang/product') }}",
                    method: 'GET',
                    data: {
                        search: search
                    },
                    success: function(res) {
                        renderProductResults(res);
                        productPopup.style.display = 'block';
                        isCallApiProducts = false;
                    },
                    error: function(xhr) {
                        console.error('Lỗi tìm kiếm sản phẩm:', xhr.responseText);

                        productList.innerHTML = `
                <div class="list-group-item text-center text-danger">
                    Không thể tải danh sách sản phẩm
                </div>
            `;

                        Toast.fire({
                            icon: 'error',
                            title: xhr.responseJSON?.message ||
                                'Không thể tìm kiếm sản phẩm.'
                        });
                    }
                });
            }

            const fetchClients = (searchText) => {
                $.ajax({
                    url: '/ban-hang/get-clients',
                    method: 'GET',
                    data: {
                        searchText
                    },
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: (res) => {

                        isCallApiClients = false
                        renderCustomerResults(res)
                    },
                    error: (xhr) => {
                        Toast.fire({
                            icon: "error",
                            title: xhr.responseJSON.message ||
                                'Đã có lỗi xảy ra. Vui lòng thử lại sau!'
                        });
                    }
                });
            }

            $('#addCustomerForm').on('submit', function(e) {
                e.preventDefault();

                const formData = $(this).serializeArray();

                $.ajax({
                    url: '/ban-hang/clients/add',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: (res) => {

                        const {
                            data,
                            message
                        } = res

                        $('#custName').val(data.name)
                        $('#custEmail').val(data.email)
                        $('#custPhone').val(data.phone)
                        $('#custAddress').val(data.address)
                        $('#custId').val(data.id)

                        $('#addCustomerModal').modal('hide');
                        $('#addCustomerForm')[0].reset();

                    },
                    error: (xhr) => {
                        Toast.fire({
                            icon: "error",
                            title: xhr.responseJSON.message ||
                                'Đã có lỗi xảy ra. Vui lòng thử lại sau!'
                        });
                    }
                })
            })

            $('#paymentMethod').on('change', function() {
                if ($('#invoiceModal').hasClass('show')) {
                    renderInvoicePayment(this.value, currentInvoiceGrand);
                }
            });

            $('#invoiceModal').on('hidden.bs.modal', function() {
                resetBankTransferInfo();
                if (orderSaved) {
                    window.location.reload();
                }
            });

            $('#pay-button').on('click', function() {
                if (orderSaved) return;

                const payButton = $(this);
                payButton.prop('disabled', true);
                const paymentMethod = selectedPaymentMethod();
                const order = {
                    items: [...cart.values()].map(({
                        product,
                        qty
                    }) => ({
                        id: product.id,
                        product_id: product.product_id,
                        tracking_type: product.tracking_type,
                        product_imei_id: product.product_imei_id || null,
                        imei: product.imei || null,
                        barcode: product.barcode || null,
                        name: product.name,
                        unit_price: product.unit_price,
                        qty,
                        quantity: qty
                    })),
                    subtotal: calcSubtotal(),
                    discountType: discountType.value,
                    discountInput: Number(discountInput.value) || 0,
                    grand: (function() {
                        const sub = calcSubtotal();
                        let d = Number(discountInput.value) || 0;
                        if (discountType.value === 'percent') {
                            d = Math.round(sub * Math.min(100, Math.max(0, d)) / 100);
                        } else {
                            d = Math.min(d, sub);
                        }
                        return sub - d;
                    })(),
                    customer: {
                        id: qs('#custId').value || null,
                        name: qs('#custName').value.trim(),
                        email: qs('#custEmail').value.trim(),
                        phone: qs('#custPhone').value.trim(),
                        address: qs('#custAddress').value.trim(),
                        payment: paymentMethod,
                        note: qs('#orderNote')?.value || ''
                    },
                };

                $.ajax({
                    url: '/ban-hang/order',
                    method: 'POST',
                    data: order,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    success: (res) => {
                        orderSaved = true;
                        renderSavedInvoice(res.order);
                        payButton.text('Đã lưu đơn');
                        Toast.fire({
                            icon: "success",
                            title: res.message
                        });

                    },
                    error: (xhr) => {
                        payButton.prop('disabled', false);
                        Toast.fire({
                            icon: "error",
                            title: xhr.responseJSON.message ||
                                'Đã có lỗi xảy ra. Vui lòng thử lại sau!'
                        });
                    }
                })
            })
        })
    </script>
@endpush
