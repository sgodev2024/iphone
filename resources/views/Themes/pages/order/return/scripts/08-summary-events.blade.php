        /*
         |--------------------------------------------------------------------------
         | SUMMARY
         |--------------------------------------------------------------------------
         */
        function currentSummary() {

            let grossAmount = 0;
            let discountAmount = 0;
            let returnAmount = 0;


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


                grossAmount +=
                    preview.grossAmount;

                discountAmount +=
                    preview.discountAmount;

                returnAmount +=
                    preview.returnAmount;
            }


            const feeAmount =
                parseMoney(
                    $('#returnFeeInput').val()
                );


            /*
             * V1:
             *
             * settlement
             * =
             * fee - return_amount
             */
            const exchangeTotal =
                exchangeAmount();


            const settlement =
                exchangeTotal -
                returnAmount +
                feeAmount;


            const refundAmount =
                settlement < 0 ?
                Math.abs(settlement) :
                0;


            const additionalPayment =
                settlement > 0 ?
                settlement :
                0;


            return {
                grossAmount,
                discountAmount,
                returnAmount,

                exchangeAmount: exchangeTotal,

                feeAmount,
                refundAmount,
                additionalPayment
            };
        }


        function renderSummary() {
            const saveButton =
                document.querySelector(
                    '#saveReturnBtn'
                );


            if (saveButton) {

                saveButton.innerHTML =
                    exchangeCart.size > 0 ?
                    `
            <i
                class="
                    fa-solid
                    fa-arrow-right-arrow-left
                    me-1
                "
            ></i>
            Xác nhận đổi / trả
            ` :
                    `
            <i
                class="
                    fa-solid
                    fa-rotate-left
                    me-1
                "
            ></i>
            Xác nhận trả hàng
            `;
            }
            const summary =
                currentSummary();
            $('#exchangeAmountPreview')
                .text(
                    money(
                        summary.exchangeAmount
                    )
                );

            $('#returnGrossPreview')
                .text(
                    money(
                        summary.grossAmount
                    )
                );


            $('#returnDiscountPreview')
                .text(
                    '-' +
                    money(
                        summary.discountAmount
                    )
                );


            $('#returnAmountPreview')
                .text(
                    money(
                        summary.returnAmount
                    )
                );


            $('#returnFeePreview')
                .text(
                    '-' +
                    money(
                        summary.feeAmount
                    )
                );

                const isExchange =
    exchangeCart.size > 0;

const title =
    isExchange
        ? 'Xác nhận đổi / trả hàng?'
        : 'Xác nhận trả hàng?';
            /*
             * Hoàn khách
             */
            if (
                summary.additionalPayment > 0
            ) {

                $('#refundPreviewRow')
                    .addClass('d-none');


                $('#additionalPaymentPreviewRow')
                    .removeClass('d-none');


                $('#additionalPaymentPreview')
                    .text(
                        money(
                            summary
                            .additionalPayment
                        )
                    );

            } else {

                $('#additionalPaymentPreviewRow')
                    .addClass('d-none');


                $('#refundPreviewRow')
                    .removeClass('d-none');


                $('#refundPreview')
                    .text(
                        money(
                            summary.refundAmount
                        )
                    );
            }
        }


        /*
         |--------------------------------------------------------------------------
         | SOURCE PRODUCT EVENTS
         |--------------------------------------------------------------------------
         */
        $(document).on(
            'click',
            '.return-add-btn',
            function() {

                addReturnItem(
                    $(this)
                    .data(
                        'detail-id'
                    )
                );
            }
        );


        /*
         |--------------------------------------------------------------------------
         | CART EVENTS
         |--------------------------------------------------------------------------
         */
        $(document).on(
            'click',
            '.return-cart-remove',
            function() {

                removeReturnItem(
                    $(this)
                    .data(
                        'detail-id'
                    )
                );
            }
        );


        $(document).on(
            'change',
            '.return-cart-qty',
            function() {

                setReturnQuantity(
                    $(this)
                    .data(
                        'detail-id'
                    ),
                    $(this).val()
                );
            }
        );


        $('#clearReturnCartBtn')
            .on(
                'click',
                function() {

                    returnCart.clear();

                    renderReturnCart();
                }
            );


        /*
         |--------------------------------------------------------------------------
         | FEE
         |--------------------------------------------------------------------------
         */
        $('#returnFeeInput')
            .on(
                'input',
                function() {

                    /*
                     * Chỉ giữ chữ số.
                     */
                    this.value =
                        String(
                            this.value
                        ).replace(
                            /[^\d]/g,
                            ''
                        );


                    renderSummary();
                }
            )
            .on(
                'blur',
                function() {

                    const value =
                        parseMoney(
                            this.value
                        );


                    this.value =
                        value.toLocaleString(
                            'vi-VN'
                        );
                }
            )
            .on(
                'focus',
                function() {

                    const value =
                        parseMoney(
                            this.value
                        );


                    this.value =
                        value || '';
                }
            );
