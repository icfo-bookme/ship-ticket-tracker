import { refreshDataTable } from '../services/api';

const configElement = document.getElementById('salesReportConfig');
const config = { canEdit: configElement?.dataset.canEdit === 'true' };
const shipFilter = document.getElementById("shipFilter");
            const companyFilter = document.getElementById("companyFilter");
            const returnDateFilter = document.getElementById("returnDateFilter");
            const clearFiltersBtn = document.getElementById("clearFilters");
            const paymentMethodFilter = document.getElementById("payment_method");
            const bftnFilter = document.getElementById("bftnFilter");
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
                    total_gross_refund_amount: document.getElementById("totalGrossRefundAmount"),
                    total_customer_refund_amount: document.getElementById("totalCustomerRefundAmount"),
                    total_due_adjusted_amount: document.getElementById("totalDueAdjustedAmount"),
                    total_customer_refund_after_due_adjustment: document.getElementById("totalCustomerRefundAfterDueAdjustment"),
                    total_partner_share_amount: document.getElementById("totalPartnerShareAmount"),
                    total_company_retained_amount: document.getElementById("totalCompanyRetainedAmount"),
                    total_bftn: document.getElementById("totalBftn"),
                    total_bftn_pending: document.getElementById("totalBftnPending"),
                    total_bftn_received: document.getElementById("totalBftnReceived"),
                    total_bftn_amount: document.getElementById("totalBftnAmount"),
                    total_bftn_pending_amount: document.getElementById("totalBftnPendingAmount"),
                    total_bftn_received_amount: document.getElementById("totalBftnReceivedAmount"),
                    net_cash: document.getElementById("netCash"),
                };
            }

            const table = document.getElementById('salesTable');
            table.__dataTableColumns = [
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
                { data: "extra_received_amount", title: "Extra Received", render: formatCurrency },
                { data: "extra_refunded_amount", title: "Extra Refunded", render: formatCurrency },
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
                { data: "gross_refund_amount", title: "Gross Amount", render: formatCurrency },
                { data: "due_adjusted_amount", title: "Due Adjusted", render: formatCurrency },
                { data: "customer_refund_amount", title: "Final Customer Refund", render: formatCurrency },
                { data: "partner_share_amount", title: "Partner Share", render: formatCurrency },
                { data: "company_retained_amount", title: "Company Retained", render: formatCurrency },
                { data: "bftn_status", title: "BFTN", render: (data) => data === "yes" ? "Yes" : "No" },
                {
                    data: "bftn_received",
                    title: "BFTN Received",
                    render: (data, type, row) => {
                        if (row.bftn_status !== "yes") {
                            return "N/A";
                        }

                        if (!data) {
                            return "Pending";
                        }

                        return `<div class="flex flex-col items-center">
                            <span>Received</span>
                            <small class="text-xs text-gray-500">${escapeHtml(row.bftn_received_at || "N/A")}</small>
                        </div>`;
                    },
                },
                { data: "bftn_amount", title: "BFTN Amount", render: formatCurrency },
                { data: "net_cash", title: "Net Cash", render: formatCurrency },
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

            table.__dataTableFilters = () => {
                const filters = {
                    ship_id: shipFilter?.value || "",
                    company_id: companyFilter?.value || "",
                    return_date: returnDateFilter?.value || "",
                    payment_method: paymentMethodFilter?.value || "",
                    bftn_status: bftnFilter?.value || "",
                    start_date: startDateFilter?.value || "",
                    end_date: endDateFilter?.value || "",
                    start_create_date: startCreateDateFilter?.value || "",
                    end_create_date: endCreateDateFilter?.value || "",
                };

                return Object.fromEntries(Object.entries(filters).filter(([, value]) => value));
            };

            table.__dataTableDataSrc = (json) => {
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

                if (config.canEdit) {
                    return `
                        <div class="flex gap-2 items-center justify-center">
                            <a href="/ship-ticket-sales/${row.id}">
                                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
                            </a>
                        </div>`;
                } else {
                    return "";
                }
            }

            function reportFilterElements() {
                return [
                    shipFilter,
                    companyFilter,
                    returnDateFilter,
                    paymentMethodFilter,
                    bftnFilter,
                    startDateFilter,
                    endDateFilter,
                    startCreateDateFilter,
                    endCreateDateFilter,
                ];
            }

            function bindReportTableEvents() {
                reportFilterElements().forEach((filter) => filter?.addEventListener("change", () => refreshDataTable('salesTable')));

                clearFiltersBtn?.addEventListener("click", () => {
                    reportFilterElements().forEach((filter) => {
                        if (filter) filter.value = "";
                    });
                    refreshDataTable('salesTable');
                });
            }

            document.addEventListener("DOMContentLoaded", bindReportTableEvents);
import { escapeHtml } from '../utils/escape-html';
