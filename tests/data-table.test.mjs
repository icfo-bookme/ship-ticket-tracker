import { test } from 'node:test';
import assert from 'node:assert/strict';
import { initializeDataTable } from '../resources/js/services/data-table.js';

test('initializes after DOM readiness, preserves callbacks and refresh, and reports request failure', () => {
    const events = new Map();
    let options;
    let removed = false;
    let reloaded;
    let processingHidden = false;
    const alert = { setAttribute() {}, classList: { add() {}, remove() {} }, remove() {} };
    const table = {
        id: 'testTable',
        dataset: { ajaxUrl: '/users', buttonClass: 'button-style', tableOptions: JSON.stringify({ buttons: ['copy'], pageLength: 25, order: [] }) },
        classList: { remove() {} }, before() {},
    };
    const api = { ajax: { reload: (...args) => { reloaded = args; } } };
    const jq = () => ({
        on() {},
        DataTable(value) { options = value; return api; },
        closest() { return { find() { return { hide() { processingHidden = true; } }; } }; },
    });
    jq.fn = { dataTable: { isDataTable: () => false } };
    globalThis.window = { jQuery: jq };
    globalThis.document = {
        readyState: 'loading',
        getElementById: id => id === 'testTable' ? table : { remove() { removed = true; } },
        createElement: () => alert,
        addEventListener: (name, callback) => events.set(name, callback),
        removeEventListener: name => events.delete(name),
    };
    const columns = [{ data: 'id' }];
    let total;
    initializeDataTable({ table: 'testTable', columns, filters: () => ({ ship_id: 4 }), dataSrc: json => { total = json.total; return json.data; } });
    assert.equal(options, undefined);
    events.get('DOMContentLoaded')();
    assert.equal(options.scrollX, true);
    assert.equal(options.columns, columns);
    assert.deepEqual(options.buttons, ['copy']);
    assert.deepEqual(options.ajax.data({ draw: 1 }), { draw: 1, ship_id: 4 });
    assert.deepEqual(options.ajax.dataSrc({ total: 12, data: [{ id: 3 }] }), [{ id: 3 }]);
    assert.equal(total, 12);
    events.get('data-table:refresh')({ detail: { tableId: 'other' } });
    assert.equal(reloaded, undefined);
    events.get('data-table:refresh')({ detail: { tableId: 'testTable' } });
    assert.deepEqual(reloaded, [null, false]);
    options.ajax.error();
    assert.equal(removed, true);
    assert.equal(processingHidden, true);
    assert.match(alert.textContent, /Unable to load/);
});
