import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

test('DOB typing does not rewrite max and age validation runs on blur and submit', () => {
    const source = readFileSync(new URL('../../resources/js/pages/ship-ticket-sales.js', import.meta.url), 'utf8');
    const method = source.slice(source.indexOf('    setupBirthDateValidation() {'), source.indexOf('    initializeSelectors() {'));
    const events = new Map();
    let validity = '';
    let maxWrites = 0;
    const error = { textContent: '', classList: { toggle() {}, add() {} } };
    const input = {
        value: '2999-01-01',
        set max(value) { maxWrites++; },
        matches: () => true,
        setCustomValidity(value) { validity = value; },
        classList: { toggle() {}, remove() {} },
        parentElement: { querySelector: () => error },
        focus() {},
    };
    const form = {
        addEventListener: (name, handler) => events.set(name, handler),
        querySelectorAll: () => [input],
        querySelector: () => input,
    };
    vm.runInNewContext(`({${method}}).setupBirthDateValidation()`, { document: { getElementById: () => form } });

    events.get('input')({ target: input });
    assert.equal(validity, '');
    assert.equal(maxWrites, 0);
    events.get('focusout')({ target: input });
    assert.match(validity, /18 years/);
    let prevented = false;
    events.get('submit')({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);

    input.value = '1990-01-01';
    events.get('focusout')({ target: input });
    assert.equal(validity, '');
    input.value = '';
    events.get('focusout')({ target: input });
    assert.equal(validity, '');
});
