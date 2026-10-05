export function initializeDataTable({ table, columns, filters, dataSrc, createdRow }) {
    const start = () => {
        const element = typeof table === 'string' ? document.getElementById(table) : table;
        if (!element) return;
        const loader = document.getElementById(`${element.id}-page-loader`);
        const hideLoader = () => loader?.remove();
        const $ = window.jQuery;
        if (!$?.fn?.dataTable) {
            hideLoader();
            throw new Error('DataTables is not loaded.');
        }
        if ($.fn.dataTable.isDataTable(element)) return;
        const options = JSON.parse(element.dataset.tableOptions);
        element.classList.remove('hidden');
        const errorMessage = document.createElement('p');
        errorMessage.setAttribute('role', 'alert');
        errorMessage.className = 'hidden text-sm text-red-600';
        element.before(errorMessage);
        const fail = () => {
            hideLoader();
            errorMessage.textContent = 'Unable to load table data. Please reload the page to retry.';
            errorMessage.classList.remove('hidden');
            $(element).closest('.dataTables_wrapper').find('.dataTables_processing').hide();
        };
        $(element).on('error.dt', fail);
        let api;
        try {
            api = $(element).DataTable({
                ...options,
                processing: true,
                serverSide: true,
                scrollX: true,
                dom: 'lBfrtip',
                buttons: options.buttons,
                columns,
                createdRow,
                ajax: {
                    url: element.dataset.ajaxUrl,
                    type: 'GET',
                    timeout: 30000,
                    data: request => Object.assign(request, filters?.() || {}),
                    dataSrc: json => {
                        errorMessage.classList.add('hidden');
                        return dataSrc ? dataSrc(json) : json.data;
                    },
                    error: fail,
                },
                initComplete: hideLoader,
            });
        } catch (error) {
            fail();
            throw error;
        }
        const refresh = event => {
            if (event.detail?.tableId === element.id) api.ajax.reload(null, false);
        };
        document.addEventListener('data-table:refresh', refresh);
        $(element).on('destroy.dt', () => {
            document.removeEventListener('data-table:refresh', refresh);
            errorMessage.remove();
        });
    };
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start, { once: true });
    } else {
        start();
    }
}
