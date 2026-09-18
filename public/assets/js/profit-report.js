(() => {
    const page = document.getElementById('profitPage');
    if (!page) return;

    const storage = document.getElementById('storageSelect');
    const period = document.getElementById('periodSelect');
    const startDate = document.getElementById('startDate');
    const endDate = document.getElementById('endDate');
    const search = page.querySelector('input[name="search"]');
    const reset = document.getElementById('btn-reset');
    const exportPdf = document.getElementById('exportPdf');
    const tableBody = document.getElementById('reportTableBody');
    const itemCount = document.getElementById('itemCount');
    const pagination = document.getElementById('pagination');
    const loader = document.getElementById('loader');
    const error = document.getElementById('profitFilterError');
    const initialStorage = storage.value;
    let rows = [];
    let currentPage = 1;
    let requestNumber = 0;
    let activeRequest;
    let searchTimer;

    const money = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 });

    function showError(message) {
        error.textContent = message || '';
        error.hidden = !message;
    }

    function filters() {
        if (!storage.value) {
            showError('Tài khoản chưa có kho để xem báo cáo.');
            return null;
        }

        const data = {
            storage_id: storage.value,
            filter: period.value,
            search: search.value.trim()
        };

        if (period.value === '6') {
            if (!startDate.value || !endDate.value) {
                showError('Vui lòng chọn cả ngày bắt đầu và ngày kết thúc.');
                return null;
            }
            if (startDate.value > endDate.value) {
                showError('Ngày bắt đầu phải trước hoặc bằng ngày kết thúc.');
                return null;
            }
            data.startDate = startDate.value;
            data.endDate = endDate.value;
        }

        showError('');
        return data;
    }

    function cell(row, value, className) {
        const td = document.createElement('td');
        td.textContent = value == null ? '' : String(value);
        if (className) td.className = className;
        row.appendChild(td);
    }

    function render() {
        tableBody.replaceChildren();
        itemCount.textContent = 'Số lượng mặt hàng: ' + rows.length;
        const pageCount = Math.max(1, Math.ceil(rows.length / 10));
        currentPage = Math.min(currentPage, pageCount);

        rows.slice((currentPage - 1) * 10, currentPage * 10).forEach(item => {
            const tr = document.createElement('tr');
            cell(tr, item.product && item.product.code);
            cell(tr, item.product && item.product.name);
            cell(tr, item.quantity, 'text-center');
            cell(tr, money.format(Number(item.revenue)), 'text-end');
            cell(tr, money.format(Number(item.cost)), 'text-end');
            cell(tr, money.format(Number(item.profit)), 'text-end');
            cell(tr, Number(item.rate).toFixed(2) + '%', 'text-end');
            tableBody.appendChild(tr);
        });

        if (rows.length === 0) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 7;
            td.className = 'text-center text-muted';
            td.textContent = 'Không có dữ liệu trong phạm vi đã chọn.';
            tr.appendChild(td);
            tableBody.appendChild(tr);
        }

        pagination.replaceChildren();
        if (pageCount <= 1) return;
        const previous = document.createElement('button');
        previous.type = 'button';
        previous.className = 'btn btn-sm btn-outline-primary';
        previous.textContent = '‹';
        previous.disabled = currentPage === 1;
        previous.setAttribute('aria-label', 'Trang trước');
        previous.addEventListener('click', () => { currentPage--; render(); });
        const status = document.createElement('span');
        status.className = 'mx-2';
        status.textContent = 'Trang ' + currentPage + ' / ' + pageCount;
        const next = document.createElement('button');
        next.type = 'button';
        next.className = 'btn btn-sm btn-outline-primary';
        next.textContent = '›';
        next.disabled = currentPage === pageCount;
        next.setAttribute('aria-label', 'Trang sau');
        next.addEventListener('click', () => { currentPage++; render(); });
        pagination.append(previous, status, next);
    }

    async function load() {
        const data = filters();
        if (!data) {
            if (activeRequest) activeRequest.abort();
            requestNumber++;
            rows = [];
            render();
            loader.style.display = 'none';
            if (exportPdf) exportPdf.disabled = true;
            return;
        }
        if (activeRequest) activeRequest.abort();
        activeRequest = new AbortController();
        const thisRequest = ++requestNumber;
        loader.style.display = 'block';
        if (exportPdf) exportPdf.disabled = true;

        try {
            const response = await fetch(page.dataset.reportUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': page.dataset.csrf
                },
                body: new URLSearchParams(data),
                signal: activeRequest.signal
            });
            const result = await response.json();
            if (!response.ok) {
                throw new Error(
                    result.message ||
                    (response.status === 404 ? 'Kho không thuộc phạm vi quản lý.' : 'Không thể tải báo cáo.')
                );
            }
            if (thisRequest !== requestNumber) return;
            rows = Array.isArray(result.product) ? result.product : [];
            currentPage = 1;
            render();
        } catch (failure) {
            if (failure.name === 'AbortError' || thisRequest !== requestNumber) return;
            rows = [];
            render();
            showError(failure.message || 'Không thể tải báo cáo.');
        } finally {
            if (thisRequest === requestNumber) {
                loader.style.display = 'none';
                if (exportPdf) exportPdf.disabled = false;
            }
        }
    }

    storage.addEventListener('change', load);
    period.addEventListener('change', () => {
        if (period.value !== '6') {
            startDate.value = '';
            endDate.value = '';
        }
        load();
    });
    [startDate, endDate].forEach(input => input.addEventListener('change', () => {
        period.value = '6';
        load();
    }));
    search.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(load, 300);
    });
    reset.addEventListener('click', () => {
        storage.value = initialStorage;
        period.value = 'all';
        startDate.value = '';
        endDate.value = '';
        search.value = '';
        load();
    });

    if (exportPdf) {
        exportPdf.addEventListener('click', () => {
            const data = filters();
            if (!data) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = page.dataset.pdfUrl;
            form.hidden = true;
            Object.entries({ ...data, _token: page.dataset.csrf }).forEach(([key, value]) => {
                const input = document.createElement('input');
                input.name = key;
                input.value = value;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
            form.remove();
        });
    }

    load();
})();