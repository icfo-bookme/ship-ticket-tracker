import { apiRequest } from '../services/api';

export function createCrudPage({
    tableId,
    formId,
    createFormId,
    updateFormId,
    createModalId,
    updateModalId,
    baseUrl,
    recordUrl,
    fields,
    getList,
    populate,
    onCreate,
    canDelete,
    prepareData,
    transformRecord,
    onEdit,
    afterSubmit,
}) {
    const tableBody = document.getElementById(`${tableId}Body`);
    const singleForm = formId ? document.getElementById(formId) : null;
    const createForm = singleForm || document.getElementById(createFormId);
    const updateForm = document.getElementById(updateFormId);

    const closeModal = (id) => {
        const modal = document.getElementById(id);
        modal?._closeModal?.();
        modal?.classList.add('hidden');
        modal?.classList.remove('flex');
    };

    const formData = (form) => {
        const data = Object.fromEntries(new FormData(form).entries());

        return prepareData ? prepareData(data, form) : data;
    };

    const submit = async (form, url, method, modalId, successMessage) => {
        try {
            const response = await apiRequest(url, { method, body: JSON.stringify(formData(form)) });
            await Swal.fire({ icon: 'success', title: 'Success', text: successMessage, timer: 1400, showConfirmButton: false });
            form.reset();
            delete form.dataset.recordId;
            const submitLabel = form.querySelector('[data-submit-label]')
                || document.querySelector(`[form="${form.id}"] [data-submit-label]`);
            submitLabel?.replaceChildren(document.createTextNode('Save'));
            afterSubmit?.(response, method);
            closeModal(modalId);
            getList();
        } catch (error) {
            const message = Object.values(error.errors || {}).flat().join('\n') || error.message || 'Please try again.';
            Swal.fire({ icon: 'error', title: 'Request failed', text: message });
        }
    };

    createForm?.addEventListener('submit', (event) => {
        event.preventDefault();
        const id = createForm.dataset.recordId;
        submit(createForm, id ? `${baseUrl}/${id}` : baseUrl, id ? 'PUT' : 'POST', id ? updateModalId : createModalId, id ? 'Record updated successfully.' : 'Record created successfully.');
    });

    tableBody?.addEventListener('click', async (event) => {
        const editButton = event.target.closest('.editBtn');
        if (editButton) {
            try {
                const recordEndpoint = recordUrl || `${baseUrl}/${editButton.dataset.id}`;
                const response = await apiRequest(recordEndpoint.replace('__ID__', editButton.dataset.id));
                const record = transformRecord ? transformRecord(response) : response;
                const targetForm = singleForm || updateForm;
                targetForm.dataset.recordId = record.id;
                if (populate) populate(targetForm, record);
                else fields.forEach((field) => {
                    const input = targetForm.elements.namedItem(field);
                    if (input) input.value = record[field] ?? '';
                });
                onEdit?.(targetForm, record);
                const submitLabel = document.querySelector(`#${updateModalId} [data-submit-label]`);
                if (submitLabel) submitLabel.textContent = 'Update';
                document.getElementById(updateModalId)?.classList.remove('hidden');
                document.getElementById(updateModalId)?.classList.add('flex');
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Unable to load record',
                    text: error.message || 'Please try again.',
                });
            }
            return;
        }

        const deleteButton = event.target.closest('.deleteBtn');
        if (!deleteButton) return;

        if (canDelete && !canDelete(deleteButton)) return;

        const confirmation = await Swal.fire({ title: 'Delete this record?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete' });
        if (!confirmation.isConfirmed) return;

        try {
            await apiRequest(`${baseUrl}/${deleteButton.dataset.id}`, { method: 'DELETE' });
            await Swal.fire({ icon: 'success', title: 'Deleted', timer: 1200, showConfirmButton: false });
            getList();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Delete failed', text: error.message || 'Please try again.' });
        }
    });

    if (singleForm) {
        document.querySelector(`[data-modal-target="${createModalId}"]`)?.addEventListener('click', () => {
            singleForm.reset();
            delete singleForm.dataset.recordId;
            onCreate?.(singleForm);
            const submitLabel = document.querySelector(`#${createModalId} [data-submit-label]`);
            if (submitLabel) submitLabel.textContent = 'Save';
        });
    }
}
