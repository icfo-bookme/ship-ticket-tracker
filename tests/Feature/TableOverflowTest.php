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

it('keeps the horizontal overflow out of the page itself', function () {
    $layout = file_get_contents(resource_path('views/layouts/app.blade.php'));

    expect($layout)
        ->toContain('id="main-content"')
        ->toContain('min-h-screen min-w-0')
        ->toContain('scrollX: true')
        ->toContain('autoWidth: false')
        ->not->toContain('min-h-screen min-w-0 overflow-x-auto');
});
