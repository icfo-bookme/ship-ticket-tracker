import { escapeHtml } from '../utils/escape-html.js';

export function renderRemainingRefundButton(sale, categories, balances, label = 'Refund Remaining Tickets') {
    return `<button type="button" data-permission="refunds.create" class="bg-blue-900 text-white px-2 py-1 rounded remainingRefundBtn"
        data-id="${Number(sale.id)}" data-received_total_amount="${Number(sale.ticket_value || 0)}"
        data-due-amount="${Number(sale.due_amount || 0)}" data-discount-amount="${Number(sale.discount_amount || 0)}"
        data-extra-remaining="${Number(sale.extra_remaining_amount || 0)}"
        data-refund-discount-available="${Number(balances.refund_discount_available || 0)}"
        data-refund-gross-remaining="${Number(balances.refund_gross_remaining || 0)}"
        data-categories="${encodeURIComponent(JSON.stringify(categories || []))}">${escapeHtml(label)}</button>`;
}

export function renderRefundCategoryRows(categories, editing) {
    return ['departure', 'return'].map(journeyType => {
        const entries = categories.filter(category => category.type === journeyType);
        if (!entries.length) return '';
        return `<section class="flex flex-col gap-2"><h4 class="font-bold">${journeyType === 'departure' ? 'Departure' : 'Return'}</h4>${entries.map(category => {
            const purchased = Number(category.purchased_quantity ?? category.quantity ?? 0);
            const reserved = Number(category.already_requested_quantity || 0);
            const remaining = Math.max(Number(category.remaining_quantity ?? purchased), 0);
            const selected = editing ? Number(category.refunded_quantity ?? 0) : 0;
            const price = Number(category.unit_price || 0);
            return `<div class="grid grid-cols-1 items-center gap-2 rounded border p-2 text-sm sm:grid-cols-3">
                <span>${escapeHtml(category.package?.name || 'Ticket')}<br>${price.toFixed(2)}</span>
                <span>Purchased: ${purchased}<br>Already Requested/Refunded: ${reserved}<br>Remaining: ${remaining}</span>
                <input type="number" min="0" max="${remaining}" value="${selected}" ${remaining === 0 ? 'disabled' : ''}
                    data-category-id="${Number(category.category_id ?? category.id)}" data-price="${price}"
                    class="refundCategoryQuantity w-full rounded border px-2 py-1 disabled:bg-gray-100" aria-label="${escapeHtml(category.package?.name || 'Ticket')} ${journeyType} refund quantity">
            </div>`;
        }).join('')}</section>`;
    }).join('');
}

export function refundDiscountForSelection(gross, ticketValue, saleDiscount, available, remainingGross) {
    const proportional = ticketValue > 0 ? Math.round((Math.min(saleDiscount, ticketValue) * gross / ticketValue + Number.EPSILON) * 100) / 100 : 0;
    return Math.min(gross, available, gross >= remainingGross ? available : proportional);
}
