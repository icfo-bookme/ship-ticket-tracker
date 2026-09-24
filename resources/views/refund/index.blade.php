<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Refundable Sales
        </h2>

        <div class="mt-6 mb-4 grid grid-cols-3 gap-10 max-w-[92%]">
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
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => type !== 'display' ? '' :
                        `<input type="checkbox" class="selectSale" data-id="${row.id}" />`,
                },
                { data: "id" },
                {
                    data: "customer_name",
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: "customer_mobile",
                    render: (data, type) => type !== 'display' ? data : escapeHtml(data),
                },
                {
                    data: null,
                    render: (row) => escapeHtml(row.ship?.name || row.ships?.name || "Not available"),
                },
                {
                    data: "journey_date",
                    render: formatDate,
                },
                { data: "number_of_ticket" },
                { data: "ticket_fee" },
                { data: "other_fee" },
                { data: "total_payable" },
                { data: "received_amount" },
                { data: "due_amount" },
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

            function formatDate(dateString) {
                if (!dateString) return "N/A";

                return new Date(dateString).toLocaleDateString("en-US", {
                    year: "numeric",
                    month: "long",
                    day: "numeric",
                });
            }

            function createActionButtons(sale) {
                @can('refunds.manage')
                    return `
                        <div class="flex gap-2 items-center justify-center">
                            <a href="/ship-ticket-sales/${sale.id}">
                                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                            </a>
                            <button class="bg-blue-900 text-white px-2 py-1 rounded verifyRefund"
                                data-id="${sale.id}"
                                data-received_total_amount="${sale.received_amount}"
                                data-number_ticket="${sale.number_of_ticket}"
                                data-status="shipped">
                                Partial Refund
                            </button>
                        </div>`;
                @else
                    return "";
                @endcan
            }

            function selectedSaleIds() {
                return Array.from(document.querySelectorAll(".selectSale:checked"))
                    .map((checkbox) => checkbox.dataset.id);
            }

            async function refundSelectedSales() {
                const ids = selectedSaleIds();
                if (!ids.length) {
                    Swal.fire({ title: "Error!", text: "Please select at least one item to refund.", icon: "error", confirmButtonText: "OK" });
                    return;
                }

                try {
                    const response = await fetch("/full/refunds", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                        },
                        body: JSON.stringify({ ids }),
                    });
                    const result = await response.json();

                    if (result.status === "success") {
                        Swal.fire({ title: "Success!", text: "Refund successfully processed for selected items.", icon: "success", confirmButtonText: "OK" });
                        window.getList();
                        return;
                    }

                    Swal.fire({ title: "Error!", text: result.message || "Refund failed.", icon: "error", confirmButtonText: "OK" });
                } catch (error) {
                    console.error("Error sending refund request:", error);
                    Swal.fire({ title: "Error!", text: "An error occurred. Please try again.", icon: "error", confirmButtonText: "OK" });
                }
            }

            function bindRefundTableEvents() {
                const table = document.getElementById("salesTable");
                const refundSelectedBtn = document.getElementById("refundSelectedBtn");
                const selectAllHeader = table?.querySelector("thead th:first-child");
                if (selectAllHeader) {
                    selectAllHeader.innerHTML = '<input type="checkbox" class="form-checkbox selectAllSales">';
                }

                [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                    filter?.addEventListener("change", () => window.getList());
                });

                clearFiltersBtn?.addEventListener("click", () => {
                    [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                        if (filter) filter.value = "";
                    });
                    window.getList();
                });

                document.addEventListener("change", (event) => {
                    const selectAll = event.target.closest(".selectAllSales");
                    if (!selectAll) return;

                    document.querySelectorAll(".selectSale").forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                    document.querySelectorAll(".selectAllSales").forEach((checkbox) => {
                        checkbox.checked = selectAll.checked;
                    });
                });

                refundSelectedBtn?.addEventListener("click", refundSelectedSales);

                table?.addEventListener("click", (event) => {
                    const button = event.target.closest(".verifyRefund");
                    if (button) refunded(button, window.getList);
                });
            }

            document.addEventListener("DOMContentLoaded", bindRefundTableEvents);
        </script>

        <x-data-table id="salesTable" :headings="[
            '',
            'ID',
            'Customer Name',
            'Mobile',
            'Ship Name',
            'Journey Date',
            'Number Of Ticket',
            'Total Ticket Price',
            'Other Fee',
            'Total Payable',
            'Total Received Amount',
            'Due Amount',
            'Action',
        ]" url="/all/refundable" :ordering="false" :delegateActions="false" :order="[]"
            :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

        <div class="flex justify-end mt-10">
            <button id="refundSelectedBtn"
                class="px-4 py-2 bg-green-900 text-white rounded-md hover:bg-green-600 focus:outline-none">
                Refund Selected
            </button>
        </div>
    </div>
</div>
