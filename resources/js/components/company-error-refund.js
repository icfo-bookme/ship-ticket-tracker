export function calculateCompanyLoss({ enabled, lossType, grossAmount, partnerShareAmount, customPercent }) {
    if (!enabled) return 0;
    const amount = lossType === 'partner_share'
        ? Number(partnerShareAmount || 0)
        : Number(grossAmount || 0) * Number(customPercent || 0) / 100;
    return Math.round((amount + Number.EPSILON) * 100) / 100;
}

export function initializeCompanyErrorRefund(onChange) {
    const checkbox = document.getElementById('companyErrorInput');
    const fields = document.getElementById('companyErrorFields');
    const type = document.getElementById('companyLossTypeInput');
    const percent = document.getElementById('companyLossPercentInput');
    const percentField = document.getElementById('companyLossPercentField');
    const reason = document.getElementById('companyErrorReasonInput');
    const preview = document.getElementById('companyLossAmountPreview');

    function updateFields() {
        const enabled = checkbox.checked;
        const custom = enabled && type.value === 'custom';
        fields.classList.toggle('hidden', !enabled);
        fields.classList.toggle('flex', enabled);
        percentField.classList.toggle('hidden', !custom);
        type.disabled = !enabled;
        reason.disabled = !enabled;
        reason.required = enabled;
        percent.disabled = !custom;
        percent.required = custom;
        percent.setCustomValidity('');
        onChange();
    }

    [checkbox, type].forEach((element) => element.addEventListener('change', updateFields));
    percent.addEventListener('input', () => { percent.setCustomValidity(''); onChange(); });

    return {
        reset(loss = null) {
            checkbox.checked = Boolean(loss);
            type.value = loss?.loss_type || 'partner_share';
            percent.value = loss?.loss_percent ?? '';
            reason.value = loss?.reason || '';
            updateFields();
        },
        updatePreview(grossAmount, partnerShareAmount, customerChargeAmount) {
            const loss = calculateCompanyLoss({ enabled: checkbox.checked, lossType: type.value, grossAmount, partnerShareAmount, customPercent: percent.value });
            preview.textContent = loss.toFixed(2);
            const uncovered = Math.round(Math.max(partnerShareAmount - customerChargeAmount, 0) * 100) / 100;
            percent.setCustomValidity(checkbox.checked && type.value === 'custom' && loss < uncovered
                ? 'Company loss must cover the partner share not covered by the customer charge.' : '');
        },
        valid() {
            return !checkbox.checked || (type.reportValidity() && (percent.disabled || percent.reportValidity()) && reason.reportValidity());
        },
        payload() {
            return checkbox.checked ? {
                is_company_error: true,
                company_loss_type: type.value,
                ...(type.value === 'custom' ? { company_loss_percent: percent.value } : {}),
                company_error_reason: reason.value,
            } : { is_company_error: false };
        },
    };
}
