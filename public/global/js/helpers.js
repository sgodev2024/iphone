function submitForm(formId, successCallback, url = null, errorCallback = null) {

    $(formId).on("submit", function (e) {
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
        $form.find(".format-price").each(function () {
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
            success: function (response) {
                if (typeof successCallback === "function") {
                    successCallback(response, $form);
                }
            },
            error: function (xhr) {
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
            complete: function () {
                $("#loadingOverlay").hide();
            },
        });
    });
}

function formatDate(dateString, format = "DD-MM-YYYY") {
    if (!dateString)
        return '<small class="text-muted">Chưa cập nhật...</small>';
    return dayjs(dateString).format(format);
}

function formatToVietnameseCurrency(value) {
    value = value.replace(/[^\d]/g, "");
    if (value === "") return "";
    return new Intl.NumberFormat("vi-VN").format(value);
}

$(document).on("input", ".format-price", function () {
    let cursorPos = this.selectionStart;
    let originalLength = this.value.length;

    this.value = formatToVietnameseCurrency(this.value);

    // Giữ lại vị trí con trỏ khi nhập
    let newLength = this.value.length;
    this.setSelectionRange(
        cursorPos + (newLength - originalLength),
        cursorPos + (newLength - originalLength)
    );
});

function updateCharCount(inputSelector, maxLength) {
    // Tìm label có 'for' tương ứng với inputSelector
    const labelSelector = $('label[for="' + inputSelector.substring(1) + '"]');

    // Tạo thẻ charCountSelector và thêm vào sau label
    const charCountSelector = $("<small></small>")
        .addClass("char-count")
        .css({
            position: "absolute",
            right: "0",
            top: ".5rem",
        })
        .insertAfter(labelSelector);

    // Đặt maxlength ban đầu cho phần tử input/textarea
    $(inputSelector).attr("maxlength", maxLength);

    // Hàm cập nhật số ký tự
    $(inputSelector).on("input", function () {
        var currentLength = $(this).val().length;
        charCountSelector.text(currentLength + "/" + maxLength);

        // Kiểm tra khi đã đạt maxLength, ngừng nhập
        if (currentLength >= maxLength) {
            $(this).attr("maxlength", maxLength); // Ngừng cho phép nhập thêm ký tự
        }
    });

    // Cập nhật số ký tự ban đầu khi trang tải
    var initialLength = $(inputSelector).val().length;
    charCountSelector.text(initialLength + "/" + maxLength);
}

function generateSlug(element) {
    let text = $(element).val();

    return text
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/đ/g, "d")
        .replace(/Đ/g, "D")
        .replace(/[^a-z0-9 -]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-")
        .trim();
}
