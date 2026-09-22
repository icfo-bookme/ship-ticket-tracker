<script>
async function handleDeleteClick(btn) {
    if (btn.dataset.isSuperAdmin === '1') {
        Swal.fire({
            title: 'Not allowed!',
            text: 'The Super Admin role cannot be deleted.',
            icon: 'warning',
            confirmButtonText: 'OK',
        });
        return;
    }

    const id = btn.dataset.id;
    const { isConfirmed } = await Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to delete the role "${btn.dataset.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
    });

    if (isConfirmed) {
        try {
            const response = await fetch(`/roles/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            });

            const result = await response.json();
            if (result.success) {
                Swal.fire({
                    title: 'Deleted!',
                    text: result.message,
                    icon: 'success',
                    confirmButtonText: 'OK',
                    customClass: { confirmButton: 'bg-blue-950 text-white' }
                });
                getList();
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: result.message || 'Failed to delete the role.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error deleting role:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error deleting the role.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    }
}
</script>