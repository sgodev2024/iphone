<style>
    .return-page {
        min-height: 100vh;
    }

    .return-page .card {
        border: 0;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .06);
    }

    .return-page-header {
        gap: 1rem;
    }

    .return-order-code {
        font-weight: 700;
    }

    .return-product-name {
        font-weight: 600;
        color: #1f2937;
    }

    .return-product-meta {
        color: #6b7280;
        font-size: .82rem;
        line-height: 1.45;
    }

    .return-stock-number {
        min-width: 48px;
        display: inline-block;
        text-align: center;
    }

    .return-cart-empty {
        padding: 2rem 1rem;
        text-align: center;
        color: #6b7280;
    }

    .return-cart-row {
        display: grid;
        grid-template-columns:
            minmax(0, 1fr) 120px 125px 38px;
        align-items: center;
        gap: .75rem;
        padding: .8rem 0;
        border-bottom: 1px solid #eee;
    }

    .return-cart-row:last-child {
        border-bottom: 0;
    }

    .return-cart-info {
        min-width: 0;
    }

    .return-cart-quantity {
        width: 100%;
        max-width: 120px;
    }

    .return-cart-amount {
        min-width: 100px;
        text-align: right;
        font-weight: 600;
    }

    .return-cart-remove {
        width: 34px;
        height: 34px;
        padding: 0;
    }

    .return-summary {
        border-top: 1px dashed #d1d5db;
        background: #fff;
    }

    .return-summary-line {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .45rem;
    }

    .return-summary-line:last-child {
        margin-bottom: 0;
    }

    .return-summary-final {
        padding-top: .6rem;
        margin-top: .6rem;
        border-top: 1px solid #e5e7eb;
        font-size: 1.05rem;
        font-weight: 700;
    }

    .return-side-card .info-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: .35rem 0;
    }

    .return-side-card .info-label {
        color: #6b7280;
    }

    .return-side-card .info-value {
        text-align: right;
        font-weight: 500;
        word-break: break-word;
    }

    .return-save-btn {
        min-height: 42px;
        font-weight: 700;
    }

    .return-fee-input {
        text-align: right;
    }

    .return-item-disabled {
        opacity: .65;
    }

    .return-preview-note {
        font-size: .78rem;
        color: #6b7280;
    }

    @media (min-width: 992px) {
        .return-side {
            position: sticky;
            top: 1rem;
            align-self: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .return-page {
            padding: 4.75rem .75rem .75rem !important;
        }

        .return-page-header {
            align-items: flex-start !important;
            flex-direction: column;
        }

        .return-page-header .return-header-actions {
            width: 100%;
        }

        .return-page-header .return-header-actions .btn {
            width: 100%;
        }

        .return-products-table thead {
            display: none;
        }

        .return-products-table,
        .return-products-table tbody,
        .return-products-table tr,
        .return-products-table td {
            display: block;
            width: 100%;
        }

        .return-products-table tr {
            padding: .8rem;
            margin-bottom: .75rem;
            border: 1px solid #e5e7eb !important;
            border-radius: .5rem;
            background: #fff;
        }

        .return-products-table td {
            padding: .25rem 0;
            border: 0;
        }

        .return-cart-row {
            grid-template-columns: minmax(0, 1fr) 38px;
            gap: .5rem;
        }

        .return-cart-info {
            grid-column: 1;
        }

        .return-cart-remove {
            grid-column: 2;
            grid-row: 1;
        }

        .return-cart-quantity {
            grid-column: 1;
            grid-row: 2;
            max-width: 100%;
        }

        .return-cart-amount {
            grid-column: 2;
            grid-row: 2;
            min-width: 0;
        }
    }

    .exchange-search-wrapper {
        position: relative;
    }

    .exchange-search-popup {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;

        z-index: 1060;

        display: none;

        max-height: 320px;
        overflow-y: auto;

        margin-top: .25rem;

        background: #fff;

        border: 1px solid rgba(0, 0, 0, .12);
        border-radius: .375rem;

        box-shadow: 0 8px 20px rgba(15, 23, 42, .12);
    }

    .exchange-product-row {
        cursor: pointer;
    }

    .exchange-product-row:hover {
        background: #f6f9ff;
    }

    .exchange-cart-row {
        display: grid;

        grid-template-columns:
            minmax(0, 1fr) 145px 90px 120px 38px;

        gap: .75rem;

        align-items: end;

        padding: .75rem 0;

        border-bottom: 1px solid #eee;
    }

    .exchange-cart-row:last-child {
        border-bottom: 0;
    }

    .exchange-cart-product {
        min-width: 0;
    }

    .exchange-cart-name {
        font-weight: 600;
    }

    .exchange-cart-meta {
        color: #6b7280;
        font-size: .8rem;
    }

    .exchange-cart-price input {
        text-align: right;
    }

    .exchange-cart-qty input {
        text-align: center;
    }

    .exchange-cart-line-total {
        text-align: right;
        font-weight: 600;
        padding-bottom: .45rem;
    }

    .exchange-cart-remove {
        width: 34px;
        height: 34px;
        padding: 0;
    }


    @media (max-width: 767.98px) {

        .exchange-cart-row {
            grid-template-columns:
                minmax(0, 1fr) 38px;

            gap: .5rem;
        }

        .exchange-cart-product {
            grid-column: 1;
        }

        .exchange-cart-remove {
            grid-column: 2;
            grid-row: 1;
        }

        .exchange-cart-price,
        .exchange-cart-qty,
        .exchange-cart-line-total {
            grid-column: 1 / 3;
        }

        .exchange-cart-line-total {
            text-align: left;
        }
    }
</style>
