import { createCrudPage } from '../components/crud-page';
import { refreshDataTable } from '../services/api';
import { initializeDataTable } from '../services/data-table.js';
import { escapeHtml } from '../utils/escape-html';

const page = document.getElementById('saleDraftsPage');
const form = document.getElementById('saleDraftForm');
const departureDateFilter = document.getElementById('departureDateFilter');
const returnDateFilter = document.getElementById('returnDateFilter');
const shipFilter = document.getElementById('draftShipFilter');
const categoryFilter = document.getElementById('draftCategoryFilter');
const formShip = document.getElementById('draftShip');
const departureContainer = document.getElementById('draftDepartureCategories');
const returnContainer = document.getElementById('draftReturnCategories');
const departureHint = document.getElementById('draftDepartureHint');
const returnHint = document.getElementById('draftReturnHint');
const returnSection = document.getElementById('draftReturnSection');

let formPackages = [];

const dateOnly = (value) => value ? String(value).split('T')[0] : '-';
const formDate = (value) => value ? String(value).slice(0, 10) : '';

function categorySummary(categories = []) {
    if (!categories.length) return '-';

    return `<div class="flex flex-col gap-1">${categories.map((category) =>
        `<span>${escapeHtml(category.category_name)} (${category.journey_type} x ${Number(category.quantity) || 0})</span>`,
    ).join('')}</div>`;
}

const columns = [
    { data: 'id' },
    { data: 'departure_date', render: dateOnly },
    { data: 'return_date', render: dateOnly },
    { data: 'ship.name', defaultContent: '-' },
    { data: 'categories', render: categorySummary },
    { data: 'details', render: (data) => escapeHtml(data).replace(/\n/g, '<br>') },
    { data: 'note', render: (data) => escapeHtml(data || '-') },
    { data: 'created_at', render: dateOnly },
    {
        data: null,
        orderable: false,
        searchable: false,
        render: (data, type, row) => type !== 'display' ? '' : `<div class="flex gap-2"><button type="button" class="editBtn rounded bg-blue-600 px-2 py-1 text-xs text-white" data-id="${row.id}">Edit</button><button type="button" class="deleteBtn rounded bg-red-600 px-2 py-1 text-xs text-white" data-id="${row.id}">Delete</button></div>`,
    },
];

const filters = () => ({
    departure_date: departureDateFilter.value,
    return_date: returnDateFilter.value,
    ship_id: shipFilter.value,
    category_id: categoryFilter.value,
});

async function fetchShipPackages(shipId) {
    if (!shipId) return [];

    const response = await fetch(`/ship-packages/${encodeURIComponent(shipId)}?length=100`, {
        headers: { Accept: 'application/json' },
    });
    if (!response.ok) throw new Error('Unable to load ticket categories.');

    const payload = await response.json();
    return Array.isArray(payload) ? payload : (payload.data || []);
}

function savedQuantities(categories = []) {
    return new Map(categories.map((category) => [
        `${category.journey_type}:${category.ship_package_id ?? category.package_id}`,
        Number(category.quantity) || 0,
    ]));
}

function appendCategoryInputs(container, packages, journeyType, quantities) {
    container.replaceChildren();
    packages.forEach((ticketPackage) => {
        const wrapper = document.createElement('div');
        wrapper.className = 'grid grid-cols-1 items-center gap-2 sm:grid-cols-[1fr_8rem]';

        const label = document.createElement('label');
        label.className = 'text-sm text-gray-700';
        label.textContent = `${ticketPackage.name} · ${Number(ticketPackage.price || 0).toFixed(2)}`;

        const quantity = document.createElement('input');
        quantity.type = 'number';
        quantity.min = '0';
        quantity.step = '1';
        quantity.value = String(quantities.get(`${journeyType}:${ticketPackage.id}`) || 0);
        quantity.dataset.packageId = ticketPackage.id;
        quantity.dataset.packageName = ticketPackage.name;
        quantity.dataset.journeyType = journeyType;
        quantity.setAttribute('aria-label', `${journeyType} quantity for ${ticketPackage.name}`);
        quantity.className = 'w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';

        wrapper.append(label, quantity);
        container.append(wrapper);
    });
}

