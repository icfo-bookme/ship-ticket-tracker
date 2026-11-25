<?php

use Symfony\Component\Process\Process;

it('renders remaining ticket quantities and opens new requests from completed refunds', function () {
    $script = <<<'JS'
        import assert from 'node:assert/strict';
        import fs from 'node:fs';
        import vm from 'node:vm';
        import { renderRemainingRefundButton, renderRefundCategoryRows, refundDiscountForSelection } from './resources/js/components/refund-remaining.js';
        const categories = [
            { id: 1, package: { name: '<Deck>' }, type: 'departure', quantity: 2, already_requested_quantity: 2, remaining_quantity: 0, unit_price: 100 },
            { id: 2, package: { name: 'Deck' }, type: 'return', quantity: 2, already_requested_quantity: 0, remaining_quantity: 2, unit_price: 100 },
        ];
        const html = renderRefundCategoryRows(categories, false);
        assert.ok(html.includes('&lt;Deck&gt;'));
        assert.ok(html.includes('>Departure</h4>'));
        assert.ok(html.includes('>Return</h4>'));
        assert.ok(html.includes('Purchased: 2'));
        assert.ok(html.includes('Already Requested/Refunded: 2'));
        assert.match(html, /max="0" value="0" disabled/);
        assert.match(html, /max="2" value="0"/);
        const editing = renderRefundCategoryRows([{ ...categories[1], refunded_quantity: 1 }], true);
        assert.match(editing, /max="2" value="1"/);
        assert.equal(refundDiscountForSelection(0.01, 0.03, 0.02, 0, 0.01), 0);
        assert.equal(refundDiscountForSelection(0.01, 0.03, 0.01, 0.01, 0.01), 0.01);
        assert.equal(refundDiscountForSelection(200, 400, 40, 20, 200), 20);
        const events = {}, tableEvents = {};
        const page = { dataset: { status: 'completed', canRefund: '1' } };
        const table = { addEventListener: (name, handler) => { tableEvents[name] = handler; } };
        let columns, opened;
        const document = {
            getElementById: id => id === 'refundRequestPage' ? page : id === 'requestedRefundsTable' ? table : null,
            addEventListener: (name, handler) => { events[name] = handler; },
            dispatchEvent: event => { opened = event; },
        };
        const source = fs.readFileSync('resources/js/pages/refund-requests.js', 'utf8').replace(/^import .*;\r?\n/gm, '');
        vm.runInNewContext(source, {
            document, window: { location: { search: '' } }, URLSearchParams,
            initializeDataTable: options => { columns = options.columns; },
            renderRemainingRefundButton, renderRefundCustomer() {},
            refreshDataTable() {}, escapeHtml: value => String(value ?? ''),
            CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        });
        const row = { can_refund_remaining: true, sale: { id: 7, ticket_value: 400 }, refundable_categories: categories, refund_discount_available: 20, refund_gross_remaining: 200 };
        assert.ok(columns.at(-1).render(null, 'display', row).includes('Refund Remaining Tickets'));
        row.can_refund_remaining = false;
        assert.ok(!columns.at(-1).render(null, 'display', row).includes('remainingRefundBtn'));
        row.can_refund_remaining = true;
        page.dataset.canRefund = '0';
        assert.ok(!columns.at(-1).render(null, 'display', row).includes('remainingRefundBtn'));
        events.DOMContentLoaded();
        const button = { dataset: { id: '7' } };
        tableEvents.click({ target: { closest: selector => selector === '.remainingRefundBtn' ? button : null } });
        assert.equal(opened.type, 'refund:open');
        assert.equal(opened.detail.editing, false);
        assert.equal(opened.detail.button, button);
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->isSuccessful())->toBeTrue();
});

it('keeps refund phone numbers under customer names without separate mobile columns', function () {
    foreach (['refunds.js', 'refund-requests.js'] as $page) {
        $source = file_get_contents(resource_path('js/pages/'.$page));

        expect($source)->toContain('renderRefundCustomer(data, type, row')
            ->not->toMatch('/data:\s*[\'"](?:sale\.)?customer_mobile[\'"]/');
    }

    foreach (['index', 'requested'] as $view) {
        expect(file_get_contents(resource_path('views/refund/'.$view.'.blade.php')))
            ->not->toContain("'Mobile'");
    }
});

