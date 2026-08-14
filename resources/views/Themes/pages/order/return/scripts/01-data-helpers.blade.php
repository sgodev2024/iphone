        /*
         |--------------------------------------------------------------------------
         | DATA
         |--------------------------------------------------------------------------
         */
        const returnItemsRaw = @json($returnItems);
        const orderSummary = @json($summary);
        const saveReturnUrl = @json(route('staff.orders.returns.store', $order -> id));
        const fullyReturned = @json((bool) $isFullyReturned);


        /*
         |--------------------------------------------------------------------------
         | HELPERS
         |--------------------------------------------------------------------------
         */

        const exchangeProductSearch =
            document.querySelector('#exchangeProductSearch');

        const exchangeProductPopup =
            document.querySelector('#exchangeProductPopup');

        const exchangeProductList =
            document.querySelector('#exchangeProductList');

        const exchangeBarcodeInput =
            document.querySelector('#exchangeBarcodeInput');

        const exchangeBarcodeFeedback =
            document.querySelector('#exchangeBarcodeFeedback');

        const exchangeBarcodeAddBtn =
            document.querySelector('#exchangeBarcodeAddBtn');


        const exchangeProductResultState =
            new Map();


        let exchangeBarcodeResolving = false;

        let exchangeLastBarcode = '';

        let exchangeLastBarcodeAt = 0;


        function safeText(value, fallback = '') {
            const text =
                String(value ?? '').trim();

            return text === '' ?
                fallback :
                text;
        }


        function debounce(fn, delay = 300) {

            let timer;

            return function(...args) {

                clearTimeout(timer);

                timer = setTimeout(
                    () => fn.apply(this, args),
                    delay
                );
            };
        }

        const money = value =>
            `${Math.round(Number(value) || 0).toLocaleString('vi-VN')} VND`;

        const escapeHtml = value =>
            String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            } [char]));


        function notify(icon, message) {
            if (typeof Toast !== 'undefined' && Toast?.fire) {
                Toast.fire({
                    icon,
                    title: message
                });

                return;
            }

            alert(message);
        }


        function parseMoney(value) {
            const normalized = String(value ?? '')
                .replace(/[^\d]/g, '');

            return Number(normalized) || 0;
        }
