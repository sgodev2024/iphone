            {{-- Sản phẩm đơn gốc --}}
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            Sản phẩm trong đơn
                        </h5>

                        <span class="text-muted small">
                            Chọn sản phẩm khách muốn trả
                        </span>
                    </div>
                </div>

                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 return-products-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th class="text-end">Đơn giá</th>
                                    <th class="text-center">Đã mua</th>
                                    <th class="text-center">Đã trả</th>
                                    <th class="text-center">Còn trả</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($returnItems as $item)
                                <tr
                                    class="{{ !$item['can_return'] ? 'return-item-disabled' : '' }}">
                                    <td>
                                        <div class="return-product-name">
                                            {{ $item['product_name'] }}
                                        </div>

                                        <div class="return-product-meta">
                                            @if (!empty($item['product_code']))
                                            Mã:
                                            {{ $item['product_code'] }}
                                            @endif

                                            @if (!empty($item['imei']))
                                            <div>
                                                IMEI:
                                                <strong>
                                                    {{ $item['imei'] }}
                                                </strong>
                                            </div>
                                            @endif

                                            @if ($item['tracking_type'] === 'imei')
                                            <span class="badge bg-info text-dark mt-1">
                                                Thiết bị IMEI
                                            </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="text-end">
                                        {{ number_format(
                                                    $item['unit_price'],
                                                    0,
                                                    ',',
                                                    '.'
                                                ) }}
                                        VND
                                    </td>

                                    <td class="text-center">
                                        <span class="return-stock-number">
                                            {{ $item['original_quantity'] }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span class="return-stock-number">
                                            {{ $item['returned_quantity'] }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        @if ($item['returnable_quantity'] > 0)
                                        <span class="badge bg-light text-dark border">
                                            {{ $item['returnable_quantity'] }}
                                        </span>
                                        @else
                                        <span class="badge bg-secondary">
                                            0
                                        </span>
                                        @endif
                                    </td>

                                    <td class="text-end">
                                        @if ($item['can_return'] && !$isFullyReturned)
                                        <button
                                            type="button"
                                            class="btn btn-outline-primary btn-sm return-add-btn"
                                            data-detail-id="{{ $item['order_detail_id'] }}">
                                            <i class="fa-solid fa-rotate-left me-1"></i>
                                            Trả hàng
                                        </button>
                                        @else
                                        <span class="badge bg-secondary">
                                            Đã trả hết
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center text-muted py-4">
                                        Đơn hàng không có sản phẩm.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
