import { escapeHtml } from '../utils/escape-html.js';

function formatDate(dateString) {
    if (!dateString || dateString === "Not specified") return dateString || "N/A";

    return new Date(dateString).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
}

function formatDateTime(dateString) {
    if (!dateString) return 'N/A';

    return new Date(dateString).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "numeric",
        minute: "2-digit",
    });
}

function formatCurrency(amount) {
    const numericAmount = Number(String(amount ?? 0).replaceAll(',', ''));

    if (!Number.isFinite(numericAmount)) return "0.00";

    return new Intl.NumberFormat("en-US", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(numericAmount);
}

function formatDetailValue(value) {
    return value === null || value === undefined || value === '' ? 'N/A' : String(value);
}

function formatElapsedTime(start, end) {
    const difference = new Date(end) - new Date(start);

    if (!Number.isFinite(difference)) return 'Time unavailable';

    const elapsedMinutes = Math.max(0, Math.floor(difference / 60000));
    const days = Math.floor(elapsedMinutes / 1440);
    const hours = Math.floor((elapsedMinutes % 1440) / 60);
    const minutes = elapsedMinutes % 60;
    const parts = [];

    if (days) parts.push(`${days} ${days === 1 ? 'day' : 'days'}`);
    if (hours) parts.push(`${hours} ${hours === 1 ? 'hour' : 'hours'}`);
    if (minutes || parts.length === 0) parts.push(`${minutes} ${minutes === 1 ? 'minute' : 'minutes'}`);

    return parts.join(' ');
}

function showSaleDetails(sale) {
    const modal = document.getElementById('saleReportDetailModal');
    const details = document.getElementById('saleReportDetails');
    const paymentHistoryElement = document.getElementById('saleReportPaymentHistory');
    const timeline = document.getElementById('saleReportVerificationTimeline');
    const fields = [
        ['Customer Name', sale.customer_name],
        ['Mobile', sale.customer_mobile],
        ['Company', sale.company_name],
        ['Ship', sale.ship_name],
        ['Journey Date', formatDate(sale.journey_date)],
        ['Return Date', formatDate(sale.return_date)],
        ['Created Date', formatDate(sale.created_at)],
        ['Status', sale.status],
        ['Tickets', sale.ticket_count],
        ['Ticket Price', formatCurrency(sale.ticket_value)],
        ['Other Fee', formatCurrency(sale.other_fee)],
        ['Discount', formatCurrency(sale.discount_amount)],
        ['Total Payable', formatCurrency(sale.total_payable)],
        ['Received Amount', formatCurrency(sale.received_amount)],
        ['Due Amount', formatCurrency(sale.due_amount)],
        ['Completed Ticket Refund Amount', formatCurrency(sale.refunded_amount)],
        ['Gross Ticket Refund Amount', formatCurrency(sale.gross_refund_amount)],
        ['Ticket Refund Discount', formatCurrency(sale.refund_discount_amount)],
        ['Due Adjusted', formatCurrency(sale.due_adjusted_amount)],
        ['Customer Refund Paid', formatCurrency(sale.customer_refund_paid)],
        ['Extra Received', formatCurrency(sale.extra_received_amount)],
        ['Extra Refund Pending', formatCurrency(sale.extra_refund_pending_amount)],
        ['Extra Payment Refund Paid', formatCurrency(sale.extra_refunded_amount)],
        ['Extra Available', formatCurrency(sale.extra_available_amount)],
        ['BFTN Status', sale.bftn_status === 'yes' ? (sale.bftn_received ? 'Received' : 'Pending') : 'No'],
        ['BFTN Received Date', sale.bftn_received_at],
        ['BFTN Amount', formatCurrency(sale.bftn_amount)],
        ['Net Cash', formatCurrency(sale.net_cash)],
    ];

    modal.querySelector('h3').textContent = `Sale #${sale.id} Details`;
    const payments = Array.isArray(sale.payments) ? sale.payments : [];
    paymentHistoryElement.innerHTML = `
        <section>
            <h4 class="text-xs font-medium uppercase text-gray-500">Payment History (${payments.length})</h4>
            ${payments.length ? `<div class="mt-2 grid grid-cols-1 gap-2 md:grid-cols-2">${payments.map((payment, index) => `
                <article class="rounded-md border border-gray-200 bg-gray-50 p-3">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-semibold text-gray-900">${escapeHtml(payment.payment_method || 'Unknown method')}</span>
                        <span class="font-semibold text-green-700">${formatCurrency(payment.amount)}</span>
                    </div>
                    <dl class="mt-2 grid grid-cols-1 gap-1 text-xs text-gray-600">
                        <div><dt class="inline font-medium">Payment #:</dt> <dd class="inline">${index + 1}</dd></div>
                        <div><dt class="inline font-medium">Date:</dt> <dd class="inline">${escapeHtml(formatDetailValue(payment.payment_datetime || payment.paid_date))}</dd></div>
                        <div><dt class="inline font-medium">Transaction ID:</dt> <dd class="inline break-all">${escapeHtml(formatDetailValue(payment.transaction_id))}</dd></div>
                        ${payment.remark ? `<div><dt class="inline font-medium">Remark:</dt> <dd class="inline break-words">${escapeHtml(payment.remark)}</dd></div>` : ''}
                    </dl>
                </article>`).join('')}</div>` : '<p class="mt-2 text-sm text-gray-500">No payment records.</p>'}
        </section>`;

    const verificationEntries = Array.isArray(sale.verification_timeline) ? sale.verification_timeline : [];
    let previousVerificationAt = sale.created_at;
    timeline.innerHTML = verificationEntries.length
        ? verificationEntries.map((verification, index) => {
                const elapsed = formatElapsedTime(previousVerificationAt, verification.verified_at);
                previousVerificationAt = verification.verified_at;

                return `<article class="min-w-0 rounded-md border border-gray-200 bg-gray-50 p-3 dark:border-gray-600 dark:bg-gray-800">
                    <div class="flex items-center gap-2">
                        <span class="h-2 w-2 shrink-0 rounded-full bg-blue-600" aria-hidden="true"></span>
                        <p class="text-sm font-semibold text-gray-900">${escapeHtml(formatDetailValue(verification.status).replaceAll('-', ' '))}</p>
                    </div>
                    <p class="mt-2 text-xs text-gray-600 dark:text-gray-300">${escapeHtml(formatDetailValue(verification.verified_by))}</p>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">${escapeHtml(formatDateTime(verification.verified_at))}</p>
                    <p class="mt-2 text-xs font-medium text-blue-700 dark:text-blue-300">${index === 0 ? 'After sale creation' : 'After previous step'}: ${escapeHtml(elapsed)}</p>
                </article>`;
            }).join('')
        : '<p class="text-sm text-gray-500">No verification history recorded.</p>';

    details.innerHTML = fields.map(([label, value]) => `
        <div class="border-b border-gray-100 pb-2">
            <dt class="text-xs font-medium uppercase text-gray-500">${escapeHtml(label)}</dt>
            <dd class="mt-1 break-words text-sm font-medium text-gray-900">${escapeHtml(formatDetailValue(value))}</dd>
        </div>`).join('');
}

function closeSaleDetails() {
    const modal = document.getElementById('saleReportDetailModal');
    modal?._closeModal?.();
}

export function initializeSaleDetailsModal(table, selectDetails = sale => sale) {
    if (!table) return;
    table.addEventListener('click', event => {
        const trigger = event.target.closest('.sale-report-detail-trigger');
        if (!trigger || !window.jQuery || !window.jQuery.fn.DataTable.isDataTable(table)) return;
        const sale = window.jQuery(table).DataTable().row(trigger.closest('tr')).data();
        if (sale) {
            showSaleDetails(selectDetails(sale));
            document.getElementById('saleReportDetailModal')?._openModal?.();
        }
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') closeSaleDetails();
    });
}
