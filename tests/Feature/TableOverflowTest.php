<?php

use Illuminate\Support\Facades\Blade;

it('keeps the table container scrollable like before', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('.overflow-x-auto {')
        ->toContain('overflow-x: auto;')
        ->toContain('.overflow-x-auto table.dataTable th,')
        ->toContain('white-space: nowrap;')
        ->not->toContain('.table-area')
        ->not->toContain('.overflow-x-auto::-webkit-scrollbar');
});

it('renders the shared table inside the scrollable wrapper', function () {
    $html = Blade::render('<x-data-table id="testTable" :headings="[\'ID\']" url="/test" />');

    expect($html)
        ->toContain('class="overflow-x-auto"')
        ->toContain('data-ajax-url="/test"');
});

it('renders a dimming backdrop on the shared modal', function () {
    $html = Blade::render('<x-entity-modal id="testModal" title="Test modal" />');

    expect($html)
        ->toContain('bg-gray-900/50')
        ->toContain('dark:bg-gray-900/70');
});

it('locks document scrolling while a shared modal is open', function () {
    $view = file_get_contents(resource_path('views/components/entity-modal.blade.php'));

    expect($view)
        ->toContain("event.target.closest('[data-modal-target]')")
        ->toContain("document.documentElement.classList.add('overflow-hidden')")
        ->toContain("document.documentElement.classList.remove('overflow-hidden')")
        ->toContain('window.innerWidth - document.documentElement.clientWidth')
        ->toContain('document.body.style.paddingRight = previousBodyPaddingRight ??');
    expect($view)->toContain('class="overflow-y-auto grow"');
});

it('opens the sales report details modal from its delegated data target', function () {
    $script = file_get_contents(resource_path('js/pages/reports-sales.js'));

    expect($script)
        ->toContain('data-modal-target="saleReportDetailModal"')
        ->not->toContain('modal._openModal?.();');
});

it('keeps the horizontal overflow out of the page itself', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)
        ->toContain('id="main-content"')
        ->toContain('min-h-screen min-w-0')
        ->toContain('scrollX: true')
        ->toContain('autoWidth: false')
        ->not->toContain('min-h-screen min-w-0 overflow-x-auto');
});

it('provides shared small-screen layout rules for grids and data tables', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toContain('@media (max-width: 639px)')
        ->toContain('.dataTables_wrapper .dt-buttons')
        ->toContain('grid-template-columns: minmax(0, 1fr);');
});

it('animates the mobile navigation drawer and backdrop', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));
    $script = file_get_contents(resource_path('js/layout/sidebar.js'));

    expect($layout)
        ->toContain('opacity-0 backdrop-blur-[1px] transition-opacity duration-300')
        ->toContain('transition-[width,transform] duration-300');
    expect($script)
        ->toContain("mobileSidebarToggle.addEventListener('click', toggleMobileSidebar)")
        ->toContain("sidebarBackdrop.classList.add('opacity-100')")
        ->toContain("event.key === 'Escape'");
});
