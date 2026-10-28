import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/layout/sale-status-counters.js', import.meta.url), 'utf8');
const flush = () => new Promise((resolve) => setImmediate(resolve));
const response = (counts, status = 200) => ({ status, ok: status === 200, redirected: false, json: async () => counts });

function setup(fetch) {
    const documentEvents = new Map();
    const windowEvents = new Map();
    const intervals = new Map();
    let timerId = 0;
    const links = ['pending', 'payment-verified'].map((status) => {
        const classes = new Set(['hidden']);
        const badge = { textContent: '', classList: { toggle: (name, hidden) => hidden ? classes.add(name) : classes.delete(name) } };
        const tooltip = { textContent: '' };
        return {
            dataset: { statusCounter: status, label: status === 'pending' ? 'Pending' : 'Payment Verified' },
            hidden: false, badge, tooltip, classes, attributes: {},
            classList: { toggle: () => {} },
            querySelector: (selector) => selector === '[data-count-badge]' ? badge : tooltip,
            setAttribute(name, value) { this.attributes[name] = value; },
        };
    });
    const counters = { dataset: { url: '/sales-status-counts' }, hidden: false, classList: { toggle: () => {} }, querySelectorAll: () => links };
    const document = {
        hidden: false, querySelector: () => counters,
        addEventListener: (name, callback) => documentEvents.set(name, callback),
    };
    vm.runInNewContext(source, {
        document, window: { addEventListener: (name, callback) => windowEvents.set(name, callback) },
        fetch, AbortController, setTimeout: () => 1, clearTimeout: () => {},
        setInterval: (callback) => { intervals.set(++timerId, callback); return timerId; },
        clearInterval: (id) => intervals.delete(id),
    });
    return { document, counters, links, documentEvents, windowEvents, intervals };
}

test('hides zero badges, caps large counts and announces full totals', async () => {
    const app = setup(async () => response({ pending: 0, 'payment-verified': 123 }));
    await flush();
    assert.equal(app.links[0].classes.has('hidden'), true);
    assert.equal(app.links[1].badge.textContent, '99+');
    assert.equal(app.links[1].tooltip.textContent, 'Payment Verified — 123 sales');
    assert.equal(app.links[1].attributes['aria-label'], 'Payment Verified — 123 sales');
});

test('coalesces refresh events while a request is already running', async () => {
    let calls = 0;
    let finish;
    const app = setup(() => {
        calls++;
        return calls === 1 ? new Promise((resolve) => { finish = resolve; }) : Promise.resolve(response({ pending: 2 }));
    });
    for (let index = 0; index < 5; index++) app.documentEvents.get('data-table:refresh')();
    assert.equal(calls, 1);
    finish(response({ pending: 1 }));
    await flush();
    assert.equal(calls, 2);
    assert.equal(app.links[0].badge.textContent, '2');
});

test('pauses background polling and resumes when visible or restored from browser cache', async () => {
    let calls = 0;
    const app = setup(async () => { calls++; return response({ pending: 1 }); });
    await flush();
    app.document.hidden = true;
    [...app.intervals.values()][0]();
    assert.equal(calls, 1);
    app.document.hidden = false;
    app.documentEvents.get('visibilitychange')();
    await flush();
    assert.equal(calls, 2);
    app.windowEvents.get('pagehide')();
    assert.equal(app.intervals.size, 0);
    app.windowEvents.get('pageshow')({ persisted: true });
    await flush();
    assert.equal(app.intervals.size, 1);
    assert.equal(calls, 3);
});

test('removes revoked statuses and stops requests after access is denied', async () => {
    let calls = 0;
    const app = setup(async () => response({ pending: 5 }, ++calls === 1 ? 200 : 403));
    await flush();
    assert.equal(app.links[1].hidden, true);
    app.documentEvents.get('sales:counts-refresh')();
    await flush();
    assert.equal(app.counters.hidden, true);
    app.documentEvents.get('sales:counts-refresh')();
    assert.equal(calls, 2);
});
