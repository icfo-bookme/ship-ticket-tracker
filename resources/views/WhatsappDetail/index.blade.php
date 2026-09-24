<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between pb-5">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Whatsapp Details
            </h2>
        </div>

        <script>
            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['whatsappTable'] = [
                { data: 'id' },
                {
                    data: 'tag',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'whatsapp_number',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'form_no',
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: 'url',
                    orderable: false,
                    searchable: false,
                    render: (data, type) => {
                        if (type !== 'display') return data;
                        return `<div class="flex items-center gap-2">
                                <span class="truncate max-w-[180px]" title="${escapeHtml(data)}">${escapeHtml(data)}</span>
                                <button class="copyBtn bg-blue-500 text-white px-2 py-1 rounded text-sm" data-url="${escapeHtml(data)}">Copy</button>
                            </div>`;
                    },
                },
            ];
        </script>

        {{-- No initial order: the server already returns the latest first --}}
        <x-data-table id="whatsappTable" :headings="['ID', 'Tag', 'Whatsapp Number', 'Form No', 'Form Url']"
            url="/whatsapp" :order="[]" :lengthMenu="[[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]]" />

        {{-- Copy button handling: event delegation survives DataTables redraws --}}
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.getElementById('whatsappTableBody').addEventListener('click', function (e) {
                    const btn = e.target.closest('.copyBtn');
                    if (!btn) return;
                    navigator.clipboard.writeText(btn.dataset.url).then(function () {
                        btn.textContent = 'Copied!';
                        setTimeout(function () { btn.textContent = 'Copy'; }, 1500);
                    });
                });
            });
        </script>
    </div>
</div>
