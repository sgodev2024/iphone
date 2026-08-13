(function (window, $) {
    'use strict';

    window.initializeDebtDateRangePicker = function (options) {
        const dateInput = $(options.inputSelector);
        const initialStart = moment(options.initialStart, 'YYYY-MM-DD', true);
        const initialEnd = moment(options.initialEnd, 'YYYY-MM-DD', true);
        const today = moment(options.today, 'YYYY-MM-DD', true).startOf('day');

        dateInput.daterangepicker({
            startDate: initialStart,
            endDate: initialEnd,
            autoUpdateInput: false,
            locale: {
                format: 'DD/MM/YYYY',
                separator: ' - ',
                applyLabel: 'Áp dụng',
                cancelLabel: 'Hủy',
                fromLabel: 'Từ',
                toLabel: 'Đến',
                customRangeLabel: 'Tùy chọn',
                daysOfWeek: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
                monthNames: [
                    'Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6',
                    'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'
                ],
                firstDay: 1
            },
            ranges: {
                'Hôm nay': [today.clone(), today.clone()],
                'Ngày mai': [today.clone().add(1, 'day'), today.clone().add(1, 'day')],
                'Tuần này': [today.clone().startOf('isoWeek'), today.clone().endOf('isoWeek')],
                'Tuần sau': [
                    today.clone().add(1, 'week').startOf('isoWeek'),
                    today.clone().add(1, 'week').endOf('isoWeek')
                ],
                'Tháng này': [today.clone().startOf('month'), today.clone().endOf('month')],
                'Tháng sau': [
                    today.clone().add(1, 'month').startOf('month'),
                    today.clone().add(1, 'month').endOf('month')
                ]
            }
        });

        const picker = dateInput.data('daterangepicker');
        let appliedStart = initialStart.clone();
        let appliedEnd = initialEnd.clone();
        let previewStart = appliedStart.clone();
        let previewEnd = appliedEnd.clone();

        function dateText(startDate, endDate) {
            const startText = startDate.format('DD/MM/YYYY');

            return endDate
                ? `${startText} - ${endDate.format('DD/MM/YYYY')}`
                : startText;
        }

        function renderPreview(activePicker) {
            if (activePicker.startDate) {
                previewStart = activePicker.startDate.clone();
                previewEnd = activePicker.endDate ? activePicker.endDate.clone() : null;
                dateInput.val(dateText(previewStart, previewEnd));
            }
        }

        function renderAppliedRange() {
            dateInput.val(dateText(appliedStart, appliedEnd));
        }

        function syncHiddenDates() {
            $(options.fromSelector).val(appliedStart.format('YYYY-MM-DD'));
            $(options.toSelector).val(appliedEnd.format('YYYY-MM-DD'));
        }

        // daterangepicker 3.1 has no pre-Apply selection event. Its public
        // date setters mirror the picker state in the visible input immediately.
        const originalSetStartDate = picker.setStartDate.bind(picker);
        const originalSetEndDate = picker.setEndDate.bind(picker);
        const originalUpdateView = picker.updateView.bind(picker);

        picker.setStartDate = function (date) {
            originalSetStartDate(date);
            if (this.isShowing) {
                renderPreview(this);
            }
        };

        picker.setEndDate = function (date) {
            originalSetEndDate(date);
            if (this.isShowing) {
                renderPreview(this);
            }
        };

        // Calendar cell clicks update startDate/endDate directly and finish by
        // calling updateView, so mirror that path as well as the public setters.
        picker.updateView = function () {
            originalUpdateView();
            if (this.isShowing) {
                renderPreview(this);
            }
        };

        // daterangepicker binds its date selection on mousedown. Registering
        // after initialization lets this listener read the newly selected state.
        picker.container.on('mousedown.debtDatePreview', 'td.available', function () {
            renderPreview(picker);
        });

        renderAppliedRange();

        dateInput.on('show.daterangepicker', function (event, activePicker) {
            previewStart = appliedStart.clone();
            previewEnd = appliedEnd.clone();
            activePicker.setStartDate(appliedStart.clone());
            activePicker.setEndDate(appliedEnd.clone());
            renderAppliedRange();
        });

        dateInput.on('outsideClick.daterangepicker cancel.daterangepicker', function () {
            previewStart = appliedStart.clone();
            previewEnd = appliedEnd.clone();
            renderAppliedRange();
        });

        dateInput.on('apply.daterangepicker', function (event, activePicker) {
            appliedStart = activePicker.startDate.clone();
            appliedEnd = activePicker.endDate.clone();
            syncHiddenDates();
            renderAppliedRange();
            document.querySelector(options.formSelector).submit();
        });

        $(options.formSelector).on('submit', function () {
            syncHiddenDates();
        });
    };
})(window, window.jQuery);
