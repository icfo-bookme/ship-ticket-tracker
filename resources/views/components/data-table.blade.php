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

    <div class="">
        <table id="{{ $id }}" class="border border-gray-300 hidden" data-ajax-url="{{ $url }}"
            data-table-options="{{ json_encode(['ordering' => $ordering, 'pageLength' => $pageLength, 'lengthMenu' => $lengthMenu, 'buttons' => $buttons, 'order' => $order, 'language' => ['lengthMenu' => '_MENU_', 'processing' => $loadingText]]) }}">
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

