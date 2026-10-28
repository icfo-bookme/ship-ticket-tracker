import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/services/sale-package-filter.js', import.meta.url), 'utf8');
const flush = () => new Promise((resolve) => setImmediate(resolve));
const response = (data, total = data.length) => ({ ok: true, redirected: false, json: async () => ({ data, recordsFiltered: total }) });

function setup(fetch) {
    class Option {
        constructor(text, value) { this.text = text; this.value = String(value); }
    }
    const select = () => ({
        value: '', disabled: true, options: [], attributes: {}, events: new Map(),
        dataset: { packagesUrl: '/ship-packages/__SHIP_ID__' },
        replaceChildren(...options) { this.options = options; this.value = options[0]?.value ?? ''; },
        add(option) { this.options.push(option); },
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener(name, callback) { this.events.set(name, callback); },
    });
    const shipFilter = select();
    const packageFilter = select();
    const message = { textContent: '' };
    let changes = 0;
    const initialize = vm.runInNewContext(source.replace('export function', 'function') + '; initializeSalePackageFilter;', {
        Option, URL, AbortController, fetch, window: { location: { origin: 'https://example.test' } },
        setTimeout: () => 1, clearTimeout: () => {},
    });
    const controls = initialize({ shipFilter, packageFilter, message, onChange: () => { changes++; } });
    return { shipFilter, packageFilter, message, controls, changes: () => changes };
}

test('loads every package across endpoint pages and safely uses package names as text', async () => {
    const starts = [];
    const app = setup(async (url) => {
        const offset = Number(url.searchParams.get('start'));
        starts.push(offset);
        return response(Array.from({ length: offset === 0 ? 100 : 2 }, (_, index) => ({ id: offset + index + 1, name: '<script>Package</script>' })), 102);
    });
    app.shipFilter.value = '1';
    app.shipFilter.events.get('change')();
    assert.equal(app.packageFilter.disabled, true);
    await flush();
    assert.deepEqual(starts, [0, 100]);
    assert.equal(app.packageFilter.options.length, 103);
    assert.equal(app.packageFilter.options[0].text, 'All Packages');
    assert.equal(app.packageFilter.options[1].text, '<script>Package</script>');
    assert.equal(app.packageFilter.disabled, false);
    assert.equal(app.changes(), 1);
    app.packageFilter.events.get('change')();
    assert.equal(app.changes(), 2);
});

test('ignores an old ship response after a newer ship has been selected', async () => {
    let finishOld;
    let oldSignal;
    const app = setup((url, options) => {
        if (url.pathname.endsWith('/1')) {
            oldSignal = options.signal;
            return new Promise((resolve) => { finishOld = resolve; });
        }
        return Promise.resolve(response([{ id: 2, name: 'New Ship Package' }]));
    });
    app.shipFilter.value = '1';
    app.shipFilter.events.get('change')();
    app.shipFilter.value = '2';
    app.shipFilter.events.get('change')();
    await flush();
    assert.equal(oldSignal.aborted, true);
    finishOld(response([{ id: 1, name: 'Old Ship Package' }]));
    await flush();
    assert.equal(app.packageFilter.options[1].text, 'New Ship Package');
});

test('clear cancels pending loads and prevents their results from restoring a selection', async () => {
    let finish;
    const app = setup(() => new Promise((resolve) => { finish = resolve; }));
    app.shipFilter.value = '1';
    app.shipFilter.events.get('change')();
    app.shipFilter.value = '';
    app.controls.reset();
    finish(response([{ id: 1, name: 'Old Package' }]));
    await flush();
    assert.equal(app.packageFilter.disabled, true);
    assert.equal(app.packageFilter.options.length, 1);
    assert.equal(app.packageFilter.options[0].text, 'Select a ship first');
    assert.equal(app.packageFilter.value, '');
});

test('shows useful empty and failed load states', async () => {
    let failed = false;
    const app = setup(async () => failed ? { ok: false } : response([]));
    app.shipFilter.value = '1';
    app.shipFilter.events.get('change')();
    await flush();
    assert.equal(app.packageFilter.disabled, true);
    assert.match(app.message.textContent, /No packages/);
    failed = true;
    app.shipFilter.events.get('change')();
    await flush();
    assert.equal(app.packageFilter.disabled, true);
    assert.match(app.message.textContent, /Could not load/);
    assert.equal(app.packageFilter.attributes['aria-busy'], 'false');
});

test('table refresh resets pagination only when the caller requests it', () => {
    const tableSource = readFileSync(new URL('../../resources/js/services/data-table.js', import.meta.url), 'utf8');
    const events = new Map();
    const resets = [];
    const table = { id: 'salesTable', dataset: { tableOptions: '{}' }, classList: { remove() {} }, before() {} };
    const jquery = () => ({ on() {}, DataTable: () => ({ ajax: { reload: (_, reset) => resets.push(reset) } }) });
    jquery.fn = { dataTable: { isDataTable: () => false } };
    const initialize = vm.runInNewContext(tableSource.replace('export function', 'function') + '; initializeDataTable;', {
        window: { jQuery: jquery },
        document: {
            readyState: 'complete', getElementById: () => null,
            createElement: () => ({ setAttribute() {}, classList: { add() {}, remove() {} } }),
            addEventListener: (name, callback) => events.set(name, callback),
        },
    });
    initialize({ table, columns: [] });
    const refresh = events.get('data-table:refresh');
    refresh({ detail: { tableId: 'salesTable', resetPaging: true } });
    refresh({ detail: { tableId: 'salesTable' } });
    refresh({ detail: { tableId: 'otherTable', resetPaging: true } });
    assert.deepEqual(resets, [true, false]);
});
