        /*
         |--------------------------------------------------------------------------
         | NORMALIZE ITEMS
         |--------------------------------------------------------------------------
         */
        const items = returnItemsRaw.map(item => ({
            ...item,

            order_detail_id: Number(item.order_detail_id),

            product_id: Number(item.product_id),

            unit_price: Number(item.unit_price) || 0,

            original_quantity: Number(item.original_quantity) || 0,

            returned_quantity: Number(item.returned_quantity) || 0,

            returnable_quantity: Number(item.returnable_quantity) || 0,
        }));


        const itemsById = new Map(
            items.map(item => [
                item.order_detail_id,
                item
            ])
        );


        /*
         |--------------------------------------------------------------------------
         | PHÂN BỔ DISCOUNT
         |--------------------------------------------------------------------------
         |
         | Cùng nguyên tắc OrderReturnService:
         |
         | 1. Discount được phân bổ theo tỷ trọng gross của từng dòng.
         | 2. Lấy phần nguyên.
         | 3. Phần VND dư phân bổ cho remainder lớn nhất.
         |
         */
        function allocateOrderDiscount() {

            const subtotal = items.reduce(
                (sum, item) =>
                sum +
                (
                    item.unit_price *
                    item.original_quantity
                ),
                0
            );


            const orderDiscount = Math.max(
                0,
                Math.min(
                    Number(orderSummary.discount_value) || 0,
                    subtotal
                )
            );


            const allocation = new Map();

            items.forEach(item => {
                allocation.set(
                    item.order_detail_id,
                    0
                );
            });


            if (
                subtotal <= 0 ||
                orderDiscount <= 0
            ) {
                return allocation;
            }


            let allocated = 0;
            const remainders = [];


            items.forEach(item => {

                const gross =
                    item.unit_price *
                    item.original_quantity;

                const numerator =
                    orderDiscount * gross;

                const base =
                    Math.floor(
                        numerator / subtotal
                    );

                const remainder =
                    numerator % subtotal;


                allocation.set(
                    item.order_detail_id,
                    base
                );

                allocated += base;


                remainders.push({
                    id: item.order_detail_id,
                    remainder
                });
            });


            let remaining =
                orderDiscount - allocated;


            remainders.sort((a, b) => {

                if (a.remainder === b.remainder) {
                    return a.id - b.id;
                }

                return b.remainder -
                    a.remainder;
            });


            for (
                let i = 0; i < remaining &&
                i < remainders.length; i++
            ) {
                const detailId =
                    remainders[i].id;

                allocation.set(
                    detailId,
                    allocation.get(detailId) + 1
                );
            }


            return allocation;
        }


        const discountByDetail =
            allocateOrderDiscount();


        /*
         |--------------------------------------------------------------------------
         | ROUNDING LŨY KẾ
         |--------------------------------------------------------------------------
         |
         | Cùng cách backend xử lý trường hợp:
         |
         | lineNet = 100.000
         | quantity = 3
         |
         | lần 1 = 33.333
         | lần 2 = 33.333
         | lần 3 = 33.334
         |
         */
        function cumulativeAmount(
            lineNetAmount,
            quantity,
            originalQuantity
        ) {

            if (quantity <= 0) {
                return 0;
            }

            if (
                quantity >=
                originalQuantity
            ) {
                return lineNetAmount;
            }

            return Math.floor(
                (
                    lineNetAmount *
                    quantity
                ) /
                originalQuantity
            );
        }


        function calculateReturnItem(
            item,
            quantity
        ) {

            const lineGross =
                item.unit_price *
                item.original_quantity;


            const lineDiscount =
                Number(
                    discountByDetail.get(
                        item.order_detail_id
                    ) || 0
                );


            const lineNet =
                lineGross -
                lineDiscount;


            const before =
                cumulativeAmount(
                    lineNet,
                    item.returned_quantity,
                    item.original_quantity
                );


            const after =
                cumulativeAmount(
                    lineNet,
                    item.returned_quantity +
                    quantity,
                    item.original_quantity
                );


            const returnAmount =
                after - before;


            const grossAmount =
                item.unit_price *
                quantity;


            const discountAmount =
                grossAmount -
                returnAmount;


            return {
                grossAmount,
                discountAmount,
                returnAmount
            };
        }
