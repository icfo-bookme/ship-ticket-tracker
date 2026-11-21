import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const source = readFileSync(new URL('../../resources/js/pages/ship-ticket-sales.js', import.meta.url), 'utf8');
const extract = (start, end) => source.slice(source.indexOf(`    ${start}`), source.indexOf(`    ${end}`));

function collectionForm() {
    const checkbox = () => ({ checked: false, addEventListener(event, handler) { this.change = handler; } });
    const office = checkbox();
    const coxsbazar = checkbox();
    const address = { value: 'Dhaka', required: true };
    const wrapper = { hidden: false, classList: { toggle(name, value) { wrapper.hidden = value; } } };
    const fields = { collect_from_office: office, collect_from_coxsbazar: coxsbazar, address, addressFieldWrapper: wrapper };
    return { office, coxsbazar, address, wrapper, document: { getElementById: id => fields[id] } };
}

test('create form allows one office and requires an address only for courier delivery', () => {
    const form = collectionForm();
    const manager = vm.runInNewContext(`({clearFieldError() {}, ${extract('setupOfficeCollectionToggle() {', 'setupMainActionListeners()')}})`, { document: form.document });
    manager.setupOfficeCollectionToggle();
    assert.equal(form.address.required, true);
    form.office.checked = true;
    form.office.change();
    assert.equal(form.address.required, false);
    form.coxsbazar.checked = true;
    form.coxsbazar.change();
    assert.equal(form.office.checked, false);
    assert.equal(form.wrapper.hidden, true);
    form.coxsbazar.checked = false;
    form.coxsbazar.change();
    assert.equal(form.address.required, true);
    assert.equal(form.wrapper.hidden, false);
    assert.equal(form.address.value, 'Dhaka');
});

test('edit form restores saved office selection and toggles offices', () => {
    const form = collectionForm();
    form.coxsbazar.checked = true;
    const edit = readFileSync(new URL('../../resources/js/pages/edit-sale.js', import.meta.url), 'utf8');
    const start = edit.indexOf("    const checkboxes = ['collect_from_office'");
    vm.runInNewContext(edit.slice(start, edit.indexOf('\n});', start)), { document: form.document });
    assert.equal(form.wrapper.hidden, true);
    form.office.checked = true;
    form.office.change();
    assert.equal(form.coxsbazar.checked, false);
    form.office.checked = false;
    form.office.change();
    assert.equal(form.address.required, true);
});

test('local draft restores checkbox state without overwriting checkbox values', () => {
    const form = collectionForm();
    const manager = vm.runInNewContext(`({getFormField() { return null; }, ${extract('restoreSimpleFields(fields) {', 'restoreNamedFields(fields, prefix)')}})`, { document: form.document });
    manager.restoreSimpleFields({ collect_from_office: '0', collect_from_coxsbazar: '1' });
    assert.equal(form.office.checked, false);
    assert.equal(form.coxsbazar.checked, true);
    manager.restoreSimpleFields({ collect_from_office: '1', collect_from_coxsbazar: '0' });
    assert.equal(form.office.checked, true);
    assert.equal(form.coxsbazar.checked, false);
});

test('review identifies each collection method', () => {
    const form = collectionForm();
    const manager = vm.runInNewContext(`({constants: {FIELD_LABELS: {}}, getElement(selector) { return document.getElementById(selector.slice(1)); }, ${extract('generateBasicInfo(formData) {', 'formatReviewValue(field, value)')}})`, { document: form.document });
    assert.match(manager.generateBasicInfo({}), /Courier delivery/);
    form.office.checked = true;
    assert.match(manager.generateBasicInfo({}), /Collect from office/);
    form.office.checked = false;
    form.coxsbazar.checked = true;
    assert.match(manager.generateBasicInfo({}), /Bazar office/);
});
