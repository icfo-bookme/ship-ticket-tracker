
<script> 

async function handleDeleteClick(btn) {
    const id = btn.dataset.id;
    const { isConfirmed } = await Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to delete this package?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
    });
        if (isConfirmed) {
            try {
                // Send DELETE request to delete the package
                const response = await fetch(`/ship-packages/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                });

                const result = await response.json();
                if (result.success) {
                    // Show success message
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Package has been successfully deleted.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-950 text-white'
                        }
                    });

                    // Reload the package list
                    getList();
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete package. Please try again later.',
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
                    text: 'An error occurred while deleting the package.',
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