it('shows escaped customer contacts below the name in refund tables', function () {
    $script = <<<'JS'
        import assert from 'node:assert/strict';
        import { renderRefundCustomer } from './resources/js/utils/refund-customer.js';
        const sale = { customer_mobile: '01712345678', whatsapp: '+8801812345678', whatsapp_username: '<customer>' };
        const html = renderRefundCustomer('Customer <name>', 'display', sale);
        assert.ok(html.indexOf('Customer &lt;name&gt;') < html.indexOf('Phone: 01712345678'));
        assert.ok(html.includes('WhatsApp: +8801812345678'));
        assert.ok(html.includes('WhatsApp username: &lt;customer&gt;'));
        assert.ok(!html.includes('<customer>'));
        assert.equal(renderRefundCustomer('Customer', 'sort', sale), 'Customer');
        const missing = renderRefundCustomer(null, 'display', null);
        assert.ok(missing.includes('N/A'));
        assert.ok(!missing.includes('Phone:'));
        assert.ok(!missing.includes('WhatsApp'));
        const usernameOnly = renderRefundCustomer('Customer', 'display', { whatsapp_username: '@customer', whatsapp: ' ' });
        assert.ok(usernameOnly.includes('WhatsApp username: @customer'));
        assert.ok(!usernameOnly.includes('WhatsApp:'));
        JS;

    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->isSuccessful())->toBeTrue();
});

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

it('previews company loss and resets hidden fields between refund requests', function () {
    $script = <<<'JS'
        import assert from 'node:assert/strict';
        import { calculateCompanyLoss, initializeCompanyErrorRefund } from './resources/js/components/company-error-refund.js';
        const elements = new Map();
        for (const id of ['companyErrorInput', 'companyErrorFields', 'companyLossTypeInput', 'companyLossPercentInput', 'companyLossPercentField', 'companyErrorReasonInput', 'companyLossAmountPreview']) {
            elements.set(id, {
                value: '', checked: false, disabled: false, required: false, textContent: '', validity: '',
                classes: new Set(), listeners: {},
                classList: { toggle(name, enabled) { enabled ? elements.get(id).classes.add(name) : elements.get(id).classes.delete(name); } },
                addEventListener(event, callback) { this.listeners[event] = callback; },
                setCustomValidity(message) { this.validity = message; },
                reportValidity() { return !this.validity && (!this.required || this.value !== ''); },
            });
        }
        globalThis.document = { getElementById: id => elements.get(id) };
        let form;
        form = initializeCompanyErrorRefund(() => form?.updatePreview(1000, 200, 0));
        form.reset();
        assert.deepEqual(form.payload(), { is_company_error: false });
        assert.equal(elements.get('companyErrorReasonInput').disabled, true);
        assert.equal(elements.get('companyErrorFields').classes.has('hidden'), true);
        form.reset({ loss_type: 'partner_share', loss_percent: 20, reason: 'Wrong booking' });
        assert.equal(elements.get('companyLossAmountPreview').textContent, '200.00');
        assert.equal(elements.get('companyLossPercentInput').disabled, true);
        assert.equal(form.valid(), true);
        assert.equal(form.payload().company_loss_type, 'partner_share');
        form.reset({ loss_type: 'custom', loss_percent: 100, reason: 'Full company loss' });
        assert.equal(elements.get('companyLossAmountPreview').textContent, '1000.00');
        assert.equal(elements.get('companyLossPercentInput').required, true);
        assert.equal(form.payload().company_loss_percent, 100);
        elements.get('companyLossPercentInput').value = 10;
        form.updatePreview(1000, 200, 0);
        assert.equal(form.valid(), false);
        elements.get('companyLossPercentInput').value = 20;
        form.updatePreview(500, 100, 0);
        assert.equal(elements.get('companyLossAmountPreview').textContent, '100.00');
        assert.equal(form.valid(), true);
        form.reset();
        assert.equal(elements.get('companyErrorReasonInput').value, '');
        assert.equal(elements.get('companyLossPercentInput').value, '');
        assert.equal(elements.get('companyLossAmountPreview').textContent, '0.00');
        assert.equal(calculateCompanyLoss({ enabled: true, lossType: 'custom', grossAmount: 333.33, customPercent: 20 }), 66.67);
        console.log('Company error refund form passed');
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->getOutput())->toContain('Company error refund form passed');
});

