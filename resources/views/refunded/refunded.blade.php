<div class="py-6">
    <div class=" mx-auto sm:px-6 lg:px-8">
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
                    data: "refund.gross_refund_amount",
                    render: (data, type, row) => row.refund?.gross_refund_amount || 0,
                },
                {
                    data: "refund.customer_refund_amount",
                    render: (data, type, row) => row.refund?.customer_refund_amount || 0,
                },
                {
                    data: "refund.partner_share_amount",
                    render: (data, type, row) => row.refund?.partner_share_amount || 0,
                },
                {
                    data: "refund.company_retained_amount",
                    render: (data, type, row) => row.refund?.company_retained_amount || 0,
                },
                {
                    data: "refund.due_adjusted_amount",
                    render: (data, type, row) => Number(row.refund?.due_adjusted_amount || 0).toFixed(2),
                },
                {
                    data: "refund.customer_refund_amount",
                    render: (data, type, row) => `<div class="bg-red-100 px-2 py-1 font-semibold text-red-700">${Number(row.refund?.customer_refund_amount || 0).toFixed(2)}</div>`,
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
                document.getElementById("totalGrossAmount").textContent = Number(json.total_gross_amount || 0).toFixed(2);
                document.getElementById("totalCustomerRefund").textContent = Number(json.total_customer_refund || 0).toFixed(2);
                document.getElementById("totalPartnerShare").textContent = Number(json.total_partner_share || 0).toFixed(2);
                document.getElementById("totalCompanyRetained").textContent = Number(json.total_company_retained || 0).toFixed(2);

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
                        <a href="/refunded/${row.id}/details" class="bg-blue-700 text-white px-2 py-1 rounded" title="View refund details">Details</a>
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
            'Refunded Num Of Tickets',
            'Gross Amount',
            'Customer Refund',
            'Partner Share',
            'Company Retained',
            'Due Adjusted',
            'Final Customer Refund',
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

        <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Gross Amount</p>
                <p id="totalGrossAmount" class="mt-2 text-2xl font-bold text-blue-950 dark:text-blue-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Customer Refund Amount</p>
                <p id="totalCustomerRefund" class="mt-2 text-2xl font-bold text-green-700 dark:text-green-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Partner Share Amount</p>
                <p id="totalPartnerShare" class="mt-2 text-2xl font-bold text-amber-700 dark:text-amber-400">0.00</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-md dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Company Retained/Company Retained Amount</p>
                <p id="totalCompanyRetained" class="mt-2 text-2xl font-bold text-purple-700 dark:text-purple-400">0.00</p>
            </div>
        </div>
    </div>
</div>
