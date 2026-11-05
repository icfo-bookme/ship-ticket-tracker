@props(['id', 'name', 'label', 'options', 'help' => 'No selection means all options.'])

<div id="{{ $id }}" data-searchable-multi-select class="relative flex min-w-0 flex-col gap-2">
    <div class="flex flex-wrap items-center gap-2">
        <label for="{{ $id }}Toggle" class="text-sm font-medium text-gray-700">{{ $label }}</label>
        <div data-multi-select-selected class="hidden flex flex-wrap gap-2"></div>
    </div>
    <div class="relative">
    <button id="{{ $id }}Toggle" type="button" data-multi-select-toggle aria-expanded="false"
        aria-controls="{{ $id }}Dropdown" aria-describedby="{{ $id }}Help"
        class="flex w-full items-center justify-between gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <span data-multi-select-caption data-placeholder="All {{ $label }}">All {{ $label }}</span>
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
    </button>
    <div id="{{ $id }}Dropdown" data-multi-select-dropdown hidden
        class="absolute left-0 right-0 top-full z-30 mt-1 rounded-md border border-gray-300 bg-white p-3 shadow-lg">
    <label for="{{ $id }}Search" class="sr-only">Search {{ strtolower($label) }}</label>
    <input id="{{ $id }}Search" data-multi-select-search type="search" placeholder="Search {{ strtolower($label) }}"
        aria-controls="{{ $id }}Options" aria-describedby="{{ $id }}Help"
        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
    <fieldset id="{{ $id }}Options" class="mt-3 max-h-48 overflow-y-auto">
        <legend class="sr-only">Select {{ strtolower($label) }}</legend>
        <div class="flex flex-col gap-3">
            @foreach ($options as $option)
                <label data-multi-select-option class="flex cursor-pointer items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="{{ $name }}" value="{{ $option->id }}" data-label="{{ $option->name }}"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span>{{ $option->name }}</span>
                </label>
            @endforeach
            <p data-multi-select-empty role="status" @if ($options->isNotEmpty()) hidden @endif class="text-sm text-gray-500">
                No matching options.
            </p>
        </div>
    </fieldset>
    </div>
    </div>
    <p id="{{ $id }}Help" class="text-xs text-gray-500">{{ $help }}</p>
</div>
