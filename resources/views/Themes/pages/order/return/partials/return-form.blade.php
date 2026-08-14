                {{-- Thông tin phiếu trả --}}
                <div class="card return-side-card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            Phiếu trả hàng
                        </h5>
                    </div>

                    <div class="card-body">

                        <div class="mb-3">
                            <label
                                for="returnFeeInput"
                                class="form-label">
                                Phí trả hàng
                            </label>

                            <div class="input-group">
                                <input
                                    id="returnFeeInput"
                                    type="text"
                                    inputmode="numeric"
                                    class="form-control return-fee-input"
                                    placeholder="0"
                                    value="0"
                                    @disabled($isFullyReturned)>

                                <span class="input-group-text">
                                    VND
                                </span>
                            </div>

                            <div class="form-text">
                                Ví dụ: khách chịu phí trả hàng 20.000 VND.
                            </div>
                        </div>


                        <div class="mb-3">
                            <label
                                for="returnNote"
                                class="form-label">
                                Ghi chú
                            </label>

                            <textarea
                                id="returnNote"
                                class="form-control"
                                rows="3"
                                placeholder="Nhập ghi chú cho phiếu trả..."
                                @disabled($isFullyReturned)></textarea>
                        </div>


                        @if (!$isFullyReturned)
                        <div class="d-grid">
                            <button
                                type="button"
                                id="saveReturnBtn"
                                class="btn btn-success return-save-btn">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                Xác nhận trả hàng
                            </button>
                        </div>
                        @else
                        <div class="alert alert-secondary mb-0 text-center">
                            Đơn đã trả toàn bộ.
                        </div>
                        @endif

                    </div>
                </div>
