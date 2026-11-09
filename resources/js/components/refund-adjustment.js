export function initializeRefundAdjustment({ remainingDue, onChange }) {
    const container = document.getElementById('refundAdjustment');
    if (!container) return null;

    const checkbox = document.getElementById('adjustFromRefund');
    const fields = document.getElementById('refundAdjustmentFields');
    const select = document.getElementById('refund_id');
    const amount = document.getElementById('refund_adjustment_amount');
    const status = document.getElementById('refundAdjustmentBalance');
    const preview = document.getElementById('refundAdjustmentPayment');
    let refunds = new Map();
    let requestId = 0;

    const value = () => checkbox.checked && select.value && !amount.disabled
        ? Math.max(Number(amount.value) || 0, 0) : 0;

    function updateConstraints() {
        const refund = refunds.get(select.value);
        const maximum = Math.max(Math.min(Number(refund?.available_amount) || 0, remainingDue()), 0);
        amount.max = maximum.toFixed(2);
        amount.setCustomValidity(value() > maximum ? 'Adjustment cannot exceed the refund balance or remaining sale due.' : '');
    }

    function notifyChange() {
        updateConstraints();
        preview.textContent = value() > 0 ? `Refund Adjusted: ${value().toFixed(2)}` : '';
        onChange();
    }

    async function load() {
        const currentRequest = ++requestId;
        status.textContent = 'Loading refunds...';
        try {
            const response = await fetch(container.dataset.url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Unable to load refunds.');
            const payload = await response.json();
            if (currentRequest !== requestId || !checkbox.checked) return;
            const candidates = payload.data.filter(refund => String(refund.sales_id) !== container.dataset.saleId);
            refunds = new Map(candidates.map(refund => [String(refund.id), refund]));
            select.replaceChildren(new Option('Select a refund', ''));
            candidates.forEach(refund => {
                const option = new Option(`#${refund.id} / Sale #${refund.sales_id} - ${refund.customer_name} (${refund.customer_mobile}) - ${Number(refund.available_amount).toFixed(2)}${refund.can_adjust ? '' : ' - Awaiting approval'}`, String(refund.id));
                option.disabled = !refund.can_adjust;
                select.add(option);
            });
            status.textContent = candidates.length ? 'Select an approved refund.' : 'No refunds with an available balance found.';
            if (refunds.get(container.dataset.refundId)?.can_adjust) {
                select.value = container.dataset.refundId;
                select.dispatchEvent(new Event('change'));
                if (container.dataset.amount) amount.value = container.dataset.amount;
                container.dataset.refundId = '';
                container.dataset.amount = '';
            }
            notifyChange();
        } catch {
            if (currentRequest !== requestId) return;
            status.textContent = 'Unable to load refunds. Uncheck and check Adjust from refund to retry.';
        }
    }

    function toggle() {
        requestId++;
        fields.classList.toggle('hidden', !checkbox.checked);
        [select, amount].forEach(field => {
            field.disabled = !checkbox.checked;
            field.required = checkbox.checked;
            field.value = '';
        });
        notifyChange();
        if (checkbox.checked) load();
    }

    checkbox.addEventListener('change', toggle);
    select.addEventListener('change', () => {
        const refund = refunds.get(select.value);
        amount.value = refund ? Math.max(Math.min(Number(refund.available_amount), remainingDue()), 0).toFixed(2) : '';
        status.textContent = refund ? `Available refund balance: ${Number(refund.available_amount).toFixed(2)}` : 'Select an approved refund.';
        notifyChange();
    });
    amount.addEventListener('input', notifyChange);
    if (container.dataset.refundId) {
        checkbox.checked = true;
        toggle();
    }

    return { amount: value, updateConstraints };
}
