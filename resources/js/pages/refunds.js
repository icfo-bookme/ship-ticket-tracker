import { initializeDataTable } from '../services/data-table.js';
import { apiRequest, refreshDataTable } from '../services/api.js';

// Refundable sales table and refund workflow UI.
if (document.getElementById('salesTable')) {
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");

    const columns = [
        {
            data: null,
            orderable: false,
            searchable: false,
            render: (data, type, row) => type !== 'display' ? '' :
                `<input type="checkbox" data-permission="refunds.create" class="selectSale" data-id="${row.id}" />`,
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
        { data: "discount_amount" },
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

    const filters = () => ({
        ship_id: shipFilter.value,
        company_id: companyFilter.value,
        journey_date: journeyDateFilter.value,
    });
    initializeDataTable({ table: document.getElementById('salesTable'), columns, filters });

    function formatDate(dateString) {
        if (!dateString) return "N/A";

        return new Date(dateString).toLocaleDateString("en-US", {
            year: "numeric",
            month: "long",
            day: "numeric",
        });
    }

    function createActionButtons(sale) {
        if (document.getElementById('refundPage')?.dataset.canManage !== '1') {
            return '';
        }

        return `
                        <div class="flex gap-2 items-center justify-center">
                            <a data-permission="sales.edit" href="${document.getElementById('refundPage').dataset.saleUrl.replace('__ID__', sale.id)}"
                                class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit" aria-label="Edit sale"></a>
                            <button data-permission="refunds.create" class="bg-blue-900 text-white px-2 py-1 rounded verifyRefund"
                                data-id="${sale.id}"
                                data-received_total_amount="${sale.ticket_fee}"
                                data-due-amount="${sale.due_amount || 0}"
                                data-ticket-fee="${sale.ticket_fee || 0}"
                                data-discount-amount="${sale.discount_amount || 0}"
                                data-other-fee="${sale.other_fee || 0}"
                                data-extra-received="${sale.extra_received_amount || 0}"
                                data-extra-remaining="${sale.extra_remaining_amount || 0}"
                                data-categories="${encodeURIComponent(JSON.stringify(sale.categories || []))}"
                                data-status="shipped">
                                Partial Refund
                            </button>
                        </div>`;
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
            const response = await fetch(document.getElementById('refundPage').dataset.fullRefundUrl, {
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
                refreshDataTable('salesTable');
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
            filter?.addEventListener("change", () => refreshDataTable('salesTable'));
        });

        clearFiltersBtn?.addEventListener("click", () => {
            [shipFilter, companyFilter, journeyDateFilter].forEach((filter) => {
                if (filter) filter.value = "";
            });
            refreshDataTable('salesTable');
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
            if (button) document.dispatchEvent(new CustomEvent('refund:open', { detail: { button, editing: false } }));
        });
    }

    document.addEventListener("DOMContentLoaded", bindRefundTableEvents);
}

let currentRefundSaleId = null;
let currentRefundRequestId = null;
let currentRefundEditing = false;
let refreshRefundList = null;
let currentRefundDueAmount = 0;
let currentRefundTicketFee = 0;
let currentRefundSaleDiscount = 0;
let currentRefundSaleOtherFee = 0;
let currentRefundSelectedGrossAmount = 0;
let currentRefundExtraReceivedAmount = 0;
let currentRefundExtraRemainingAmount = 0;

function roundRefundMoney(amount) {
    return Math.round((amount + Number.EPSILON) * 100) / 100;
}

function updateRefundPreview() {
    const grossAmount = currentRefundSelectedGrossAmount;
    const chargePercent = Number(document.getElementById('customerChargePercentInput')?.value || 0);
    const partnerPercent = Number(document.getElementById('partnerSharePercentInput')?.value || 0);
    const refundDiscount = currentRefundTicketFee > 0
        ? roundRefundMoney(Math.min(currentRefundSaleDiscount, currentRefundTicketFee) * grossAmount / currentRefundTicketFee)
        : 0;
    const otherFeeDeduction = grossAmount > 0 ? roundRefundMoney(currentRefundSaleOtherFee) : 0;
    const amountAfterFeeAndDiscount = Math.max(roundRefundMoney(grossAmount - otherFeeDeduction - refundDiscount), 0);
    const customerCharge = roundRefundMoney(grossAmount * chargePercent / 100);
    const partnerShare = roundRefundMoney(grossAmount * partnerPercent / 100);
    const refundAfterAdjustments = Math.max(roundRefundMoney(amountAfterFeeAndDiscount - customerCharge), 0);

    document.getElementById('refundAmountInput').value = amountAfterFeeAndDiscount.toFixed(2);
    const dueAdjustment = roundRefundMoney(Math.min(currentRefundDueAmount, refundAfterAdjustments));
    const finalRefund = roundRefundMoney(refundAfterAdjustments - dueAdjustment);

    document.getElementById('partnerSharePreview').textContent = partnerShare.toFixed(2);
    document.getElementById('customerChargePreview').textContent = customerCharge.toFixed(2);
    document.getElementById('companyRetainedPreview').textContent = roundRefundMoney(customerCharge - partnerShare).toFixed(2);
    document.getElementById('refundBreakdownNote').textContent =
        `Ticket subtotal ${grossAmount.toFixed(2)} - other fee ${otherFeeDeduction.toFixed(2)} - discount ${refundDiscount.toFixed(2)} = ${amountAfterFeeAndDiscount.toFixed(2)}`;
    document.getElementById('refundDueAmountPreview').textContent = currentRefundDueAmount.toFixed(2);
    document.getElementById('dueAdjustmentPreview').textContent = dueAdjustment.toFixed(2);
    document.getElementById('payableRefundPreview').textContent = finalRefund.toFixed(2);
    document.getElementById('finalCustomerRefundPreview').textContent = finalRefund.toFixed(2);
    document.getElementById('refundExtraReceivedPreview').textContent = currentRefundExtraReceivedAmount.toFixed(2);
    document.getElementById('refundExtraAvailablePreview').textContent = currentRefundExtraRemainingAmount.toFixed(2);
}

document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('refundModal');
    if (!modal) return;
    const customerChargeInput = document.getElementById('customerChargePercentInput');
    const partnerShareInput = document.getElementById('partnerSharePercentInput');

    [customerChargeInput, partnerShareInput].forEach((input) => input.addEventListener('input', updateRefundPreview));

    function closeModal() {
        modal._closeModal();
    }

    document.getElementById('closeModalBtn').addEventListener('click', closeModal);

    document.getElementById('submitRefundBtn').addEventListener('click', async () => {
        const refundTickets = Array.from(document.querySelectorAll('.refundCategoryQuantity'))
            .reduce((total, input) => total + Number(input.value || 0), 0);
        const ticketSelections = Array.from(document.querySelectorAll('.refundCategoryQuantity')).map((input) => ({
            category_id: input.dataset.categoryId,
            refunded_quantity: Number(input.value || 0),
        })).filter((selection) => selection.refunded_quantity > 0);
        const remark = document.getElementById('remark').value;
        const customerChargePercent = customerChargeInput.value;
        const partnerSharePercent = partnerShareInput.value;
        const invalidQuantity = Array.from(document.querySelectorAll('.refundCategoryQuantity'))
            .find((input) => !input.reportValidity());

        if (invalidQuantity) return;

        if (currentRefundSelectedGrossAmount <= 0 || !refundTickets || !ticketSelections.length) {
            Swal.fire({
                title: 'Error!',
                text: 'Please enter refund amount and number of tickets.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
            return;
        }

        const isConfirmed = await Swal.fire({
            title: 'Are you sure?',
            text: 'Send this refund request to the partner?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, send request',
            cancelButtonText: 'Cancel',
        });

        if (!isConfirmed.isConfirmed) return;

        try {
            const refundPage = document.getElementById('refundPage');
            const endpoint = currentRefundEditing
                ? refundPage.dataset.updateRefundUrl.replace('__ID__', currentRefundRequestId)
                : refundPage.dataset.partialRefundUrl.replace('__ID__', currentRefundSaleId);
            const response = await fetch(endpoint, {
                method: currentRefundEditing ? 'PUT' : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    refunded_amount: currentRefundSelectedGrossAmount,
                    refunded_number_of_tickets: refundTickets,
                    ticket_selections: ticketSelections,
                    customer_charge_percent: customerChargePercent,
                    partner_share_percent: partnerSharePercent,
                    remark: remark,
                }),
            });

            const responseText = await response.text();
            let result;

            try {
                result = JSON.parse(responseText);
            } catch (parseError) {
                throw new Error(`Refund request failed with HTTP ${response.status}.`);
            }

            if (result.success) {
                Swal.fire({
                    title: 'Success!',
                    text: currentRefundEditing ? 'Refund request updated.' : 'Refund request sent to partner.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
                closeModal();
                document.getElementById('refundAmountInput').value = '';
                document.getElementById('remark').value = '';
                if (typeof refreshRefundList === 'function') refreshRefundList();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: result.message || result.errors?.ticket_selections?.[0] || 'Failed to process refund.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        } catch (error) {
            console.error('Error processing refund:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while processing the refund.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});

function openRefundModal(btn, editing = false) {
    currentRefundSaleId = btn.dataset.id;
    currentRefundRequestId = editing ? btn.dataset.requestId : null;
    currentRefundEditing = editing;
    document.querySelector('#refundModal h3').textContent = btn.dataset.refundType === 'bulk'
        ? 'Bulk Refund Request'
        : 'Partial Refund Request';
    document.getElementById('submitRefundBtn').textContent = editing
        ? 'Update Refund Request'
        : 'Submit Refund Request';
    refreshRefundList = () => refreshDataTable('salesTable');
    document.getElementById('receivedAmountInput').value = btn.dataset.received_total_amount;
    currentRefundDueAmount = Number(btn.dataset.dueAmount || 0);
    currentRefundTicketFee = Number(btn.dataset.ticketFee || btn.dataset.received_total_amount || 0);
    currentRefundSaleDiscount = Number(btn.dataset.discountAmount || 0);
    currentRefundSaleOtherFee = Number(btn.dataset.otherFee || 0);
    currentRefundExtraReceivedAmount = Number(btn.dataset.extraReceived || 0);
    currentRefundExtraRemainingAmount = Number(btn.dataset.extraRemaining || 0);
    document.getElementById('customerChargePercentInput').value = editing ? (btn.dataset.customerCharge || 0) : 0;
    document.getElementById('partnerSharePercentInput').value = editing ? (btn.dataset.partnerShare || 0) : 0;
    document.getElementById('refundAmountInput').value = editing ? (btn.dataset.grossAmount || 0) : 0;
    document.getElementById('selectedTicketCountInput').value = 0;
    document.getElementById('remark').value = '';
    const categories = JSON.parse(decodeURIComponent(btn.dataset.categories || '[]'));
    document.getElementById('refundCategoryRows').innerHTML = categories.map((category) => {
        const price = Number(category.package?.price || category.unit_amount || 0);
        const roundTripTotal = Number(category.package?.round_trip_price || 0);
        const returnPrice = roundTripTotal > 0 ? roundTripTotal - price : price;
        const purchasedQuantity = Number(category.quantity || category.purchased_quantity || 0);
        const refundedQuantity = editing
            ? Number(category.refunded_quantity ?? category.quantity_refunded ?? 0)
            : 0;

        return `<div class="grid grid-cols-5 items-center gap-2 rounded border p-2 text-sm">
                <span class="col-span-2">${escapeHtml(category.package?.name || 'Ticket')}</span>
                <span>${escapeHtml(category.type || '')}</span>
                <span>Qty: ${purchasedQuantity}<br>৳${(category.type === 'return' ? returnPrice : price).toFixed(2)}</span>
                <input type="number" min="0" max="${purchasedQuantity}" value="${refundedQuantity}"
                    data-category-id="${category.category_id ?? category.id}" data-price="${category.type === 'return' ? returnPrice : price}"
                    class="refundCategoryQuantity w-full rounded border px-2 py-1" aria-label="Return quantity">
            </div>`;
    }).join('');
    document.querySelectorAll('.refundCategoryQuantity').forEach((input) => input.addEventListener('input', () => {
        const quantity = Number(input.value || 0);
        const maximum = Number(input.max || 0);
        input.setCustomValidity(quantity > maximum
            ? `Refund quantity cannot exceed the purchased quantity (${maximum}).`
            : '');
        updateRefundAmount();
    }));
    updateRefundAmount();
    if (editing && !Number(document.getElementById('selectedTicketCountInput').value || 0)) {
        document.getElementById('selectedTicketCountInput').value = btn.dataset.ticketCount || 0;
    }
    document.getElementById('refundModal').classList.remove('hidden');
    document.getElementById('refundModal').classList.add('flex');
    updateRefundPreview();
}

function updateRefundAmount() {
    const inputs = Array.from(document.querySelectorAll('.refundCategoryQuantity'));
    const amount = inputs.reduce((total, input) => total + Number(input.value || 0) * Number(input.dataset.price || 0), 0);
    const ticketCount = inputs.reduce((total, input) => total + Number(input.value || 0), 0);
    currentRefundSelectedGrossAmount = amount;
    document.getElementById('selectedTicketCountInput').value = ticketCount;
    updateRefundPreview();
}

document.addEventListener('refund:open', (event) => {
    openRefundModal(event.detail.button, event.detail.editing);
});

import { escapeHtml } from '../utils/escape-html';
