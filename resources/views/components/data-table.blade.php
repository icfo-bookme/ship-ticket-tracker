@props([
    'id' => 'dataTable',
    'headings' => [],
    'url' => '',
    'order' => [[0, 'asc']],
    'ordering' => true,
    'delegateActions' => true,
    'pageLength' => 25,
    'lengthMenu' => [[10, 25, 50, 75, 100, 200, 300, 400, 500], [10, 25, 50, 75, 100, 200, 300, 400, 500]],
    'buttons' => ['copy', 'excel', 'csv', 'pdf', 'print', ['extend' => 'colvis', 'text' => 'Column Visibility']],
    'loadingText' => 'Loading data...',
])

<!-- Table area: the loader overlay covers ONLY this container, not the page.
     Table stays hidden until the first data loads. DataTables keeps its own
     default processing indicator for later ajax reloads. -->
<div class="relative">
    <div id="{{ $id }}-page-loader"
        class="absolute inset-0 z-[100] bg-white flex items-center justify-center min-h-[60vh] transition-opacity duration-200">
        <div class="flex flex-col items-center gap-3 bg-white p-5 rounded-xl shadow-lg border border-gray-100">
            <!-- Tailwind CSS Spinner -->
            <div class="w-10 h-10 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin"></div>
            <p class="text-xs font-bold text-gray-600 tracking-wide select-none">{{ $loadingText }}</p>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="{{ $id }}" class=" border border-gray-300 hidden" data-ajax-url="{{ $url }}">
            <thead class="bg-[#003366] text-white">
                <tr>
                    @foreach ($headings as $heading)
                        <th class="border px-4 py-2">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="{{ $id }}Body"></tbody>
        </table>
    </div>
</div>

<script>
    (function () {
        const bodyId = @json($id) + 'Body';
        const tableEl = document.getElementById(@json($id));
        const bodyEl = document.getElementById(bodyId);
        const pageLoaderEl = document.getElementById(@json($id) + '-page-loader');

        // Full-page loader is visible while the page/first data loads, then
        // fades out. DataTables' own default indicator handles later reloads.
        let pageLoaderHidden = false;
        function hidePageLoader() {
            if (pageLoaderHidden || !pageLoaderEl) return;
            pageLoaderHidden = true;
            pageLoaderEl.classList.add('opacity-0', 'pointer-events-none');
            setTimeout(function () { pageLoaderEl.remove(); }, 250);
        }

        // Safety net: never trap the user behind the loader (e.g. network fail).
        setTimeout(hidePageLoader, 15000);

        // Shared escapeHtml — only defined once globally.
        if (!window.escapeHtml) {
            window.escapeHtml = function (value) {
                return String(value ?? '')
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };
        }

        // Refresh helper so modal views can reload after create/update/delete.
        window.getList = window.getList || function () {};
        const previousGetList = window.getList;
        window.getList = function () {
            previousGetList();
            if (window.jQuery && $.fn.DataTable.isDataTable(tableEl)) {
                $(tableEl).DataTable().ajax.reload(null, false);
            }
        };

        // Optional per-page filter hook: window.dataTableFilters[id] = fn returning
        // extra query params merged into every ajax request (e.g. dropdown filters).
        // Resolved lazily inside DOMContentLoaded (see below).

        // Event delegation for edit/delete buttons — survives DataTables redraws.
        // Pages that wire their own actions can disable it with :delegateActions="false".
        const delegateActions = @json($delegateActions);
        if (delegateActions) {
            bodyEl.addEventListener('click', function (e) {
                const editBtn = e.target.closest('.editBtn');
                if (editBtn && typeof window.showEditModal === 'function') { showEditModal(editBtn); return; }

                const deleteBtn = e.target.closest('.deleteBtn');
                if (deleteBtn && typeof window.handleDeleteClick === 'function') { handleDeleteClick(deleteBtn); }
            });
        }

        // Uniform DataTables button styling for every table.
        if (window.jQuery && $.fn.DataTable) {
            $.extend(true, $.fn.DataTable.Buttons.defaults, {
                dom: { button: { className: 'btn border border-gray-300 bg-white text-gray-800 px-3 py-1.5 text-sm rounded hover:bg-gray-100' } },
            });
        }

        // Page-specific column definitions live in window.dataTableColumns[id].
        // Every page must register its columns there before this script runs.

        document.addEventListener('DOMContentLoaded', function () {
            const customColumns = (window.dataTableColumns || {})[@json($id)];
            const filtersFn = (window.dataTableFilters || {})[@json($id)];
            const dataSrcFn = (window.dataTableDataSrc || {})[@json($id)];

            // Table must be visible BEFORE DataTables initialises: the global
            // scrollX default (layouts/app.blade.php) clones the thead, and
            // cloning a hidden table yields a zero-height header row.
            tableEl.classList.remove('hidden');

            // Every page must register its columns in window.dataTableColumns[id]
            // (see the usage comment at the top of this component).
            $(tableEl).on('error.dt', function () {
                hidePageLoader();
            });

            $(tableEl).DataTable({
                processing: true,
                serverSide: true,
                ordering: @json($ordering),
                ajax: filtersFn || dataSrcFn
                    ? {
                        url: @json($url),
                        type: 'GET',
                        data: filtersFn ? (request) => Object.assign(request, filtersFn()) : undefined,
                        dataSrc: dataSrcFn,
                    }
                    : @json($url),
                pageLength: @json($pageLength),
                lengthMenu: @json($lengthMenu),
                dom: 'lBfrtip',
                buttons: @json($buttons),
                order: @json($order),
                language: {
                    lengthMenu: '_MENU_',
                    processing: @json($loadingText),
                },
                columns: customColumns,
                initComplete: function () {
                    hidePageLoader();
                },
            });
        });
    })();
</script>
