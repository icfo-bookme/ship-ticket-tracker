const config = document.getElementById('verifySalesConfig');
const urlTemplate = config?.dataset.urlTemplate;

const showError = (message) => {
    Swal.fire({
        title: 'Error!',
        text: message,
        icon: 'error',
        confirmButtonText: 'OK',
        buttonsStyling: false,
        customClass: {
            confirmButton: 'bg-red-600 text-white',
        },
    });
};

const verifySale = async ({ button, onSuccess }) => {
    const saleId = button.dataset.id;
    const status = button.dataset.status;
    const isOfficeCollection = status === 'collect_from_office';

    const confirmation = await Swal.fire({
        title: isOfficeCollection ? 'Mark as collected?' : 'Are you sure?',
        text: isOfficeCollection
            ? 'Confirm that the customer collected the ticket from the office.'
            : 'You want to verify this!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: isOfficeCollection ? 'Yes, Collected' : 'Yes, Verify it!',
        buttonsStyling: false,
    });

    if (!confirmation.isConfirmed || !urlTemplate) {
        return;
    }

    try {
        const url = urlTemplate
            .replace('__ID__', encodeURIComponent(saleId))
            .replace('__STATUS__', encodeURIComponent(status));
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            },
        });
        const result = await response.json();

        if (!response.ok || !result.success) {
            showError(result.message || 'Could not update the sale status.');
            return;
        }

        await Swal.fire({
            title: isOfficeCollection ? 'Collected!' : 'Verified!',
            text: isOfficeCollection
                ? 'Ticket marked as collected from office.'
                : 'Sale has been successfully verified.',
            icon: 'success',
            confirmButtonText: 'OK',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'bg-blue-950 text-white',
            },
        });

        onSuccess?.();
    } catch (error) {
        console.error('Error updating sale status:', error);
        showError('An error occurred while updating the sale status.');
    }
};

document.addEventListener('sale:verify', (event) => {
    verifySale(event.detail);
});
