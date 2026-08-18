        /*
         |--------------------------------------------------------------------------
         | RETURN CART
         |--------------------------------------------------------------------------
         |
         | Map:
         |
         | order_detail_id => quantity
         |
         */
        const returnCart = new Map();
        /*
 |--------------------------------------------------------------------------
 | EXCHANGE CART
 |--------------------------------------------------------------------------
 |
 | quantity:{productId}
 | imei:{productImeiId}
 |
 */
        const exchangeCart = new Map();
        //Normalize exchange product
        function normalizeExchangeProduct(
            product,
            trackingType
        ) {

            const productId =
                Number(
                    product.product_id ||
                    product.id ||
                    0
                );


            const unitPrice =
                Number(
                    product.unit_price ??
                    product.price ??
                    0
                );


            return {
                ...product,

                id: productId,

                product_id: productId,

                tracking_type: trackingType,

                product_imei_id: product.product_imei_id ?
                    Number(
                        product.product_imei_id
                    ) : null,

                available_quantity: Number(
                    product.available_quantity ??
                    product.quantity ??
                    1
                ),

                unit_price: unitPrice,

                price: unitPrice,

                imei: safeText(product.imei),

                barcode: safeText(product.barcode),

                name: safeText(
                    product.name ??
                    product.product_name,
                    'Sản phẩm'
                ),
            };
        }



        function addToExchangeCart(product) {

            const trackingType =
                product.tracking_type ||
                product.type ||
                'quantity';


            /*
             |--------------------------------------------------------------------------
             | IMEI
             |--------------------------------------------------------------------------
             */
            if (trackingType === 'imei') {

                const productImeiId =
                    Number(
                        product.product_imei_id ||
                        0
                    );


                if (!productImeiId) {

                    notify(
                        'error',
                        'Thiết bị IMEI không hợp lệ.'
                    );

                    return false;
                }


                const key =
                    `imei:${productImeiId}`;


                if (exchangeCart.has(key)) {

                    notify(
                        'warning',
                        'Thiết bị đã có trong danh sách hàng đổi.'
                    );

                    return false;
                }


                const normalized =
                    normalizeExchangeProduct({
                            ...product,
                            product_imei_id: productImeiId
                        },
                        'imei'
                    );


                exchangeCart.set(
                    key, {
                        product: normalized,

                        quantity: 1
                    }
                );


                renderExchangeCart();

                return true;
            }


            /*
             |--------------------------------------------------------------------------
             | QUANTITY
             |--------------------------------------------------------------------------
             */
            const normalized =
                normalizeExchangeProduct(
                    product,
                    'quantity'
                );


            if (!normalized.product_id) {

                notify(
                    'error',
                    'Sản phẩm không hợp lệ.'
                );

                return false;
            }


            const key =
                `quantity:${normalized.product_id}`;


            const current =
                exchangeCart.get(key) ?? {
                    product: normalized,

                    quantity: 0
                };


            const maxQuantity =
                Number(
                    normalized.available_quantity ||
                    0
                );


            if (
                current.quantity + 1 >
                maxQuantity
            ) {

                notify(
                    'warning',
                    'Số lượng yêu cầu vượt tồn kho.'
                );

                return false;
            }


            current.quantity += 1;


            exchangeCart.set(
                key,
                current
            );


            renderExchangeCart();

            return true;
        }


        function addReturnItem(detailId) {

            const item =
                itemsById.get(
                    Number(detailId)
                );


            if (
                !item ||
                item.returnable_quantity <= 0
            ) {
                return;
            }


            if (
                returnCart.has(
                    item.order_detail_id
                )
            ) {
                return;
            }


            /*
             * IMEI và sản phẩm thường đều mặc định trả 1.
             */
            returnCart.set(
                item.order_detail_id,
                1
            );


            renderReturnCart();
        }
