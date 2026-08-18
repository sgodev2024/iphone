        function setReturnQuantity(
            detailId,
            quantity
        ) {

            const id =
                Number(detailId);

            const item =
                itemsById.get(id);


            if (
                !item ||
                !returnCart.has(id)
            ) {
                return;
            }


            /*
             * IMEI bắt buộc = 1.
             */
            if (
                item.tracking_type ===
                'imei'
            ) {
                returnCart.set(
                    id,
                    1
                );

                renderReturnCart();

                return;
            }


            let qty =
                Number(quantity) || 1;


            qty = Math.max(
                1,
                Math.min(
                    qty,
                    item.returnable_quantity
                )
            );


            returnCart.set(
                id,
                qty
            );


            renderReturnCart();
        }


        /*
         |--------------------------------------------------------------------------
         | RENDER CART
         |--------------------------------------------------------------------------
         */
        function renderReturnCart() {

            const cartBody =
                document.querySelector(
                    '#returnCartBody'
                );

            const empty =
                document.querySelector(
                    '#returnCartEmpty'
                );


            if (!cartBody || !empty) {
                return;
            }


            cartBody.innerHTML = '';


            if (returnCart.size === 0) {

                empty.classList.remove(
                    'd-none'
                );

                syncSourceButtons();
                renderSummary();

                return;
            }


            empty.classList.add(
                'd-none'
            );


            for (
                const [detailId, qty] of returnCart.entries()
            ) {

                const item =
                    itemsById.get(detailId);


                if (!item) {
                    continue;
                }


                const preview =
                    calculateReturnItem(
                        item,
                        qty
                    );


                const row =
                    document.createElement(
                        'div'
                    );


                row.className =
                    'return-cart-row';


                const imeiHtml =
                    item.imei ?
                    `
                            <div class="return-product-meta">
                                IMEI:
                                ${escapeHtml(item.imei)}
                            </div>
                            ` :
                    '';


                const quantityHtml =
                    item.tracking_type ===
                    'imei' ?
                    `
                            <div class="return-cart-quantity">
                                <label class="small text-muted mb-1 d-block">
                                    Số lượng
                                </label>

                                <div
                                    class="
                                        form-control
                                        text-center
                                        bg-light
                                    "
                                >
                                    1
                                </div>
                            </div>
                            ` :
                    `
                            <div class="return-cart-quantity">
                                <label class="small text-muted mb-1 d-block">
                                    Số lượng trả
                                </label>

                                <input
                                    type="number"
                                    class="
                                        form-control
                                        text-center
                                        return-cart-qty
                                    "
                                    min="1"
                                    max="${item.returnable_quantity}"
                                    value="${qty}"
                                    data-detail-id="${detailId}"
                                >

                                <div class="small text-muted mt-1">
                                    Tối đa:
                                    ${item.returnable_quantity}
                                </div>
                            </div>
                            `;


                row.innerHTML = `
                        <div class="return-cart-info">

                            <div class="return-product-name">
                                ${escapeHtml(item.product_name)}
                            </div>

                            ${imeiHtml}

                            <div class="return-product-meta">
                                Đơn giá:
                                ${money(item.unit_price)}
                            </div>

                        </div>


                        ${quantityHtml}


                        <div class="return-cart-amount">
                            <div class="small text-muted mb-1">
                                Giá trị trả
                            </div>

                            ${money(preview.returnAmount)}
                        </div>


                        <button
                            type="button"
                            class="
                                btn
                                btn-outline-danger
                                btn-sm
                                return-cart-remove
                            "
                            data-detail-id="${detailId}"
                            title="Bỏ khỏi danh sách trả"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    `;


                cartBody.appendChild(row);
            }


            syncSourceButtons();
            renderSummary();
        }


        /*
         |--------------------------------------------------------------------------
         | ĐỒNG BỘ BUTTON SẢN PHẨM GỐC
         |--------------------------------------------------------------------------
         */
        function syncSourceButtons() {

            document
                .querySelectorAll(
                    '.return-add-btn'
                )
                .forEach(button => {

                    const detailId =
                        Number(
                            button.dataset
                            .detailId
                        );


                    if (
                        returnCart.has(
                            detailId
                        )
                    ) {

                        button.disabled = true;

                        button.innerHTML = `
                                <i
                                    class="
                                        fa-solid
                                        fa-check
                                        me-1
                                    "
                                ></i>
                                Đã chọn
                            `;

                        return;
                    }


                    button.disabled = false;

                    button.innerHTML = `
                            <i
                                class="
                                    fa-solid
                                    fa-solid fa-trash me-1
                                    me-1
                                    text-danger
                                "
                            ></i>

                        `;
                });
        }
