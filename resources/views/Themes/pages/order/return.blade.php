@extends('Themes.layout_staff.app')

@section('content')
    @include('Themes.pages.order.return.partials.styles')

    <div class="container-fluid py-2 return-page">
        @include('Themes.pages.order.return.partials.header')

        <div class="row g-2">
            <div class="col-lg-9">
                @include('Themes.pages.order.return.partials.original-products')
                @include('Themes.pages.order.return.partials.return-cart')
                @include('Themes.pages.order.return.partials.exchange-card')
            </div>

            <div class="col-lg-3">
                <div class="return-side">
                    @include('Themes.pages.order.return.partials.order-info')
                    @include('Themes.pages.order.return.partials.return-form')
                </div>
            </div>
        </div>
    </div>
    @include('Themes.pages.order.return.partials.source-order-modal')
@endsection

@push('script')
<script>
    $(function() {
        @include('Themes.pages.order.return.scripts.01-data-helpers')
        @include('Themes.pages.order.return.scripts.02-return-calculations')
        @include('Themes.pages.order.return.scripts.03-cart-state-core')
        @include('Themes.pages.order.return.scripts.04-exchange-barcode-search')
        @include('Themes.pages.order.return.scripts.05-exchange-product-results')
        @include('Themes.pages.order.return.scripts.06-exchange-cart-render')
        @include('Themes.pages.order.return.scripts.07-return-cart')
        @include('Themes.pages.order.return.scripts.08-summary-events')
        @include('Themes.pages.order.return.scripts.09-confirm-save-init')
    });
</script>
@endpush
