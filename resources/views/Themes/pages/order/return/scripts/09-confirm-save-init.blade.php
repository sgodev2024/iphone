        /*
         |--------------------------------------------------------------------------
         | CONFIRM
         |--------------------------------------------------------------------------
         */
        async function confirmReturn() {

            const summary =
                currentSummary();


            const finalText =
                summary.additionalPayment > 0 ?
                `
                            Khách trả thêm:
                            <strong>
                                ${money(
                                    summary
                                        .additionalPayment
                                )}
                            </strong>
                        ` :
                `
                            Hoàn khách:
                            <strong>
                                ${money(
                                    summary
                                        .refundAmount
                                )}
                            </strong>
                        `;


            if (
                typeof Swal !==
                'undefined'
            ) {

                const result =
                    await Swal.fire({
                        title: 'Xác nhận trả hàng?',
                        html: `
                                <div
                                    class="
                                        text-start
                                        mt-2
                                    "
                                >
                                    <div class="mb-2">
                                        Giá trị hàng trả:
                                        <strong>
                                            ${money(
                                                summary
                                                    .returnAmount
                                            )}
                                        </strong>
                                    </div>

                                    <div class="mb-2">
                                        Phí trả hàng:
                                        <strong>
                                            ${money(
                                                summary
                                                    .feeAmount
                                            )}
                                        </strong>
                                    </div>

                                    <div>
                                        ${finalText}
                                    </div>
                                </div>
                            `,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Xác nhận trả',
                        cancelButtonText: 'Hủy'
                    });


                return result.isConfirmed;
            }


            return window.confirm(
                'Xác nhận trả hàng?'
            );
        }


        /*
         |--------------------------------------------------------------------------
         | SAVE
         |--------------------------------------------------------------------------
         */
        $('#saveReturnBtn')
            .on(
                'click',
                async function() {

                    if (fullyReturned) {
                        return;
                    }


                    if (
                        returnCart.size === 0
                    ) {

                        notify(
                            'warning',
                            'Vui lòng chọn ít nhất một sản phẩm cần trả.'
                        );

                        return;
                    }


                    const confirmed =
                        await confirmReturn();


                    if (!confirmed) {
                        return;
                    }


                    const button =
                        $(this);


                    const originalText =
                        button.html();


                    button
                        .prop(
                            'disabled',
                            true
                        )
                        .html(`
                                <span
                                    class="
                                        spinner-border
                                        spinner-border-sm
                                        me-1
                                    "
                                ></span>
                                Đang lưu...
                            `);


                    const payload = {

                        return_items: Array.from(
                                returnCart.entries()
                            )
                            .map(
                                ([
                                    detailId,
                                    quantity
                                ]) => ({

                                    order_detail_id: Number(
                                        detailId
                                    ),

                                    quantity: Number(
                                        quantity
                                    )
                                })
                            ),


                        new_items: Array.from(
                                exchangeCart.values()
                            )
                            .map(
                                item => ({

                                    tracking_type: item.product
                                        .tracking_type,

                                    product_id: Number(
                                        item.product
                                        .product_id
                                    ),

                                    product_imei_id: item.product
                                        .tracking_type ===
                                        'imei' ?
                                        Number(
                                            item.product
                                            .product_imei_id
                                        ) : null,

                                    quantity: Number(
                                        item.quantity
                                    ),

                                    unit_price: Number(
                                        item.product
                                        .unit_price
                                    )
                                })
                            ),


                        fee_amount: parseMoney(
                            $('#returnFeeInput')
                            .val()
                        ),


                        note: $('#returnNote')
                            .val()
                            .trim()
                    };


                    $.ajax({

                        url: saveReturnUrl,

                        method: 'POST',

                        contentType: 'application/json',

                        data: JSON.stringify(
                            payload
                        ),

                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },


                        success: function(res) {

                            button
                                .html(`
                                            <i
                                                class="
                                                    fa-solid
                                                    fa-check
                                                    me-1
                                                "
                                            ></i>
                                            Đã lưu phiếu trả
                                        `);


                            notify(
                                'success',
                                res.message ||
                                'Trả hàng thành công.'
                            );


                            /*
                             * Reload để:
                             *
                             * - cập nhật số đã trả;
                             * - cập nhật số còn trả;
                             * - nếu trả hết chuyển
                             *   sang chế độ chỉ xem.
                             */
                            setTimeout(
                                function() {
                                    window
                                        .location
                                        .reload();
                                },
                                700
                            );
                        },


                        error: function(xhr) {

                            button
                                .prop(
                                    'disabled',
                                    false
                                )
                                .html(
                                    originalText
                                );


                            const message =
                                xhr.responseJSON
                                ?.message ||
                                'Không thể thực hiện trả hàng.';


                            notify(
                                'error',
                                message
                            );
                        }
                    });
                }
            );


        /*
         |--------------------------------------------------------------------------
         | INITIAL RENDER
         |--------------------------------------------------------------------------
         */
        renderReturnCart();
