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
            min-height: 20px;
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
            display: flex;
            align-items: center;
            gap: 8px;
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
    </style>

    @php
        $configBank = $config?->bank;
        $bankName = $configBank?->name ?? 'Chưa cấu hình ngân hàng';
        $bankCode = $configBank?->code ?? '';
        $bankAccount = $config?->bank_account ?? 'Chưa cấu hình số tài khoản';
        $bankAccountForQr = $config?->bank_account ?? '';
        $receiver = $config?->receiver ?? 'Chưa cấu hình người nhận';
    @endphp

    <div class="container-fluid py-4">
        <div class="row g-4">
            <!-- LEFT 9 cols -->
            <div class="col-lg-9">
                <!-- Section: Sản phẩm + search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row g-3">
                            {{-- Tìm kiếm sản phẩm --}}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label for="productSearch" class="form-label fw-semibold mb-0">
                                        Tìm & chọn sản phẩm
                                    </label>

                                </div>

                                <div class="search-wrapper">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                        </span>

                                        <input id="productSearch" type="text" class="form-control"
                                            placeholder="Tìm kiếm sản phẩm..." autocomplete="off" />
                                    </div>

                                    <div id="productPopup" class="search-popup">
                                        <div id="productList" class="list-group list-group-flush"></div>
                                    </div>
                                </div>
                            </div>

                            {{-- Quét hoặc nhập barcode --}}
                            <div class="col-md-6">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <label for="barcodeInput" class="form-label fw-semibold mb-0">
                                        Quét hoặc nhập barcode
                                    </label>

                                    <span class="text-muted small">
                                        Nhấn Enter để thêm vào giỏ
                                    </span>
                                </div>

                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fa-solid fa-barcode"></i>
                                    </span>

                                    <input id="barcodeInput" type="text" class="form-control"
                                        placeholder="Quét hoặc nhập barcode..." autocomplete="off" />
                                </div>

                                <div id="barcodeFeedback" class="barcode-feedback small text-muted mt-1"></div>
                            </div>
                        </div>

                        <div class="mt-3 small text-muted">
                            Gợi ý sẽ xuất hiện khi bạn nhập — bấm vào dòng sản phẩm để thêm vào giỏ.
                        </div>
                    </div>
                </div>

                <!-- Section: Giỏ hàng / tính tiền -->
                <div class="card">
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
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label mb-1">Khuyến mãi</label>
                                    <div class="input-group">
                                        <input id="discountInput" type="number" class="form-control" min="0"
                                            step="1000" placeholder="Số tiền hoặc %" />
                                        <select id="discountType" class="form-select" style="max-width:120px">
                                            <option value="amount">VND</option>
                                            <option value="percent">%</option>
                                        </select>
                                    </div>
                                    <div class="form-text">Để trống nếu không áp dụng.</div>
                                </div>
                                <div class="col-md-4">
                                </div>
                                <div class="col-md-4">
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
            <div class="col-lg-3">

                <div class="card mb-4">
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
                                <select id="paymentMethod" class="form-select">
                                    <option value="cash">Tiền mặt</option>
                                    <option value="bank_transfer">Chuyển khoản</option>
                                    <option value="debt">Công nợ</option>
                                </select>
                            </div>
                            <input type="hidden" id="custId">
                            <div class="col-12 d-grid">
                                <button class="btn btn-success" id="saveOrderBtn">Lưu đơn</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
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
                            <label class="form-label">Họ tên</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" name="phone" required>
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
                            <h5 class="mt-2">Siêu Thị Thực Phẩm - CH01</h5>
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
                    <button class="btn btn-dark" id="pay-button">Thanh toán</button>
                </div>
            </div>
        </div>
    </div>
@endsection


