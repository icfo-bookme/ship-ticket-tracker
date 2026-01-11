
<script> 

async function handleDeleteClick(btn) {
    const id = btn.dataset.id;
    const isConfirmed = confirm('Are you sure you want to delete this sale?');
        if (isConfirmed) {
            try {
                // Send DELETE request to delete the sale
                const response = await fetch(`/ships/${id}`, {
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
                        text: 'Sale has been successfully deleted.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-blue-950 text-white'
                        }
                    });

                    // Reload the sales list
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

</script>