function renderFormCategories(quantities = new Map()) {
    appendCategoryInputs(departureContainer, formPackages, 'departure', quantities);
    departureHint.classList.toggle('hidden', formPackages.length > 0);
    departureHint.textContent = formShip.value ? 'No categories available for this ship.' : 'Select a ship to load categories.';

    const hasReturnDate = Boolean(document.getElementById('returnDate').value);
    returnSection.classList.toggle('hidden', !hasReturnDate);
    appendCategoryInputs(returnContainer, hasReturnDate ? formPackages : [], 'return', quantities);
    returnHint.classList.toggle('hidden', !hasReturnDate || formPackages.length > 0);
    returnHint.textContent = formShip.value ? 'No categories available for this ship.' : 'Select a ship to load categories.';
}

function selectedCategories() {
    const categories = { departure: [], return: [] };

    form.querySelectorAll('[data-package-id][data-journey-type]').forEach((input) => {
        const quantity = Number.parseInt(input.value, 10) || 0;
        if (quantity < 1) return;

        categories[input.dataset.journeyType].push({
            package_id: Number(input.dataset.packageId),
            name: input.dataset.packageName,
            quantity,
        });
    });

    return categories;
}

async function loadFormCategories(selected = []) {
    const quantities = savedQuantities(selected);
    formPackages = await fetchShipPackages(formShip.value);
    renderFormCategories(quantities);
}

async function loadCategoryFilter() {
    const shipId = shipFilter.value;
    categoryFilter.replaceChildren();
    categoryFilter.add(new Option(shipId ? 'All categories' : 'Select a ship first', ''));
    categoryFilter.disabled = !shipId;

    if (!shipId) return;

    try {
        const packages = await fetchShipPackages(shipId);
        packages.forEach((ticketPackage) => {
            categoryFilter.add(new Option(ticketPackage.name, ticketPackage.id));
        });
    } catch (error) {
        categoryFilter.add(new Option('Unable to load categories', ''));
        console.error(error);
    }
}

initializeDataTable({ table: document.getElementById('saleDraftsTable'), columns, filters });

document.addEventListener('DOMContentLoaded', () => {
    createCrudPage({
        tableId: 'saleDraftsTable',
        formId: 'saleDraftForm',
        baseUrl: page.dataset.baseUrl,
        transformRecord: (draft) => ({
            ...draft,
            departure_date: formDate(draft.departure_date),
            return_date: formDate(draft.return_date),
        }),
        fields: ['departure_date', 'return_date', 'ship_id', 'details', 'note'],
        prepareData: (data) => ({ ...data, ticket_categories: selectedCategories() }),
        getList: () => refreshDataTable('saleDraftsTable'),
        onEdit: async (editForm, draft) => {
            editForm.querySelector('[data-submit-label]')?.replaceChildren(document.createTextNode('Update Draft'));
            await loadFormCategories(draft.categories || []);
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
    });

    formShip.addEventListener('change', async () => {
        try {
            await loadFormCategories();
        } catch (error) {
            departureHint.textContent = error.message;
            departureHint.classList.remove('hidden');
        }
    });

    document.getElementById('returnDate').addEventListener('change', () => {
        renderFormCategories(savedQuantities(selectedCategories().departure.concat(selectedCategories().return)));
    });

    form.addEventListener('reset', () => {
        window.setTimeout(() => {
            formPackages = [];
            renderFormCategories();
            document.querySelector('#saleDraftForm [data-submit-label]')?.replaceChildren(document.createTextNode('Save Draft'));
        });
    });

    document.getElementById('clearDraftButton')?.addEventListener('click', () => {
        form.reset();
        delete form.dataset.recordId;
    });

    [departureDateFilter, returnDateFilter].forEach((filter) => {
        filter.addEventListener('change', () => refreshDataTable('saleDraftsTable'));
    });
    shipFilter.addEventListener('change', async () => {
        categoryFilter.value = '';
        await loadCategoryFilter();
        refreshDataTable('saleDraftsTable');
    });
    categoryFilter.addEventListener('change', () => refreshDataTable('saleDraftsTable'));

    document.getElementById('clearDraftFilters').addEventListener('click', async () => {
        departureDateFilter.value = '';
        returnDateFilter.value = '';
        shipFilter.value = '';
        categoryFilter.value = '';
        await loadCategoryFilter();
        refreshDataTable('saleDraftsTable');
    });
});