@push('script')
    <script>
        $(function() {
            // --------- Helpers ---------
            const money = n => (parseFloat(n) || 0).toLocaleString('vi-VN');
            const qs = (s, el = document) => el.querySelector(s);
            const qsa = (s, el = document) => [...el.querySelectorAll(s)];
            const appBaseUrl = @json(rtrim(config('app.url'), '/'));
            const storageBaseUrl = `${appBaseUrl}/storage`;
            const paymentMethodLabels = {
                cash: 'Tiền mặt',
                bank_transfer: 'Chuyển khoản',
                debt: 'Công nợ',
            };
            const bankCode = @json($bankCode);
            const bankAccount = @json($bankAccountForQr);
            let currentInvoiceGrand = 0;

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

            function productThumbHtml(product, className = 'product-thumb') {
                const name = escapeHtml(safeText(product?.name, 'Sản phẩm'));
                const thumbnail = safeText(product?.thumbnail);

                if (!thumbnail) {
                    return `<div class="${className} product-thumb-placeholder d-flex align-items-center justify-content-center" aria-label="${name}">
                        <i class="fa-solid fa-box-open"></i>
                    </div>`;
                }

                return `<img class="${className}" src="${storageBaseUrl}/${encodeURI(thumbnail)}" alt="${name}" />`;
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

            function resolveBarcode(barcode) {
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
                        addToCart(product);
                        productPopup.style.display = 'none';
                        barcodeFeedback.textContent =
                            `${product.product_name || product.name} đã được thêm vào giỏ.`;
                        Toast.fire({
                            icon: "success",
                            title: "Đã thêm barcode vào giỏ."
                        });
                    },
                    error: (xhr) => {
                        const message = xhr.responseJSON?.message || 'Không thể xử lý barcode.';
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
                const keyword = e.target.value.trim();

                productPopup.style.display = 'block';
                fetchProducts(keyword);
            }, 300));

            productSearch.addEventListener('focus', () => {
                productPopup.style.display = 'block';

                if (productList.children.length === 0) {
                    fetchProducts('');
                }
            });
            productSearch.addEventListener('focus', async () => {
                isCallApiProducts && await fetchProducts()

                productPopup.style.display = 'block';
            });

            document.addEventListener('click', (e) => {
                if (!productPopup.contains(e.target) && e.target !== productSearch) {
                    productPopup.style.display = 'none';
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

            function renderProductResults(response) {
                const products = normalizeProductResponse(response).filter(Boolean);
                productList.innerHTML = '';

                if (products.length === 0) {
                    productList.innerHTML =
                        '<div class="list-group-item text-center text-muted">Không tìm thấy sản phẩm</div>';
                    return;
                }

                products.forEach(product => {
                    const p = product || {};
                    const trackingType = p.tracking_type || p.inventory_tracking || 'quantity';
                    const isImeiProduct = trackingType === 'imei_product';
                    const availableQuantity = Number(p.available_quantity ?? p.quantity ?? 0);
                    const productName = safeText(p.name, 'Sản phẩm chưa có tên');
                    const productCode = safeText(p.code);
                    const productBarcode = safeText(p.barcode);
                    const badgeText = isImeiProduct ? 'Quản lý theo IMEI' : 'Sản phẩm thường';
                    const stockText = isImeiProduct ?
                        `Thiết bị còn tồn: ${availableQuantity}` :
                        `Tồn: ${availableQuantity}`;
                    const badgeClass = isImeiProduct ? 'bg-warning text-dark' : 'bg-light text-dark border';

                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'list-group-item list-group-item-action product-row';
                    row.innerHTML = `
                    <div class="d-flex align-items-center gap-3">
                        ${productThumbHtml(p)}
                        <div class="flex-grow-1">
                        <div class="fw-semibold">${escapeHtml(productName)}</div>
                        <div class="small text-muted">${money(p.price_buy)}</div>
                        <div class="small text-muted">
                            ${productCode ? `Mã: ${escapeHtml(productCode)}` : ''}
                            ${productCode && productBarcode ? ' · ' : ''}
                            ${productBarcode ? `Barcode: ${escapeHtml(productBarcode)}` : ''}
                        </div>
                        </div>
                        <div class="text-end d-flex flex-column align-items-end gap-1">
                        <span class="badge ${badgeClass}">${badgeText}</span>
                        <span class="badge border badge-stock text-dark">${stockText}</span>
                        </div>
                    </div>`;

                    row.addEventListener('click', () => {

                        if (isImeiProduct) {
                            Toast.fire({
                                icon: "info",
                                title: "Sản phẩm này quản lý theo IMEI. Vui lòng quét barcode của thiết bị cụ thể để thêm vào giỏ."
                            });
                            productPopup.style.display = 'none';
                            productSearch.value = '';
                            focusBarcodeInput();
                            return;
                        }

                        if (availableQuantity <= 0) return Toast.fire({
                            icon: "error",
                            title: "Số lượng tồn kho không đủ!"
                        })

                        addToCart({
                            ...p,
                            tracking_type: 'quantity',
                            quantity: availableQuantity,
                            available_quantity: availableQuantity
                        });
                        productPopup.style.display = 'none';
                        productSearch.value = '';
                    });
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
                    const key = `imei:${product.product_imei_id}`;

                    if (cart.has(key)) {
                        Toast.fire({
                            icon: "error",
                            title: "Thiết bị đã có trong giỏ."
                        });
                        return;
                    }

                    cart.set(key, {
                        product: normalizeCartProduct(product, 'imei'),
                        qty: 1
                    });
                    renderCart();
                    return;
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
                    return;
                }

                item.qty += 1;
                cart.set(key, item);
                renderCart();
            }

            function normalizeCartProduct(product, trackingType) {
                const productId = Number(product.product_id || product.id);

                return {
                    ...product,
                    id: productId,
                    product_id: productId,
                    tracking_type: trackingType,
                    quantity: Number(product.available_quantity || product.quantity || 1),
                    available_quantity: Number(product.available_quantity || product.quantity || 1),
                    price_buy: Number(product.price_buy || product.price || 0),
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
                            '<span class="badge bg-secondary">1</span>' :
                            `<input type="number" min="1" max="${product.available_quantity}" value="${qty}"
                                class="form-control form-control-sm text-center qty-input" style="width: 80px" />`;

                        const row = document.createElement('div');
                        row.className = 'cart-row';
                        row.dataset.rowId = key;
                        row.innerHTML = `
                        ${productThumbHtml(product, 'cart-thumb')}
                        <div class="cart-info">
                        <div class="fw-semibold">${productName}</div>
                        <div class="small text-muted">Giá: ${money(product.price_buy)}</div>
                        <div class="small text-muted">${stockText}</div>
                        ${isImei && productBarcode ? `<div class="small text-muted">Barcode: ${productBarcode}</div>` : ''}
                        </div>
                        <div class="cart-actions">
                        ${quantityControl}
                        <button class="btn btn-sm btn-outline-danger remove-btn">&times;</button>
                        </div>
                    `;
                        const qtyInput = qs('.qty-input', row);
                        if (qtyInput) {
                            qtyInput.addEventListener('input', (e) => updateQty(key, e.target.value));
                        }
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

                    sum += product.price_buy * qty;
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
                            <td> ${ money(product.price_buy) }</td>
                            <td>${ money(product.price_buy * qty) }</td>
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

                // Validate: thông tin khách hàng
                const name = qs('#custName').value.trim();
                const phone = qs('#custPhone').value.trim();
                const email = qs('#custEmail').value.trim();

                if (!name) {
                    Toast.fire({
                        icon: "error",
                        title: "Vui lòng nhập họ tên khách hàng!"
                    });
                    qs('#custName').focus();
                    return;
                }

                if (!email) {
                    Toast.fire({
                        icon: "error",
                        title: "Vui lòng nhập email khách hàng!"
                    });
                    qs('#custEmail').focus();
                    return;
                }

                if (!phone) {
                    Toast.fire({
                        icon: "error",
                        title: "Vui lòng nhập số điện thoại khách hàng!"
                    });
                    qs('#custPhone').focus();
                    return;
                }

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
                        price: product.price,
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

            $('#invoiceModal').on('hidden.bs.modal', resetBankTransferInfo);

            $('#pay-button').on('click', function() {
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
                        price: product.price_buy,
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

                        Toast.fire({
                            icon: "success",
                            title: res.message
                        });

                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
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
        })
    </script>
@endpush