it('preserves live refund searches while using URL search only as the initial value', function () {
    $script = <<<'JS'
        import fs from 'node:fs';
        import vm from 'node:vm';
        import assert from 'node:assert/strict';
        const source = fs.readFileSync('resources/js/pages/refund-requests.js', 'utf8');
        const pageSetup = source.slice(source.indexOf('const filters ='), source.indexOf('function formatRequestedDate'));
        const service = fs.readFileSync('resources/js/services/data-table.js', 'utf8').replace('export function', 'function');
        for (const locationSearch of ['', '?search=Rahim']) {
            let configuration;
            const table = {
                id: 'requestedRefundsTable',
                dataset: { tableOptions: JSON.stringify({ pageLength: 10 }), ajaxUrl: '/refund-requests/data?status=partner_approved' },
                classList: { remove() {} }, before() {},
            };
            const jquery = () => ({
                on() {},
                DataTable(options) { configuration = options; return { ajax: { reload() {} } }; },
            });
            jquery.fn = { dataTable: { isDataTable: () => false } };
            const document = {
                readyState: 'complete', addEventListener() {}, removeEventListener() {},
                getElementById(id) {
                    if (id === table.id) return table;
                    if (id.endsWith('-page-loader')) return null;
                    return { value: { requestedShip: '12', requestedCompany: '7', requestedJourneyDate: '2026-11-21' }[id] || '' };
                },
                createElement: () => ({ setAttribute() {}, classList: { add() {}, remove() {} } }),
            };
            vm.runInNewContext(`${service}\n${pageSetup}`, {
                document, window: { location: { search: locationSearch }, jQuery: jquery }, URLSearchParams, columns: [],
            });
            assert.equal(configuration.search?.search || '', locationSearch ? 'Rahim' : '');
            assert.equal(configuration.pageLength, 10);
            for (const term of ['Karim', '01712345678', '']) {
                const request = { search: { value: term, regex: false } };
                configuration.ajax.data(request);
                assert.equal(request.search.value, term);
                assert.equal(request.search.regex, false);
                assert.equal(request.ship_id, '12');
                assert.equal(request.company_id, '7');
                assert.equal(request.journey_date, '2026-11-21');
            }
        }
        JS;

    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->isSuccessful())->toBeTrue();
});

it('groups all sale categories under departure and return headings below the ship safely', function () {
    $script = <<<'JS'
        import fs from 'node:fs';
        import vm from 'node:vm';
        import assert from 'node:assert/strict';
        import { escapeHtml } from './resources/js/utils/escape-html.js';
        const source = fs.readFileSync('resources/js/pages/refunds.js', 'utf8');
        const helper = source.slice(source.indexOf('function renderRefundShip('), source.indexOf('// Refundable sales table'));
        const render = vm.runInNewContext(helper + '; renderRefundShip;', { escapeHtml });
        const sale = { ships: { name: 'Ship <One>' }, categories: [
            { package: { name: 'Deck' }, quantity: 2, type: 'departure' },
            { package: { name: 'Cabin <VIP>' }, quantity: 1, type: 'departure' },
            { package: { name: 'Deck' }, quantity: 3, type: 'return' },
            { package: { name: 'Cabin' }, quantity: 0, type: 'return' },
        ] };
        const html = render(null, 'display', sale);
        assert.ok(html.includes('Ship &lt;One&gt;'));
        const departure = html.slice(html.indexOf('>Departure</span>'), html.indexOf('>Return</span>'));
        const returning = html.slice(html.indexOf('>Return</span>'));
        assert.ok(departure.includes('Deck - 2 tickets'));
        assert.ok(departure.includes('Cabin &lt;VIP&gt; - 1 ticket'));
        assert.ok(!departure.includes('Deck - 3 tickets'));
        assert.ok(returning.includes('Deck - 3 tickets'));
        assert.ok(returning.includes('Cabin - 0 tickets'));
        assert.ok(!returning.includes('Deck - 2 tickets'));
        assert.equal((html.match(/>Departure<\/span>/g) || []).length, 1);
        assert.equal((html.match(/>Return<\/span>/g) || []).length, 1);
        assert.ok(html.indexOf('Ship &lt;One&gt;') < html.indexOf('>Departure</span>'));
        assert.ok(!html.includes('<VIP>'));
        assert.equal(render(null, 'export', sale), 'Ship <One> | Departure: Deck - 2 tickets, Cabin <VIP> - 1 ticket | Return: Deck - 3 tickets, Cabin - 0 tickets');
        assert.ok(render(null, 'display', {}).includes('Not available'));
        assert.ok(!render(null, 'display', { ship: { name: 'Ship Two' } }).includes('>Departure</span>'));
        const oneWay = render(null, 'display', { categories: [sale.categories[0]] });
        assert.ok(oneWay.includes('>Departure</span>'));
        assert.ok(!oneWay.includes('>Return</span>'));
        const unknown = render(null, 'display', { categories: [{ quantity: 1, type: 'unknown' }] });
        assert.ok(unknown.includes('Not specified'));
        assert.ok(unknown.includes('Ticket - 1 ticket'));
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->isSuccessful())->toBeTrue();
});

