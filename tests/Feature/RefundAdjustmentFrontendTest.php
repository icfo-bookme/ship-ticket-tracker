<?php

use Symfony\Component\Process\Process;

it('includes refund credit in received and due and removes it when the checkbox is cleared', function () {
    $script = <<<'JS'
        import fs from 'node:fs';
        import vm from 'node:vm';
        import assert from 'node:assert/strict';
        const elements = new Map();
        const element = (id, value = '') => {
            const field = {
                value, disabled: false, checked: false, textContent: '', classList: { toggle() {} },
                listeners: {}, addEventListener(event, callback) { this.listeners[event] = callback; },
            };
            elements.set(id, field);
            return field;
        };
        element('refund_id', '7');
        element('refund_adjustment_amount', '800');
        element('refundAdjustmentPayment');
        element('adjustFromRefund').checked = true;
        element('refundAdjustmentFields');
        element('received_amount');
        element('bftn_pending_amount');
        element('due_amount');
        element('extra_received_amount');
        element('total_payable', '1200');
        const context = vm.createContext({ document: { getElementById: id => elements.get(id) }, console });
        const source = fs.readFileSync('resources/js/pages/ship-ticket-sales.js', 'utf8')
            .replace('const ticketSystem = new TicketSalesSystem();', 'globalThis.TicketSalesSystem = TicketSalesSystem;');
        vm.runInContext(source, context);
        const system = Object.create(context.TicketSalesSystem.prototype);
        system.selectors = { classes: { paymentEntry: '.payment-entry', paymentAmountInput: '.amount' } };
        const payment = (amount, bftn) => ({ querySelector: selector => selector === '.payment-is-bftn' ? { checked: bftn } : { value: amount } });
        system.getElements = () => [payment('200', false), payment('100', true)];
        system.getValue = id => elements.get(id).value;
        system.setValue = (id, value) => { elements.get(id).value = value; };
        system.saveDraftSoon = () => {};
        element('refundAdjustment');
        element('refundAdjustmentBalance');
        system.setupRefundAdjustment();
        let loads = 0;
        system.loadRefundAdjustments = () => { loads++; };
        elements.get('adjustFromRefund').listeners.change();
        assert.equal(loads, 1);
        system.refundOptions.set('7', { available_amount: 800 });
        elements.get('refund_id').listeners.change();
        assert.equal(elements.get('refund_adjustment_amount').value, '800.00');
        system.updateRefundAdjustmentPayment();
        assert.equal(elements.get('received_amount').value, '1000.00');
        assert.equal(elements.get('bftn_pending_amount').value, '100.00');
        assert.equal(elements.get('due_amount').value, '100.00');
        assert.equal(elements.get('refundAdjustmentPayment').textContent, 'Refund Adjusted: 800.00');
        system.toggleRefundAdjustment(false);
        assert.equal(elements.get('received_amount').value, '200.00');
        assert.equal(elements.get('due_amount').value, '900.00');
        assert.equal(elements.get('refund_id').disabled, true);
        assert.equal(elements.get('refund_id').value, '');
        assert.equal(elements.get('refundAdjustmentPayment').textContent, '');
        console.log('Refund adjustment totals passed');
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->getOutput())->toContain('Refund adjustment totals passed');
});

it('loads the edit dropdown and limits new credit to due without changing existing payments', function () {
    $script = <<<'JS'
        import assert from 'node:assert/strict';
        import { initializeRefundAdjustment } from './resources/js/components/refund-adjustment.js';
        const elements = new Map();
        for (const id of ['refundAdjustment', 'adjustFromRefund', 'refundAdjustmentFields', 'refund_id', 'refund_adjustment_amount', 'refundAdjustmentBalance', 'refundAdjustmentPayment']) {
            elements.set(id, {
                value: '', checked: false, disabled: false, dataset: {}, options: [], listeners: {},
                classList: { toggle() {} },
                addEventListener(event, listener) { this.listeners[event] = listener; },
                dispatchEvent(event) { this.listeners[event.type]?.(event); },
                replaceChildren(...options) { this.options = options; this.value = ''; },
                add(option) { this.options.push(option); },
                setCustomValidity(message) { this.validationMessage = message; },
            });
        }
        globalThis.document = { getElementById: id => elements.get(id) };
        globalThis.Option = class { constructor(text, value) { this.text = text; this.value = value; } };
        elements.get('refundAdjustment').dataset = { saleId: '99', url: '/refund-adjustments' };
        globalThis.fetch = async () => ({ ok: true, json: async () => ({ data: [
            { id: 7, sales_id: 1, customer_name: 'Customer', customer_mobile: '01712345678', available_amount: 800, can_adjust: true },
            { id: 8, sales_id: 99, available_amount: 100, can_adjust: true },
            { id: 9, sales_id: 2, available_amount: 100, can_adjust: false },
        ] }) });
        let due = 500;
        let previewCredit = 0;
        let adjustment;
        adjustment = initializeRefundAdjustment({ remainingDue: () => due, onChange: () => { previewCredit = adjustment?.amount() || 0; } });
        const checkbox = elements.get('adjustFromRefund');
        checkbox.checked = true;
        checkbox.dispatchEvent(new Event('change'));
        await new Promise(setImmediate);
        const select = elements.get('refund_id');
        const amount = elements.get('refund_adjustment_amount');
        assert.deepEqual(select.options.map(option => option.value), ['', '7', '9']);
        assert.equal(select.options.at(-1).disabled, true);
        select.value = '7';
        select.dispatchEvent(new Event('change'));
        assert.equal(amount.value, '500.00');
        assert.equal(amount.max, '500.00');
        assert.equal(previewCredit, 500);
        due = 300;
        adjustment.updateConstraints();
        assert.ok(amount.validationMessage.includes('remaining sale due'));
        amount.value = '200';
        amount.dispatchEvent(new Event('input'));
        assert.equal(amount.validationMessage, '');
        assert.equal(previewCredit, 200);
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
        assert.equal(amount.disabled, true);
        assert.equal(previewCredit, 0);
        console.log('Edit refund dropdown passed');
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->getOutput())->toContain('Edit refund dropdown passed');
});
