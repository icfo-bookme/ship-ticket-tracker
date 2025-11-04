

export function initializeDataTable(tableId) {
    if ($(tableId).DataTable().settings().length > 0) {
        return; 
    }

    $(tableId).DataTable({
        dom: 'lBfrtip',
        lengthChange: true,
        lengthMenu: [
            [10, 25, 50, 75, 100, 200, 300, 400, 500],
            [10, 25, 50, 75, 100, 200, 300, 400, 500]
        ],
        language: {
            lengthMenu: '_MENU_' // Display dropdown only
        },
        buttons: [
            'copy', 'excel', 'csv', 'pdf', 'print',
            {
                extend: 'colvis',
                text: 'Column Visibility'
            }
        ]
    });
}
