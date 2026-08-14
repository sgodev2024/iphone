            {{-- Giỏ hàng trả --}}
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <h5 class="card-title mb-0">
                            Hàng khách trả
                        </h5>

                        <button
                            id="clearReturnCartBtn"
                            type="button"
                            class="btn btn-outline-danger btn-sm"
                            @disabled($isFullyReturned)>
                            Xóa danh sách
                        </button>
                    </div>
                </div>

                <div class="card-body">

                    <div id="returnCartBody"></div>

                    <div id="returnCartEmpty" class="return-cart-empty">
                        Chưa chọn sản phẩm nào để trả.
                    </div>


                    {{-- Summary --}}
                    <div class="return-summary mt-3 pt-3">

                        <div class="return-summary-line">
                            <span>Giá trị hàng theo giá bán</span>

                            <span id="returnGrossPreview">
                                0 VND
                            </span>
                        </div>

                        <div class="return-summary-line text-muted">
                            <span>Giảm giá của đơn gốc phân bổ</span>

                            <span id="returnDiscountPreview">
                                -0 VND
                            </span>
                        </div>

                        <div class="return-summary-line fw-semibold">
                            <span>Giá trị hàng trả</span>

                            <span id="returnAmountPreview">
                                0 VND
                            </span>
                        </div>

                        <div class="return-summary-line">
                            <span>Phí trả hàng</span>

                            <span id="returnFeePreview">
                                -0 VND
                            </span>
                        </div>


                        <div
                            id="refundPreviewRow"
                            class="return-summary-line return-summary-final">
                            <span>Hoàn khách</span>

                            <span
                                id="refundPreview"
                                class="text-success">
                                0 VND
                            </span>
                        </div>


                        <div
                            id="additionalPaymentPreviewRow"
                            class="return-summary-line return-summary-final d-none">
                            <span>Khách trả thêm</span>

                            <span
                                id="additionalPaymentPreview"
                                class="text-danger">
                                0 VND
                            </span>
                        </div>


                        <div class="return-preview-note mt-2">
                            Số tiền trên màn hình là giá trị dự kiến.
                            Khi lưu, hệ thống sẽ tính lại từ dữ liệu đơn hàng gốc.
                        </div>
                    </div>

                </div>
            </div>
