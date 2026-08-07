const dataTables = (
    api,
    {
        filters = {},
        isOperation = true,
        hasCheckbox = true,
        hasDateRange = false,
        hasDtControl = false,
        fixedColumns = null,
        scrollX = false,
        onInitComplete = null,
        onDrawCallback = null,
        tableId = "#myTable",
    } = {}
) => {
    const $table = $(tableId);
    $table.empty(); // clear old headers if re-render

    let start = moment();
    let end = moment().add(1, "month");

    // Generate <thead>
    let thead = "<thead><tr>";
    if (hasDtControl) thead += "<th></th>";

    if (hasCheckbox)
        thead +=
            '<th><input type="checkbox" id="checkedAll" class="form-check-input" /></th>';
    thead += columns
        .filter((col) => col.className !== "dt-control")
        .map((col) => `<th>${col.title || ""}</th>`)
        .join("");
    if (isOperation) thead += "<th>HÀNH ĐỘNG</th>";
    thead += "</tr></thead>";
    $table.append(thead);

    // Build columns
    const baseColumns = columns.filter((col) => col.className !== "dt-control");
    const finalColumns = [];

    if (hasDtControl) {
        finalColumns.push({
            className: "dt-control",
            orderable: false,
            data: null,
            defaultContent: "",
        });
    }

    if (hasCheckbox) {
        finalColumns.push({
            data: "checkbox",
            name: "checkbox",
            orderable: false,
            searchable: false,
            width: "5px",
            className: "text-center",
        });
    }

    finalColumns.push(...baseColumns);

    if (isOperation) {
        finalColumns.push({
            data: "operations",
            name: "operations",
            title: "HÀNH ĐỘNG",
            orderable: false,
            searchable: false,
            className: "text-center",
            width: "10%",
        });
    }

    // DataTable options
    const options = {
        processing: true,
        serverSide: true,
        ajax: {
            url: api,
            data: function (d) {
                Object.keys(filters).forEach((key) => {
                    const val = $(`#filter-${key}`).val();
                    if (val) d[key] = val;
                });

                const dateRange = $("#dateRangePicker").val();

                if (dateRange) {
                    const [startDate, endDate] = dateRange.split(" - ");
                    d.start_date = moment(startDate, "DD/MM/YYYY").format(
                        "YYYY-MM-DD"
                    );
                    d.end_date = moment(endDate, "DD/MM/YYYY").format(
                        "YYYY-MM-DD"
                    );
                } else {
                    d.start_date = start.format("YYYY-MM-DD");
                    d.end_date = end.format("YYYY-MM-DD");
                }
            },
        },
        columns: finalColumns,
        order: [],
        createdRow: function (row, data) {
            $(row).attr("data-id", data.id);
        },
        initComplete: function () {
            if (typeof onInitComplete === "function")
                onInitComplete(this.api());
        },
        drawCallback: function () {
            if (typeof onDrawCallback === "function")
                onDrawCallback(this.api());
        },
        layout: {
            topEnd: {
                search: {
                    placeholder: "Tìm kiếm...",
                },
            },
        },
        language: {
            lengthMenu: "Hiển thị _MENU_ bản ghi mỗi trang",
            zeroRecords: "Không tìm thấy kết quả phù hợp",
            info: "Hiển thị _START_ đến _END_ trong tổng số _TOTAL_ bản ghi",
            infoEmpty: "Không có bản ghi nào",
            infoFiltered: "(lọc từ tổng số _MAX_ bản ghi)",
            search: "Tìm kiếm:",
            paginate: {
                first: "Đầu",
                last: "Cuối",
                next: "Sau",
                previous: "Trước",
            },
        },
    };

    if (scrollX) {
        options.scrollX = true;
        options.scrollCollapse = true;
    }

    if (fixedColumns) {
        options.fixedColumns = {
            leftColumns: fixedColumns.left || 0,
            rightColumns: fixedColumns.right || 0,
        };
    }

    const table = $table.DataTable(options);

    const targetDiv = $(".dt-layout-cell.dt-layout-start .dt-length");

    let _html = `
        <div id="actionBox" class="d-none dropdown">
            <button
                class="btn btn-sm py-1 btn-light dropdown-toggle border"
                type="button"
                id="bulkActionDropdown"
                data-bs-toggle="dropdown"
                aria-expanded="false"
            >
                Chọn hành động
            </button>
            <ul class="dropdown-menu" aria-labelledby="bulkActionDropdown">
                <li><a class="dropdown-item bulk-action-item" href="#" data-action="delete">Xóa đã chọn</a></li>
                <li><a class="dropdown-item bulk-action-item" href="#" data-action="change-status">Thay đổi trạng thái</a></li>
            </ul>
        </div>

        `;

    targetDiv.after(_html);

    $(document).on("change", "#checkedAll", function () {
        const isChecked = $(this).is(":checked");
        $(".row-checkbox").prop("checked", isChecked);
        toggleActionBox();
    });

    $(document).on("change", ".row-checkbox", function () {
        const all = $table.find(".row-checkbox").length;
        const checked = $table.find(".row-checkbox:checked").length;

        // Nếu tất cả được chọn thì check lại #selectAll
        $("#checkedAll").prop("checked", all === checked);

        // Hiển thị action box nếu có ít nhất 1 dòng được chọn
        toggleActionBox();
    });

    function toggleActionBox() {
        const anyChecked = $table.find(".row-checkbox:checked").length > 0;
        if (anyChecked) {
            $("#actionBox").removeClass("d-none");
        } else {
            $("#actionBox").addClass("d-none");
        }
    }

    // Date range filter
    if (hasDateRange) {
        const $target = $(".dt-layout-cell.dt-layout-start .dt-length");
        const datePickerHtml = `
            <div class="d-flex align-items-center ms-2">
                <input type="text" id="dateRangePicker" name="date_range" class="form-control form-control-sm" placeholder="Chọn khoảng ngày" />
            </div>`;
        $target.after(datePickerHtml);

        $("#dateRangePicker").daterangepicker({
            startDate: start,
            endDate: end,
            autoUpdateInput: true,
            locale: {
                format: "DD/MM/YYYY",
                cancelLabel: "Hủy",
                applyLabel: "Áp dụng",
                customRangeLabel: "Tùy chọn",
                daysOfWeek: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"],
                monthNames: [
                    "Tháng 1",
                    "Tháng 2",
                    "Tháng 3",
                    "Tháng 4",
                    "Tháng 5",
                    "Tháng 6",
                    "Tháng 7",
                    "Tháng 8",
                    "Tháng 9",
                    "Tháng 10",
                    "Tháng 11",
                    "Tháng 12",
                ],
                firstDay: 1,
            },
            ranges: {
                "Hôm nay": [moment(), moment()],
                "Ngày mai": [moment().add(1, "days"), moment().add(1, "days")],
                "Tuần này": [moment().startOf("week"), moment().endOf("week")],
                "Tuần sau": [
                    moment().add(1, "week").startOf("week"),
                    moment().add(1, "week").endOf("week"),
                ],
                "Tháng này": [
                    moment().startOf("month"),
                    moment().endOf("month"),
                ],
                "Tháng sau": [
                    moment().add(1, "month").startOf("month"),
                    moment().add(1, "month").endOf("month"),
                ],
            },
        });

        $("#dateFilter").val(
            start.format("DD/MM/YYYY") + " - " + end.format("DD/MM/YYYY")
        );

        $("#dateRangePicker").on("cancel.daterangepicker", function () {
            $(this).val("");
            table.ajax.reload();
        });

        $("#dateRangePicker").on(
            "apply.daterangepicker",
            function (ev, picker) {
                $(this).val(
                    `${picker.startDate.format(
                        "DD/MM/YYYY"
                    )} - ${picker.endDate.format("DD/MM/YYYY")}`
                );
                table.ajax.reload();
            }
        );
    }

    // Reload on filter change
    Object.keys(filters).forEach((key) => {
        $(`#filter-${key}`).on("change", () => table.ajax.reload());
    });

    return table;
};

