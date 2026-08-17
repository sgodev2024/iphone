
        function renderExchangeCart() {

            const body =
                document.querySelector(
                    '#exchangeCartBody'
                );


            const empty =
                document.querySelector(
                    '#exchangeCartEmpty'
                );


            if (!body || !empty) {
                return;
            }


            body.innerHTML =
                '';


            if (
                exchangeCart.size === 0
            ) {

                empty.classList
                    .remove('d-none');


                renderSummary();

                return;
            }


            empty.classList
                .add('d-none');


            for (
                const [
                    key,
                    item
                ] of exchangeCart.entries()
            ) {

                const product =
                    item.product;


                const quantity =
                    Number(
                        item.quantity
                    );


                const isImei =
                    product.tracking_type ===
                    'imei';


                const lineTotal =
                    Number(
                        product.unit_price
                    ) *
                    quantity;


                const row =
                    document.createElement(
                        'div'
                    );


                row.className =
                    'exchange-cart-row';


                row.dataset
                    .exchangeCartKey =
                    key;


                row.innerHTML = `

        <div class="exchange-cart-product">

            <div class="exchange-cart-name">
                ${escapeHtml(
                    product.name
                )}
            </div>

            ${
                isImei
                    ?
                    `
                    <div class="exchange-cart-meta">
                        IMEI:
                        ${escapeHtml(
                            product.imei
                        )}
                    </div>
                    `
                    :
                    `
                    <div class="exchange-cart-meta">
                        Tồn:
                        ${Number(
                            product.available_quantity
                        )}
                    </div>
                    `
            }

        </div>
        


        <div class="exchange-cart-price">

            <label class="small text-muted mb-1">
                Giá bán
            </label>

            <input
                type="text"
                inputmode="numeric"
                class="
                    form-control
                    form-control-sm
                    exchange-unit-price
                "
                value="${
                    Number(
                        product.unit_price
                    ).toLocaleString(
                        'vi-VN'
                    )
                }"
            >

        </div>


        <div class="exchange-cart-qty">

            <label class="small text-muted mb-1">
                Số lượng
            </label>

            ${
                isImei
                    ?
                    `
                    <div
                        class="
                            form-control
                            form-control-sm
                            bg-light
                            text-center
                        "
                    >
                        1
                    </div>
                    `
                    :
                    `
                    <input
                        type="number"
                        min="1"
                        max="${
                            product
                                .available_quantity
                        }"
                        value="${quantity}"
                        class="
                            form-control
                            form-control-sm
                            exchange-qty-input
                            text-center
                        "
                    >
                    `
            }

        </div>


        <div class="exchange-cart-line-total">
            ${money(lineTotal)}
        </div>


        <button
            type="button"
            class="
                btn
                btn-outline-danger
                btn-sm
                exchange-cart-remove
            "
            title="Xóa sản phẩm"
        >
            <i class="fa-solid fa-xmark"></i>
        </button>
    `;
    const clearExchangeCartBtn = document.querySelector(
    '#clearExchangeCartBtn'
);

clearExchangeCartBtn?.addEventListener(
    'click',
    function () {

        if (exchangeCart.size === 0) {
            return;
        }

        exchangeCart.clear();

        renderExchangeCart();
    }
);



                /*
                 * Quantity
                 */
                const qtyInput =
                    row.querySelector(
                        '.exchange-qty-input'
                    );


                qtyInput
                    ?.addEventListener(
                        'change',
                        function() {

                            updateExchangeQuantity(
                                key,
                                this.value
                            );
                        }
                    );


                /*
                 * Price
                 */
                const priceInput =
                    row.querySelector(
                        '.exchange-unit-price'
                    );


                priceInput
                    ?.addEventListener(
                        'change',
                        function() {

                            updateExchangeUnitPrice(
                                key,
                                this
                            );
                        }
                    );


                /*
                 * Remove
                 */
                row.querySelector(
                        '.exchange-cart-remove'
                    )
                    ?.addEventListener(
                        'click',
                        function() {

                            exchangeCart
                                .delete(key);


                            renderExchangeCart();
                        }
                    );


                body.appendChild(
                    row
                );
            }


            renderSummary();
        }

        function updateExchangeQuantity(
            key,
            value
        ) {

            const item =
                exchangeCart.get(key);


            if (!item) {
                return;
            }


            if (
                item.product
                .tracking_type ===
                'imei'
            ) {

                item.quantity = 1;

                return;
            }


            const max =
                Number(
                    item.product
                    .available_quantity ||
                    0
                );


            let quantity =
                Number(value) || 1;


            quantity =
                Math.max(
                    1,
                    Math.min(
                        quantity,
                        max
                    )
                );


            item.quantity =
                quantity;


            exchangeCart.set(
                key,
                item
            );


            renderExchangeCart();
        }


        function updateExchangeUnitPrice(
            key,
            input
        ) {

            const item =
                exchangeCart.get(key);


            if (!item) {
                return;
            }


            const price =
                parseMoney(
                    input.value
                );


            if (price <= 0) {

                input.classList
                    .add(
                        'is-invalid'
                    );

                notify(
                    'warning',
                    'Giá bán phải lớn hơn 0.'
                );

                return;
            }


            input.classList
                .remove(
                    'is-invalid'
                );


            item.product.unit_price =
                price;


            item.product.price =
                price;


            exchangeCart.set(
                key,
                item
            );


            renderExchangeCart();
        }

        function exchangeAmount() {

            let amount = 0;


            for (
                const item of exchangeCart.values()
            ) {

                amount +=
                    Number(
                        item.product.unit_price
                    ) *
                    Number(
                        item.quantity
                    );
            }


            return amount;
        }