it('shows departure and return together in the refundable sales journey column', function () {
    $script = <<<'JS'
        import fs from 'node:fs';
        import vm from 'node:vm';
        import assert from 'node:assert/strict';
        import { escapeHtml } from './resources/js/utils/escape-html.js';
        const source = fs.readFileSync('resources/js/pages/refunds.js', 'utf8');
        const helper = source.slice(source.indexOf('function renderRefundJourney('), source.indexOf('// Refundable sales table'));
        const render = vm.runInNewContext(helper + '; renderRefundJourney;', { escapeHtml });
        const html = render({ journey_date: '2026-11-25', return_date: '2026-11-28' }, 'display');
        assert.ok(html.includes('Departure:</span> 2026-11-25'));
        assert.ok(html.includes('Return:</span> 2026-11-28'));
        assert.ok(html.indexOf('Departure:') < html.indexOf('Return:'));
        assert.ok(render({ journey_date: '2026-11-25' }, 'display').includes('Return:</span> Not specified'));
        assert.ok(render({ return_date: '<script>' }, 'display').includes('&lt;script&gt;'));
        assert.equal(render({ journey_date: '2026-11-25', return_date: '2026-11-28' }, 'export'), 'Departure: 2026-11-25; Return: 2026-11-28');
        assert.ok(source.includes('render: renderRefundJourney'));
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->isSuccessful())->toBeTrue();
});

it('opens the same sale details modal from report and refund rows and clears stale details', function () {
    $script = <<<'JS'
        import assert from 'node:assert/strict';
        import { initializeSaleDetailsModal } from './resources/js/components/sale-details-modal.js';
        const elements = new Map();
        const title = { textContent: '' };
        let opened = 0, closed = 0;
        const modal = { querySelector: () => title, _openModal() { opened++; }, _closeModal() { closed++; } };
        elements.set('saleReportDetailModal', modal);
        for (const id of ['saleReportDetails', 'saleReportPaymentHistory', 'saleReportVerificationTimeline']) elements.set(id, { innerHTML: '' });
        const events = {};
        globalThis.document = { getElementById: id => elements.get(id), addEventListener: (name, handler) => { events[name] = handler; } };
        let row;
        const jquery = () => ({ DataTable: () => ({ row: () => ({ data: () => row }) }) });
        jquery.fn = { DataTable: { isDataTable: () => true } };
        globalThis.window = { jQuery: jquery };
        const sale = { id: 42, customer_name: '<Customer>', ship_name: 'Test Ship', total_payable: 500, received_amount: 200, due_amount: 300,
            created_at: '2026-11-01T00:00:00Z', payments: [{ payment_method: '<Cash>', amount: 200, transaction_id: 'TRX-1' }],
            verification_timeline: [{ status: 'payment-verified', verified_by: '<Verifier>', verified_at: '2026-11-01T01:00:00Z' }] };
        for (const isRefund of [false, true]) {
            const tableEvents = {};
            const table = { addEventListener: (name, handler) => { tableEvents[name] = handler; } };
            initializeSaleDetailsModal(table, isRefund ? row => row.sale_details : row => row);
            const trigger = { closest: () => ({}) };
            const click = () => tableEvents.click({ target: { closest: () => trigger } });
            row = isRefund ? { sale_details: sale } : sale;
            click();
            assert.equal(title.textContent, 'Sale #42 Details');
            assert.ok(elements.get('saleReportDetails').innerHTML.includes('&lt;Customer&gt;'));
            assert.ok(elements.get('saleReportDetails').innerHTML.includes('300.00'));
            assert.ok(elements.get('saleReportPaymentHistory').innerHTML.includes('&lt;Cash&gt;'));
            assert.ok(elements.get('saleReportPaymentHistory').innerHTML.includes('200.00'));
            assert.ok(elements.get('saleReportVerificationTimeline').innerHTML.includes('&lt;Verifier&gt;'));
            const empty = { id: 43, payments: [], verification_timeline: [] };
            row = isRefund ? { sale_details: empty } : empty;
            click();
            assert.equal(title.textContent, 'Sale #43 Details');
            assert.ok(elements.get('saleReportPaymentHistory').innerHTML.includes('No payment records.'));
            assert.ok(!elements.get('saleReportPaymentHistory').innerHTML.includes('TRX-1'));
            assert.ok(elements.get('saleReportVerificationTimeline').innerHTML.includes('No verification history recorded.'));
            events.keydown({ key: 'Escape' });
        }
        assert.equal(opened, 4);
        assert.equal(closed, 2);
        JS;
    $process = new Process(['node', '--input-type=module', '-e', $script], base_path());
    $process->mustRun();

    expect($process->isSuccessful())->toBeTrue();
});