const initBulkAction = (modelName) => {
    $(document).on("click", ".bulk-action-item", function (e) {
        e.preventDefault();

        const action = $(this).data("action");

        const ids = $(".row-checkbox:checked")
            .map(function () {
                return $(this).val();
            })
            .get();

        if (!action || ids.length === 0) {
            datgin.warning("Vui lòng chọn hành động và ít nhất 1 bản ghi.");
            $(this).val(""); // reset lại select
            return;
        }

        let confirmText = "Bạn có chắc chắn muốn thực hiện hành động này?";

        if (action === "delete") {
            confirmText = "Bạn có chắc chắn muốn xóa các mục đã chọn?";
        } else if (action === "change-status") {
            confirmText = "Bạn có chắc chắn muốn thay đổi trạng thái đã chọn?";
        }

        Swal.fire({
            title: "Xác nhận hành động",
            text: confirmText,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Xác nhận",
            cancelButtonText: "Hủy bỏ",
            customClass: {
                confirmButton: "btn btn-primary me-2",
                cancelButton: "btn  btn-danger",
            },
            buttonsStyling: false,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/admin/handle-bulk-action",
                    method: "POST",
                    data: {
                        type: action,
                        model: modelName,
                        ids: ids,
                    },
                    success: function (res) {
                        $("#checkedAll, .row-checkbox").prop("checked", false);
                        $("table").DataTable().ajax.reload();
                        datgin.success(res.message);
                        $("#bulkAction").val("");
                        $("#actionBox").addClass("d-none");
                    },
                    error: function (xhr) {
                        datgin.error(
                            xhr.responseJSON.message ||
                                "Đã có lỗi xảy ra, vui lòng thử lại sau!"
                        );
                    },
                });
            } else {
                $("#bulkAction").val(""); // reset nếu hủy
            }
        });
    });
};

