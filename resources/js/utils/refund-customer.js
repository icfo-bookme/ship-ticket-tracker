import { escapeHtml } from './escape-html.js';

export function renderRefundCustomer(name, type, sale) {
    if (type !== 'display') {
        return name || 'N/A';
    }

    const contacts = [
        ['Phone', sale?.customer_mobile],
        ['WhatsApp', sale?.whatsapp],
        ['WhatsApp username', sale?.whatsapp_username],
    ].filter(([, value]) => String(value ?? '').trim() !== '');

    return `<div class="flex flex-col gap-1"><span>${escapeHtml(name || 'N/A')}</span>${contacts.map(([label, value]) =>
        `<span class="text-xs text-gray-600">${label}: ${escapeHtml(value)}</span>`
    ).join('')}</div>`;
}
