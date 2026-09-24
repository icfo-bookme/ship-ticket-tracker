<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4 max-w-[92%]">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Sales Reports
            </h2>
            <div class="flex items-end">
                <button id="clearFilters"
                    class="w-full md:w-auto px-4 py-2 bg-blue-200 text-gray-800 font-semibold rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    Clear Filters
                </button>
            </div>
        </div>

        <div class="mt-6 mb-6 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 max-w-[92%]">
            <div class="flex flex-col">
                <label for="shipFilter" class="text-sm font-semibold text-gray-700 mb-1">Ship</label>
                <select id="shipFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Ships</option>
                    @foreach ($ships as $ship)
                        <option value="{{ $ship->id }}">{{ $ship->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <label for="companyFilter" class="text-sm font-semibold text-gray-700 mb-1">Source
                    Company</label>
                <select id="companyFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Companies</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col">
                <label for="payment_method" class="text-sm font-semibold text-gray-700 mb-1">Payment Method</label>
                <select id="payment_method"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Methods</option>
                    <option value="Cash">Cash</option>
                    <option value="Bkash">Bkash</option>
                    <option value="Nagad">Nagad</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>

            <div class="flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startDate" class="text-sm font-semibold text-gray-700 mb-1">Journey Date
                        From</label>
                    <input type="date" id="startDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endDate" class="text-sm font-semibold text-gray-700 mb-1">To</label>
                    <input type="date" id="endDate"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>

            <div class="flex flex-col">
                <label for="returnDateFilter" class="text-sm font-semibold text-gray-700 mb-1">Return Date</label>
                <input type="date" id="returnDateFilter"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>

            <div class="flex flex-col md:flex-row gap-2">
                <div class="flex-1 flex flex-col">
                    <label for="startCreateDate" class="text-sm font-semibold text-gray-700 mb-1">Created Date:
                        From</label>
                    <input type="date" id="startCreateDate" value="{{ now()->subDays(6)->toDateString() }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
                <div class="flex-1 flex flex-col">
                    <label for="endCreateDate" class="text-sm font-semibold text-gray-700 mb-1">To</label>
                    <input type="date" id="endCreateDate" value="{{ now()->toDateString() }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
        </div>

        <script>
            const shipFilter = document.getElementById("shipFilter");
            const companyFilter = document.getElementById("companyFilter");
            const returnDateFilter = document.getElementById("returnDateFilter");
            const clearFiltersBtn = document.getElementById("clearFilters");
            const paymentMethodFilter = document.getElementById("payment_method");
            const startDateFilter = document.getElementById("startDate");
            const endDateFilter = document.getElementById("endDate");
            const startCreateDateFilter = document.getElementById("startCreateDate");
            const endCreateDateFilter = document.getElementById("endCreateDate");
            function totalElements() {
                return {
                    total_number_of_tickets: document.getElementById("totalSellTickets"),
                    total_ticket_fee: document.getElementById("totalSoldTicketsAmount"),
                    total_other_fee: document.getElementById("totalOtherFees"),
                    total_discount_amount: document.getElementById("totalDiscountAmount"),
                    total_payable: document.getElementById("totalSold"),
                    total_refunded_tickets: document.getElementById("totalRefundedTickets"),
                    total_refunded_amount: document.getElementById("totalRefundedAmount"),
                    total_received_amount: document.getElementById("totalReceivedAmount"),
                    total_due_amount: document.getElementById("totalDueAmount"),
                    net_sales_amount: document.getElementById("netSalesAmount"),
                };
            }

            window.dataTableColumns = window.dataTableColumns || {};
            window.dataTableColumns['salesTable'] = [
                {
                    data: "id",
                    title: "ID",
                    render: (data) => data || "N/A",
                },
                {
                    data: "customer_name",
                    title: "Customer Name",
                    render: (data) => data || "N/A",
                },
                {
                    data: "customer_mobile",
                    title: "Mobile",
                    render: (data) => data || "N/A",
                },
                {
                    data: "ship_name",
                    title: "Ship Name",
                    render: (data) => data || "N/A",
                },
                {
                    data: "journey_date",
                    title: "Journey Date",
                    render: formatDate,
                },
                {
                    data: "number_of_ticket",
                    title: "Number Of Ticket",
                    render: (data) => data || "0",
                },
                {
                    data: "ticket_fee",
                    title: "Total Ticket Price",
                    render: formatCurrency,
                },
                {
                    data: "other_fee",
                    title: "Other Fee",
                    render: formatCurrency,
                },
                {
                    data: "discount_amount",
                    title: "Discount Amount",
                    render: formatCurrency,
                },
                {
                    data: "total_payable",
                    title: "Total Payable",
                    render: formatCurrency,
                },
                {
                    data: "received_amount",
                    title: "Received Amount",
                    render: formatCurrency,
                },
                {
                    data: "refunded_number_of_tickets",
                    title: "Refunded Tickets",
                    render: (data) => data || 0,
                },
                {
                    data: "refunded_amount",
                    title: "Refunded Amount",
                    render: formatCurrency,
                },
                {
                    data: "due_amount",
                    title: "Due Amount",
                    render: formatCurrency,
                },
                {
                    data: null,
                    title: "Action",
                    orderable: false,
                    searchable: false,
                    render: (data, type, row) => type !== 'display' ? '' : createActionButtons(row),
                },
            ];

            window.dataTableFilters = window.dataTableFilters || {};
            window.dataTableFilters['salesTable'] = () => {
                const filters = {
                    ship_id: shipFilter?.value || "",
                    company_id: companyFilter?.value || "",
                    return_date: returnDateFilter?.value || "",
                    payment_method: paymentMethodFilter?.value || "",
                    start_date: startDateFilter?.value || "",
                    end_date: endDateFilter?.value || "",
                    start_create_date: startCreateDateFilter?.value || "",
                    end_create_date: endCreateDateFilter?.value || "",
                };

                return Object.fromEntries(Object.entries(filters).filter(([, value]) => value));
            };

            window.dataTableDataSrc = window.dataTableDataSrc || {};
            window.dataTableDataSrc['salesTable'] = (json) => {
                updateTotals(json.totals);

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

            function formatCurrency(amount) {
                if (!amount) return "0.00";

                return new Intl.NumberFormat("en-US", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(amount);
            }

            function updateTotals(totals = {}) {
                Object.entries(totalElements()).forEach(([key, element]) => {
                    if (element) element.textContent = totals[key] || (key.includes("tickets") ? "0" : "0.00");
                });
            }

            function createActionButtons(row) {
                if (!row?.id) return "";

                @can('sales.edit')
                    return `
                        <div class="flex gap-2 items-center justify-center">
                            <a href="/ship-ticket-sales/${row.id}">
                                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                            </a>
                        </div>`;
                @else
                    return "";
                @endcan
            }

            function reportFilterElements() {
                return [
                    shipFilter,
                    companyFilter,
                    returnDateFilter,
                    paymentMethodFilter,
                    startDateFilter,
                    endDateFilter,
                    startCreateDateFilter,
                    endCreateDateFilter,
                ];
            }

            function bindReportTableEvents() {
                reportFilterElements().forEach((filter) => filter?.addEventListener("change", () => window.getList()));

                clearFiltersBtn?.addEventListener("click", () => {
                    reportFilterElements().forEach((filter) => {
                        if (filter) filter.value = "";
                    });
                    window.getList();
                });
            }

            document.addEventListener("DOMContentLoaded", bindReportTableEvents);
        </script>

        <x-data-table id="salesTable" :headings="[
            'ID',
            'Customer Name',
            'Mobile',
            'Ship Name',
            'Journey Date',
            'Number Of Ticket',
            'Total Ticket fee',
            'Other Fee',
            'Discount Amount',
            'Total Payable',
            'Received Amount',
            'Refunded Tickets',
            'Refunded Amount',
            'Due Amount',
            'Action',
        ]" url="/reports" :ordering="false" :delegateActions="false" :order="[]"
            :lengthMenu="[[10, 25, 50, 100], [10, 25, 50, 100]]" />

        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6 max-w-[92%]">
            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Number of Sold Tickets</p>
                    <p id="totalSellTickets" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Sold Tickets Value/Fees</p>
                    <p id="totalSoldTicketsAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Other Fees Collections</p>
                    <p id="totalOtherFees" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Discount Amount</p>
                    <p id="totalDiscountAmount" class="text-2xl font-bold text-blue-950 dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-green-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Payable</p>
                    <p id="totalSold" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-red-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Refunded Tickets</p>
                    <p id="totalRefundedTickets" class="text-2xl text-white font-bold dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-red-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Refunded Amount</p>
                    <p id="totalRefundedAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-indigo-500 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Received Amount</p>
                    <p id="totalReceivedAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-yellow-600 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Total Due Amount</p>
                    <p id="totalDueAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>

            <div
                class="bg-teal-600 dark:bg-gray-800 shadow-md rounded-lg p-6 flex items-center justify-between border border-gray-200 dark:border-gray-700">
                <div>
                    <p class="text-sm font-medium text-white dark:text-gray-400">Net Sales Amount</p>
                    <p id="netSalesAmount" class="text-2xl font-bold text-white dark:text-blue-400">0</p>
                </div>
            </div>
        </div>
    </div>
</div>
