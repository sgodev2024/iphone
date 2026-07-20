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

    <div class="container-fluid py-4">
        <div class="row g-4">
            <!-- LEFT 9 cols -->
            <div class="col-lg-9">
                <!-- Section: Sản phẩm + search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 class="card-title mb-0">Tìm & chọn sản phẩm</h5>
                            <span class="text-muted small">Nhập tên, mã hoặc từ khóa…</span>
                        </div>

                        <div class="search-wrapper">
                            <input id="productSearch" type="text" class="form-control" placeholder="Tìm kiếm sản phẩm…"
                                autocomplete="off" />
                            <div id="productPopup" class="search-popup">
                                <!-- Kết quả sản phẩm sẽ render ở đây -->
                                <div id="productList" class="list-group list-group-flush"></div>
                            </div>
                        </div>

                        <div class="mt-3 small text-muted">Gợi ý sẽ xuất hiện khi bạn nhập — bấm vào dòng sản phẩm để thêm
                            vào giỏ.</div>
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
                        <div class="qr-section text-center my-4">
                            <p>Cảm ơn quý khách!</p>
                            <img src="" alt="QR Code" width="180" id="qr-code">
                            <p class="mt-2 mb-0"><strong>{{ $config->bank->name }}:</strong> {{ $config['bank_account'] }}
                            </p>
                            <p class="mb-0">{{ $config['receiver'] }}</p>
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
            let isCallApiProducts = true;
            let isCallApiClients = true;

            let searchTimer = null;
            productSearch.addEventListener('input', debounce((e) => {
                fetchProducts(e.target.value.trim());
            }, 300));

            productSearch.addEventListener('focus', async () => {
                isCallApiProducts && await fetchProducts()

                productPopup.style.display = 'block';
            });

            document.addEventListener('click', (e) => {
                if (!productPopup.contains(e.target) && e.target !== productSearch) {
                    productPopup.style.display = 'none';
                }
            });

            function renderProductResults(products) {
                const path = "{{ config('app.url') }}"
                productList.innerHTML = '';

                if (products.length === 0) {
                    productList.innerHTML =
                        '<div class="list-group-item text-center text-muted">Không tìm thấy sản phẩm</div>';
                    return;
                }

                products.forEach(p => {

                    const row = document.createElement('button');
                    row.type = 'button';
                    row.className = 'list-group-item list-group-item-action product-row';
                    row.innerHTML = `
                    <div class="d-flex align-items-center gap-3">
                        <img class="product-thumb" src="${path}/storage/${p.thumbnail}" alt="${p.name}" />
                        <div class="flex-grow-1">
                        <div class="fw-semibold">${p.name}</div>
                        <div class="small text-muted">${money(p.price_buy)}</div>
                        </div>
                        <div class="text-end">
                        <span class="badge border badge-stock text-dark">Tồn: ${p.quantity}</span>
                        </div>
                    </div>`;

                    row.addEventListener('click', () => {

                        if (p.quantity <= 0) return Toast.fire({
                            icon: "error",
                            title: "Số lượng tồn kho không đủ!"
                        })

                        addToCart(p);
                        productPopup.style.display = 'none';
                        productSearch.value = '';
                    });
                    productList.appendChild(row);
                });
            }

            // --------- Cart Logic ---------
            const cart = new Map(); // key: productId -> {product, qty}
            const cartBody = qs('#cartBody');
            const cartEmptyRow = qs('#cartEmptyRow');

            function addToCart(product) {
                const item = cart.get(product.id) || {
                    product,
                    qty: 0
                };
                item.qty = Math.min(item.qty + 1, product.quantity);
                cart.set(product.id, item);
                renderCart();
            }

            function removeFromCart(id) {
                cart.delete(id);
                renderCart();
            }

            function updateQty(id, qty) {

                const item = cart.get(id);
                if (!item) return;
                const q = Math.max(1, Math.min(Number(qty) || 1, item.product.quantity));
                item.qty = q;
                cart.set(id, item);
                renderCartTotals();
            }

            function renderCart() {
                const path = "{{ config('app.url') }}"

                cartBody.innerHTML = '';
                if (cart.size === 0) {
                    cartEmptyRow.style.display = '';
                } else {
                    cartEmptyRow.style.display = 'none';

                    for (const [id, {
                            product,
                            qty
                        }] of cart.entries()) {

                        const row = document.createElement('div');
                        row.className = 'cart-row';
                        row.dataset.rowId = id;
                        row.innerHTML = `
                        <img class="cart-thumb" src="${path}/storage/${product.thumbnail}" alt="${product.name}">
                        <div class="cart-info">
                        <div class="fw-semibold">${product.name}</div>
                        <div class="small text-muted">Giá: ${money(product.price_buy)}</div>
                        <div class="small text-muted">Tồn kho: ${product.quantity}</div>
                        </div>
                        <div class="cart-actions">
                        <input type="number" min="1" max="${product.quantity}" value="${qty}"
                            class="form-control form-control-sm text-center qty-input" style="width: 80px" />
                        <button class="btn btn-sm btn-outline-danger remove-btn">&times;</button>
                        </div>
                    `;
                        qs('.qty-input', row).addEventListener('input', (e) => updateQty(product.id, e.target
                            .value));
                        qs('.remove-btn', row).addEventListener('click', () => removeFromCart(product.id));
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

                    _html += `
                        <tr>
                            <td>${index + 1}</td>
                            <td class="text-start">${product.name}</td>
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

                $('#invoice-subtotal').html(money(sub) + ' VND');
                $('#invoice-discount').html('-' + money(discount) + ' VND');
                $('#invoice-total').html(money(grand) + ' VND');
                $('#total-text').html(convertNumberToWords(grand));
                $('#invoice-note').html($('#orderNote').val());
                $('#payment-method').html($('#paymentMethod option:selected').text());
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
                        name: product.name,
                        price: product.price,
                        qty
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
                        payment: qs('#paymentMethod').value,
                        note: qs('#orderNote')?.value || ''
                    }
                };

                let bankCode = "{{ $config->bank->code }}"
                let bankAccount = "{{ $config->bank_account_number }}"

                // https: //img.vietqr.io/image/MB-1080128122002-compact.png?amount=1000&addInfo=
                $('#qr-code').attr('src',
                    `https://img.vietqr.io/image/${bankCode}-${bankAccount}-compact.png?amount=${order.grand}&addInfo=ThanhToanDonHang`
                )

                $('#invoiceModal').modal('show')
            });

            function fetchProducts(searchText) {
                $.ajax({
                    url: '/ban-hang/product',
                    method: 'GET',
                    data: {
                        searchText
                    },
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    success: (res) => {
                        isCallApiProducts = false
                        renderProductResults(res)
                    },
                    error: (xhr) => {
                        Toast.fire({
                            icon: "error",
                            title: xhr.responseJSON?.message ||
                                'Đã có lỗi xảy ra. Vui lòng thử lại sau!'
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
                        alert('Đã có lỗi xảy ra. Vui lòng thử lại sau!')
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

            $('#pay-button').on('click', function() {
                const order = {
                    items: [...cart.values()].map(({
                        product,
                        qty
                    }) => ({
                        id: product.id,
                        name: product.name,
                        price: product.price_buy,
                        qty
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
                        payment: qs('#paymentMethod').value,
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
