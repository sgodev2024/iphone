    <script src="{{ asset('assets/js/core/jquery-3.7.1.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

    <script src="{{ asset('assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/chart.js/chart.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/chart-circle/circles.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugin/jsvectormap/world.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.22.4/sweetalert2.min.js"></script>
    <script src="{{ asset('assets/js/plugin/webfont/webfont.min.js') }}"></script>
    <script src="{{ asset('assets/js/kaiadmin.min.js') }}"></script>
    <script src="{{ asset('assets/js/kaiadmin.js') }}"></script>
    <script src="{{ asset('assets/js/setting-demo.js') }}"></script>
    <script src="{{ asset('assets/js/setting-demo2.js') }}"></script>
    <script src="{{ asset('assets/js/demo.js') }}"></script>

    <script src="{{ asset('global/js/toastr.js') }}?v={{ filemtime(public_path('global/js/toastr.js')) }}"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        })

        @php
            $types = ['success', 'error', 'info', 'warning'];
        @endphp

        @foreach ($types as $type)
            @if (session()->has($type))
                setTimeout(function() {
                    datgin.{{ $type }}(@json(session($type)));
                }, 600);
                @break
            @endif
        @endforeach

        $(function() {
            $(document).on('click', '#check-all', function() {

                let isChecked = $(this).prop('checked');

                $('.checked-item').prop('checked', isChecked)
            })

            $(document).on('click', '.checked-item', function() {
                let total = $('.checked-item').length;
                let checked = $('.checked-item:checked').length;

                $('#check-all').prop('checked', checked === total);
            })
        })

        function handleDestroy(callback, model, id) {
            let ids = $('.checked-item:checked').map((i, el) => $(el).val()).get()

            if (ids.length === 0 && id) {
                ids = [id];
            }

            if (ids.length <= 0) return datgin.warning('Vui lòng chọn ít nhất 1 bản ghi!')

            Swal.fire({
                title: "Xác nhận xóa?",
                text: "Dữ liệu sẽ bị xóa vĩnh viễn và không thể khôi phục.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Vâng, xóa ngay!",
                cancelButtonText: "Hủy"
            }).then((result) => {
                if (result.isConfirmed) {

                    $.ajax({
                        url: '/admin/bulk/delete',
                        method: 'POST',
                        data: {
                            ids,
                            model
                        },
                        success: (res) => {
                            datgin.success(res.message)
                            $('input[type="checkbox"]').prop('checked', false);

                            if (typeof callback === 'function') {
                                callback(); // load lại danh sách sau khi xóa
                            }
                        },
                        error: (xhr) => {
                            datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                        }
                    })
                }
            });
        }

        function handleChangeStatus(callback, model) {

            let ids = $('.checked-item:checked').map((i, el) => $(el).val()).get()

            if (ids.length <= 0) return datgin.warning('Vui lòng chọn ít nhất 1 bản ghi!')

            Swal.fire({
                title: "Xác nhận thay đổi trạng thái?",
                text: "Bạn có chắc chắn muốn cập nhật trạng thái cho các bản ghi đã chọn?",
                icon: "question",
                showCancelButton: true,
                confirmButtonColor: "#3085d6",
                cancelButtonColor: "#d33",
                confirmButtonText: "Vâng, cập nhật!",
                cancelButtonText: "Hủy"
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/admin/bulk/status',
                        method: 'POST',
                        data: {
                            ids,
                            model
                        },
                        success: (res) => {
                            datgin.success(res.message)
                            $('input[type="checkbox"]').prop('checked', false);

                            if (typeof callback === 'function') {
                                callback();
                            }
                        },
                        error: (xhr) => {
                            datgin.error('Đã có lỗi xảy ra. Vui lòng thử lại sau!');
                        }
                    })
                }
            });
        }

        function debounce(fn, delay = 500) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => fn.apply(this, args), delay);
            };
        }

        function previewImage(event, imgId) {
            const file = event.target.files[0];
            const reader = new FileReader();
            reader.onload = function() {
                const imgElement = document.getElementById(imgId);
                imgElement.src = reader.result;
            }
            if (file) {
                reader.readAsDataURL(file);
            }
        }

        function handleSubmit(formId, successCallback, url = null, errorCallback = null) {

            $(formId).on("submit", function(e) {
                e.preventDefault();

                const $form = $(this);

                // ✅ Validate toàn bộ form dùng formValidator
                if (
                    typeof formValidator !== "undefined" &&
                    typeof formValidator.validate === "function"
                ) {
                    if (!formValidator.validate()) {
                        $btn.prop("disabled", false).html(originalText);
                        return;
                    }
                }

                // ✅ Cập nhật dữ liệu từ CKEditor nếu có
                if (typeof CKEDITOR !== "undefined") {
                    for (const instance in CKEDITOR.instances) {
                        CKEDITOR.instances[instance].updateElement();
                    }
                }

                const formData = new FormData(this);

                // ✅ Xóa dấu chấm trong các input có class `format-price`
                $form.find(".format-price").each(function() {
                    const name = $(this).attr("name");
                    if (!name) return; // bỏ qua nếu không có name
                    const raw = $(this).val().replace(/\./g, "");
                    formData.set(name, raw); // Ghi đè vào FormData
                });

                $.ajax({
                    url: url || window.location.href,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: () => {
                        $("#loadingOverlay").show();
                    },
                    success: function(response) {
                        if (typeof successCallback === "function") {
                            successCallback(response, $form);
                        }
                    },
                    error: function(xhr) {
                        if (
                            xhr.status === 403 &&
                            xhr.getResponseHeader("Content-Type")?.includes("text/html")
                        ) {
                            document.open();
                            document.write(xhr.responseText);
                            document.close();
                            return;
                        }

                        if (typeof errorCallback === "function") {
                            errorCallback(xhr);
                        }

                        datgin?.error(
                            xhr.responseJSON?.message ||
                            "Đã có lỗi xảy ra, vui lòng thử lại sau!"
                        );
                    },
                    complete: function() {
                        $("#loadingOverlay").hide();
                    },
                });
            });

        }

        function formatNumber(amount) {
            if (!amount) return "0";

            const formatted = new Intl.NumberFormat("en-US", {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(amount);

            // Remove .00 if present
            if (formatted.endsWith(".00")) {
                return formatted.slice(0, -3);
            }

            // Remove trailing 0 if present
            if (formatted.endsWith("0")) {
                return formatted.slice(0, -1);
            }

            return formatted;
        }
    </script>

    @stack('script')
