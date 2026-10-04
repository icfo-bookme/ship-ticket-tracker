if (window.jQuery && $.fn.dataTable?.Buttons) {
    $.extend(true, $.fn.dataTable.Buttons.defaults, {
        dom: {
            button: {
                className: 'btn border border-gray-300 bg-white text-gray-800 px-3 py-1.5 text-sm rounded hover:bg-gray-100',
            },
        },
    });
}
