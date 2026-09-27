<script>
    async function varifySale(btn, getList) {
        const saleId = btn.dataset.id;
        const status = btn.dataset.status;
        const isOfficeCollection = status === 'collect_from_office';

        const isConfirmed = await Swal.fire({
            title: isOfficeCollection ? 'Mark as collected?' : 'Are you sure?',
            text: isOfficeCollection ? 'Confirm that the customer collected the ticket from the office.' : 'You want to verify this!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: isOfficeCollection ? 'Yes, Collected' : 'Yes, Verify it!',
            customClass: {
                confirmButton: 'bg-blue-950 text-white',
                cancelButton: 'bg-red-500 text-white'
            }

        });

        if (isConfirmed.isConfirmed) {
            try {
                const response = await fetch(`/sale/verify/${saleId}/${status}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                    },
                });

                const result = await response.json();
                if (result.success) {
                    Swal.fire({
                        title: isOfficeCollection ? 'Collected!' : 'Verified!',
                        text: isOfficeCollection ? 'Ticket marked as collected from office.' : 'Sale has been successfully Verified.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-950 text-white'
                        }
                    });

                    getList();
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: result.message || 'Could not update the sale status.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white'
                        }
                    });
                }
            } catch (error) {
                console.error('Error updating sale status:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the sale status.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'bg-red-600 text-white'
                    }
                });
            }
        }
    }
</script>
