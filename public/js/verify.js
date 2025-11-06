// Function to delete sale
    async function varifySale(btn) {
        const saleId = btn.dataset.id;
        const status = btn.dataset.status;
        console.log(status);

        const isConfirmed = await Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
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
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                const result = await response.json();
                if (result.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Sale has been successfully deleted.',
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
                        text: 'Failed to delete sale. Please try again later.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white'
                        }
                    });
                }
            } catch (error) {
                console.error('Error deleting sale:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while deleting the sale.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    customClass: {
                        confirmButton: 'bg-red-600 text-white'
                    }
                });
            }
        }
    }