const initStatusToggle = ({
    model,
    successCallback = null,
    errorCallback = null,
}) => {
    $(document).on("change", '.switch input[type="checkbox"]', function () {
        const checkbox = $(this);
        const newChecked = checkbox.prop("checked");
        const id = checkbox.closest(".switch").data("id");

        Swal.fire({
            title: "Bạn có chắc chắn?",
            text: "Hành động này không thể hoàn tác!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Vâng, thay đổi!",
            cancelButtonText: "Hủy",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/handle-bulk-action",
                    type: "POST",
                    data: {
                        model: model,
                        type: "change-status",
                        ids: id,
                    },
                    success: function (res) {
                        datgin.success(res.message);
                        if (typeof successCallback === "function")
                            successCallback(res, id, newChecked);
                    },
                    error: function (xhr) {
                        checkbox.prop("checked", !newChecked); // Quay về nếu lỗi
                        datgin.error(
                            xhr.responseJSON?.message ||
                                "Đã có lỗi xảy ra, vui lòng thử lại sau!"
                        );
                        if (typeof errorCallback === "function")
                            errorCallback(xhr, id, newChecked);
                    },
                });
            } else {
                checkbox.prop("checked", !newChecked);
            }
        });
    });
};

const handleDestroy = (model) => {
    $(document).on("click", ".btn-delete", function () {
        const id = $(this).data("id");

        Swal.fire({
            title: "Bạn có chắc chắn?",
            text: "Hành động này không thể hoàn tác!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Vâng, xóa ngay!",
            cancelButtonText: "Hủy",
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "/admin/handle-bulk-action",
                    type: "POST",
                    data: {
                        type: "delete",
                        model,
                        ids: [id],
                    },
                    success: function (res) {
                        $("table").DataTable().ajax.reload();
                        datgin.success(res.message);
                    },
                    error: function (xhr) {
                        datgin.error(
                            xhr.responseJSON.message ||
                                "Đã có lỗi xảy ra, vui lòng thử lại sau!"
                        );
                    },
                });
            }
        });
    });
};
