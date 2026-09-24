<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Refunded Ship Ticket Sales
        </h2>

        <div class="mt-6 mb-4 grid grid-cols-3 gap-10">
            <div>
                <label for="shipFilter" class="block text-sm font-medium text-gray-700">Filter by Ship</label>
                <select id="shipFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Ships</option>
                    @foreach ($ships as $ship)
                        <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="companyFilter" class="block text-sm font-medium text-gray-700">Filter by Source
                    Company</label>
                <select id="companyFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">All Companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex-1">
                <label for="journeyDateFilter" class="block text-sm font-medium text-gray-700">Filter by Journey
                    Date</label>
                <input type="date" id="journeyDateFilter"
                    class="mt-1 block w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <div class="flex items-end">
                <button id="clearFilters"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                    Clear Filters
                </button>
            </div>
        </div>

        <script>
            const shipFilter = document.getElementById("shipFilter");
            const companyFilter = document.getElementById("companyFilter");
            const journeyDateFilter = document.getElementById("journeyDateFilter");
            const clearFiltersBtn = document.getElementById("clearFilters");

            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['salesTable'] = [
                {
                    data: "id",
                    render: (data) => data || "N/A",
                },
                {
                    data: "customer_name",
                    render: (data) => data ? escapeHtml(data) : "N/A",
                },
                {
                    data: "customer_mobile",
                    render: (data) => data ? escapeHtml(data) : "N/A",
                },
                {
                    data: null,
                    render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available"),
                },
                {
                    data: "journey_date",
                    render: formatDate,
                },
                {
                    data: "number_of_ticket",
                    render: (data) => data || 0,
                },
                {
                    data: "refund.refunded_number_of_tickets",
                    render: (data, type, row) => row.refund?.refunded_number_of_tickets || 0,
                },
                {
                    data: "received_amount",
                    render: (data) => data || 0,
                },
                {
                    data: "refund.refunded_amount",
                    render: (data, type, row) => row.refund?.refunded_amount || 0,
                },
                {
                    data: "status",
                    render: (data) => data || "N/A",
                },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => type !== 'display' ? '' : createActionButtons(row),
                },
            ];

            window.dataTableFilters = window.dataTableFilters || {};
            window.dataTableFilters['salesTable'] = () => ({
                ship_id: shipFilter.value,
                company_id: companyFilter.value,
                journey_date: journeyDateFilter.value,
            });

            window.dataTableDataSrc = window.dataTableDataSrc || {};
            window.dataTableDataSrc['salesTable'] = (json) => {
                const totalRefundedTicketsElement = document.getElementById("totalRefundedTickets");
                const totalRefundedAmountElement = document.getElementById("totalRefundedAmount");

                if (totalRefundedTicketsElement && json.total_refunded_tickets !== undefined) {
                    totalRefundedTicketsElement.textContent = json.total_refunded_tickets;
                }
                if (totalRefundedAmountElement && json.total_refunded_amount !== undefined) {
                    totalRefundedAmountElement.textContent = json.total_refunded_amount;
                }

                return json.data || [];
            };

            function formatDate(dateString) {
                if (!dateString || dateString === "Not specified") return dateString || "N/A";

                return new Date(dateString).toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            }

            function createActionButtons(row) {
                @can('refunds.manage')
                    return `
                        <button class="text-white bg-yellow-700 px-2 py-1 rounded editRefundedBtn"
                            data-id="${row.refund?.id ?? ""}"
                            data-received_total_amount="${row.received_amount}"
                            data-number_ticket="${row.number_of_ticket}"
                            data-refunded_amount="${row.refund?.refunded_amount ?? ""}"
                            data-refunded_number_of_tickets="${row.refund?.refunded_number_of_tickets ?? ""}">
                            Edit Refunded
                        </button>
                        <a href="/ship-ticket-sales/${row.id}">
                            <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                        </a>
                        <button class="fas fa-trash text-red-500 px-2 py-1 rounded deleteBtn" data-id="${row.id}"></button>`;
                @else
                    return "";
                @endcan
            }

            async function deleteSale(button) {
                const saleId = button.dataset.id;
                const { isConfirmed } = await Swal.fire({
                    title: "Are you sure?",
                    text: "Do you want to delete this sale?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, delete",
                    cancelButtonText: "Cancel",
                });

                if (!isConfirmed) return;

                try {
                    const response = await fetch(`/ship-ticket-sales/${saleId}`, {
                        method: "DELETE",
                        headers: {
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                            "Content-Type": "application/json",
                        },
                    });
                    const result = await response.json();

                    if (response.ok) {
                        Swal.fire({ title: "Deleted!", text: "Sale deleted successfully.", icon: "success", confirmButtonText: "OK" });
                        window.getList();
                        return;
                    }

                    Swal.fire({ title: "Error!", text: result.message || "Failed to delete sale.", icon: "error", confirmButtonText: "OK" });
                } catch (error) {
                    console.error("Error deleting sale:", error);
                    Swal.fire({ title: "Error!", text: "An error occurred while deleting the sale.", icon: "error", confirmButtonText: "OK" });
                }
            }

            function bindRefundedTableEvents() {
                const table = document.getElementById("salesTable");

                [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                    filter?.addEventListener("change", () => window.getList());
                });

                clearFiltersBtn?.addEventListener("click", () => {
                    [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                        if (filter) filter.value = "";
                    });
                    window.getList();
                });

                table?.addEventListener("click", (event) => {
                    const button = event.target.closest("button");
                    if (!button) return;

                    if (button.classList.contains("deleteBtn")) {
                        event.preventDefault();
                        deleteSale(button);
                    } else if (button.classList.contains("editRefundedBtn")) {
                        event.preventDefault();
                        refunded(button, window.getList);
                    }
                });
            }

            document.addEventListener("DOMContentLoaded", bindRefundedTableEvents);
        </script>

        <x-data-table id="salesTable" :headings="[
            'ID',
            'Customer Name',
            'Mobile',
            'Ship Name',
            'Journey Date',
            'Purchase Num Of Tickets',
            'Return Num Of Tickets',
            'Received Amount',
            'Refunded Amount',
            'Status',
            'Action',
        ]" url="/all/refunded" :ordering="false" :delegateActions="false" :order="[]"
            :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Refunded Tickets</p>
                    <p id="totalRefundedTickets" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
                <div class="text-blue-200 dark:text-blue-600 text-3xl">
                    Ticket/s
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Refunded Amount</p>
                    <p id="totalRefundedAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
                <div class="text-blue-200 dark:text-blue-600 text-3xl">
                    BDT
                </div>
            </div>
        </div>
    </div>
</div>
