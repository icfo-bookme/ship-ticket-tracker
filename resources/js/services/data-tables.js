if (window.jQuery && $.fn.DataTable?.Buttons) {
    $.extend(true, $.fn.DataTable.Buttons.defaults, {
        dom: {
            button: {
                className: 'btn border border-gray-300 bg-white text-gray-800 px-3 py-1.5 text-sm rounded hover:bg-gray-100',
            },
        },
    });
}
