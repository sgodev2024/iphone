
        function currentExchangeImeiIds() {

            return [...exchangeCart.values()]
                .filter(
                    item =>
                    item.product
                    .tracking_type ===
                    'imei'
                )
                .map(
                    item =>
                    Number(
                        item.product
                        .product_imei_id
                    )
                );
        }

        function resolveExchangeBarcode(barcode) {

            barcode =
                String(barcode ?? '')
                .trim();


            if (
                !barcode ||
                exchangeBarcodeResolving
            ) {
                return;
            }


            const now =
                Date.now();


            if (
                barcode ===
                exchangeLastBarcode &&
                now -
                exchangeLastBarcodeAt <
                800
            ) {

                exchangeBarcodeInput.value = '';

                exchangeBarcodeInput.focus();

                return;
            }


            exchangeBarcodeResolving = true;

            exchangeLastBarcode =
                barcode;

            exchangeLastBarcodeAt =
                now;


            exchangeBarcodeFeedback
                .classList
                .remove(
                    'text-danger',
                    'text-warning'
                );


            exchangeBarcodeFeedback
                .classList
                .add(
                    'text-muted'
                );


            exchangeBarcodeFeedback
                .textContent =
                'Đang xử lý barcode / IMEI...';


            $.ajax({

                url: '/ban-hang/barcode/resolve',

                method: 'POST',

                data: {

                    barcode,

                    cart_imei_ids: currentExchangeImeiIds(),

                    cart_product_quantities: currentExchangeProductQuantities()
                },

                headers: {

                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },


                success: function(product) {

                    const added =
                        addToExchangeCart(
                            product
                        );


                    if (!added) {
                        return;
                    }


                    exchangeBarcodeFeedback
                        .classList
                        .remove(
                            'text-danger',
                            'text-warning'
                        );


                    exchangeBarcodeFeedback
                        .classList
                        .add(
                            'text-success'
                        );


                    exchangeBarcodeFeedback
                        .textContent =
                        'Đã thêm sản phẩm.';


                    notify(
                        'success',
                        product.tracking_type ===
                        'imei' ?
                        'Đã thêm thiết bị IMEI.' :
                        'Đã thêm sản phẩm.'
                    );
                },


                error: function(xhr) {

                    const message =
                        xhr.responseJSON
                        ?.message ||
                        'Không thể xử lý barcode / IMEI.';


                    exchangeBarcodeFeedback
                        .classList
                        .remove(
                            'text-muted'
                        );


                    exchangeBarcodeFeedback
                        .classList
                        .add(
                            'text-danger'
                        );


                    exchangeBarcodeFeedback
                        .textContent =
                        message;


                    notify(
                        'error',
                        message
                    );
                },


                complete: function() {

                    exchangeBarcodeResolving =
                        false;


                    exchangeBarcodeInput.value =
                        '';


                    exchangeBarcodeInput.focus();
                }
            });
        }
        /*
 |--------------------------------------------------------------------------
 | BARCODE / IMEI EVENTS
 |--------------------------------------------------------------------------
 */

/*
 * Quét barcode scanner thường kết thúc bằng Enter.
 */
exchangeBarcodeInput
    ?.addEventListener(
        'keydown',
        function(event) {

            if (event.key !== 'Enter') {
                return;
            }

            event.preventDefault();

            resolveExchangeBarcode(
                exchangeBarcodeInput.value
            );
        }
    );


/*
 * Cho phép nhập thủ công rồi bấm nút Thêm.
 */
exchangeBarcodeAddBtn
    ?.addEventListener(
        'click',
        function() {

            resolveExchangeBarcode(
                exchangeBarcodeInput.value
            );
        }
    );

        function fetchExchangeProducts(
            search = ''
        ) {

            exchangeProductList.innerHTML = `
        <div
            class="
                list-group-item
                text-center
                text-muted
            "
        >
            Đang tìm kiếm...
        </div>
    `;


            exchangeProductPopup.style.display =
                'block';


            return $.ajax({

                url: "{{ url('/ban-hang/product') }}",

                method: 'GET',

                data: {
                    search
                },


                success: function(response) {

                    renderExchangeProductResults(
                        response
                    );

                    exchangeProductPopup
                        .style
                        .display =
                        'block';
                },


                error: function(xhr) {

                    exchangeProductList.innerHTML = `
                    <div
                        class="
                            list-group-item
                            text-center
                            text-danger
                        "
                    >
                        Không thể tải danh sách sản phẩm
                    </div>
                `;


                    notify(
                        'error',
                        xhr.responseJSON
                        ?.message ||
                        'Không thể tìm kiếm sản phẩm.'
                    );
                }
            });
        }

        function currentExchangeProductQuantities() {

            const quantities = {};


            for (
                const item of exchangeCart.values()
            ) {

                if (
                    item.product
                    .tracking_type !==
                    'quantity'
                ) {
                    continue;
                }


                quantities[
                        item.product.product_id
                    ] =
                    Number(
                        item.quantity
                    );
            }


            return quantities;
        }

        function removeReturnItem(detailId) {

            returnCart.delete(
                Number(detailId)
            );

            renderReturnCart();
        }
