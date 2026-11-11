import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import { escapeHtml } from '../../resources/js/utils/escape-html.js';

const source = readFileSync(new URL('../../resources/js/pages/sale-status.js', import.meta.url), 'utf8');
const renderer = source.slice(source.indexOf('function renderJourney('), source.indexOf('const columns = ['));
const renderJourney = vm.runInNewContext(`${renderer}\nrenderJourney`, { escapeHtml });
const createdAtRenderer = source.slice(source.indexOf('function renderCreatedAt('), source.indexOf('function renderJourney('));
const renderCreatedAt = vm.runInNewContext(`${createdAtRenderer}\nrenderCreatedAt`, { escapeHtml });

test('created at stacks Bangladesh date and time immediately before actions', () => {
    const value = '2026-11-11T08:30:00.000000Z';
    const html = renderCreatedAt(value, 'display');
    assert.match(html, /flex flex-col gap-1 whitespace-nowrap/);
    assert.match(html, /<span>11 Nov 2026<\/span>\s*<span>02:30 pm<\/span>/);
    assert.equal(renderCreatedAt(value, 'export'), '11 Nov 2026 02:30 pm');
    assert.equal(renderCreatedAt(null, 'display'), '-');
    assert.equal(renderCreatedAt('invalid', 'display'), '-');
    const view = readFileSync(new URL('../../resources/views/ship_ticket_sales/sales.blade.php', import.meta.url), 'utf8');
    assert.match(view, /'Created At',\s*'Action',/);
    assert.match(source, /data: "created_at",\s*title: "Created At",\s*render: renderCreatedAt,\s*},\s*{\s*data: null,\s*orderable: false,\s*render: \(data, type, row\) => createActionButtons\(row\)/);
});

test('journey cell stacks departure and return and escapes values', () => {
    const html = renderJourney({ journey_date: '2026-11-01', return_date: '<script>' }, 'display');
    assert.match(html, /flex-col/);
    assert.ok(html.indexOf('Departure:') < html.indexOf('Return:'));
    assert.match(html, /2026-11-01/);
    assert.match(html, /&lt;script&gt;/);
    assert.doesNotMatch(html, /<script>/);
});

test('journey handles absent dates and provides plain export text', () => {
    assert.match(renderJourney({}, 'display'), /Not specified/);
    assert.equal(renderJourney({ journey_date: '2026-11-01' }, 'export'), 'Departure: 2026-11-01; Return: Not specified');
    const view = readFileSync(new URL('../../resources/views/ship_ticket_sales/sales.blade.php', import.meta.url), 'utf8');
    assert.match(view, /'Ship Name',\s*'Journey',/);
    assert.match(source, /title: "Journey",\s*render: renderJourney/);
});
