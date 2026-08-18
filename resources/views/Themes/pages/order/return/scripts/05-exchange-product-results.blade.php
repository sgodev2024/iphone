
        function normalizeExchangeProductResponse(
            response
        ) {

            if (Array.isArray(response)) {
                return response;
            }


            if (
                Array.isArray(
                    response?.data
                )
            ) {
                return response.data;
            }


            return [];
        }


        function renderExchangeProductResults(
            response
        ) {

            const products =
                normalizeExchangeProductResponse(
                    response
                )
                .filter(Boolean);


            exchangeProductList.innerHTML =
                '';


            exchangeProductResultState.clear();


            if (products.length === 0) {

                exchangeProductList.innerHTML = `
            <div
                class="
                    list-group-item
                    text-center
                    text-muted
                "
            >
                Không tìm thấy sản phẩm
            </div>
        `;

                return;
            }


            products.forEach(
                (product, index) => {

                    const trackingType =
                        product.tracking_type ||
                        product.inventory_tracking ||
                        'quantity';


                    const isImeiDevice =
                        trackingType === 'imei' ||
                        product.result_type ===
                        'imei_device';


                    const isImeiProduct =
                        trackingType ===
                        'imei_product';


                    const availableQuantity =
                        Number(
                            product.available_quantity ??
                            product.quantity ??
                            0
                        );


                    const name =
                        safeText(
                            product.name,
                            'Sản phẩm'
                        );


                    const resultKey =
                        String(index);


                    const row =
                        document.createElement(
                            'button'
                        );


                    row.type =
                        'button';


                    row.className =
                        'list-group-item ' +
                        'list-group-item-action ' +
                        'exchange-product-row';


                    row.dataset
                        .exchangeProductResultKey =
                        resultKey;


                    row.innerHTML = `
                <div
                    class="
                        d-flex
                        justify-content-between
                        align-items-center
                        gap-3
                    "
                >

                    <div class="min-width-0">

                        <div class="fw-semibold">
                            ${escapeHtml(name)}
                        </div>

                        <div class="small text-muted">
                            ${money(
                                product.price
                                ?? product.unit_price
                            )}
                        </div>

                        ${
                            isImeiDevice
                                ?
                                `
                                <div class="small text-muted">
                                    IMEI:
                                    ${escapeHtml(
                                        product.imei
                                    )}
                                </div>
                                `
                                :
                                ''
                        }

                        ${
                            product.code
                                ?
                                `
                                <div class="small text-muted">
                                    Mã:
                                    ${escapeHtml(
                                        product.code
                                    )}
                                </div>
                                `
                                :
                                ''
                        }

                    </div>


                    <div class="text-end">

                        ${
                            isImeiDevice
                                ?
                                `
                                <span
                                    class="
                                        badge
                                        bg-info
                                        text-dark
                                    "
                                >
                                    Thiết bị IMEI
                                </span>
                                `
                                :
                            isImeiProduct
                                ?
                                `
                                <span
                                    class="
                                        badge
                                        bg-warning
                                        text-dark
                                    "
                                >
                                    Quản lý IMEI
                                </span>
                                `
                                :
                                `
                                <span
                                    class="
                                        badge
                                        bg-light
                                        text-dark
                                        border
                                    "
                                >
                                    Tồn:
                                    ${availableQuantity}
                                </span>
                                `
                        }

                    </div>

                </div>
            `;


                    exchangeProductResultState
                        .set(
                            resultKey,
                            product
                        );


                    exchangeProductList
                        .appendChild(
                            row
                        );
                }
            );
        }
        exchangeProductList
            ?.addEventListener(
                'click',
                function(event) {

                    const row =
                        event.target.closest(
                            '[data-exchange-product-result-key]'
                        );


                    if (!row) {
                        return;
                    }


                    const product =
                        exchangeProductResultState
                        .get(
                            row.dataset
                            .exchangeProductResultKey
                        );


                    if (!product) {
                        return;
                    }


                    handleExchangeProductSelection(
                        product
                    );
                }
            );

        function handleExchangeProductSelection(
            product
        ) {

            const trackingType =
                product.tracking_type ||
                product.inventory_tracking ||
                'quantity';


            const isImeiDevice =
                trackingType === 'imei' ||
                product.result_type ===
                'imei_device';


            const isImeiProduct =
                trackingType ===
                'imei_product';


            const availableQuantity =
                Number(
                    product.available_quantity ??
                    product.quantity ??
                    0
                );


            /*
             * Thiết bị IMEI cụ thể.
             */
            if (isImeiDevice) {

                addToExchangeCart({
                    ...product,
                    tracking_type: 'imei',

                    quantity: 1,

                    available_quantity: 1
                });


                exchangeProductPopup
                    .style
                    .display =
                    'none';


                exchangeProductSearch.value =
                    '';


                return;
            }


            /*
             * Sản phẩm IMEI chung:
             * chưa biết khách lấy IMEI nào.
             */
            if (isImeiProduct) {

                notify(
                    'info',
                    'Sản phẩm này quản lý theo IMEI. Hãy nhập IMEI hoặc quét barcode của thiết bị cụ thể.'
                );


                exchangeProductPopup
                    .style
                    .display =
                    'none';


                exchangeProductSearch.value =
                    '';


                exchangeBarcodeInput
                    ?.focus();


                return;
            }


            /*
             * Hàng thường.
             */
            if (availableQuantity <= 0) {

                notify(
                    'error',
                    'Sản phẩm đã hết hàng.'
                );

                return;
            }


            addToExchangeCart({
                ...product,

                tracking_type: 'quantity',

                available_quantity: availableQuantity
            });


            exchangeProductPopup
                .style
                .display =
                'none';


            exchangeProductSearch.value =
                '';
        }
        exchangeProductSearch
            ?.addEventListener(
                'input',

                debounce(
                    function(event) {

                        fetchExchangeProducts(
                            event.target.value.trim()
                        );
                    },
                    300
                )
            );


        exchangeProductSearch
            ?.addEventListener(
                'focus',
                function() {

                    fetchExchangeProducts(
                        exchangeProductSearch
                        .value
                        .trim()
                    );
                }
            );


        document.addEventListener(
            'click',
            function(event) {

                if (
                    !exchangeProductPopup ||
                    !exchangeProductSearch
                ) {
                    return;
                }


                if (
                    !exchangeProductPopup
                    .contains(event.target) &&
                    event.target !==
                    exchangeProductSearch
                ) {

                    exchangeProductPopup
                        .style
                        .display =
                        'none';
                }
            }
        );
