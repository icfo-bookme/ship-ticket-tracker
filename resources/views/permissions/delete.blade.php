<script>
async function handleDeleteClick(btn) {
    if (Number(btn.dataset.rolesCount) > 0) {
        Swal.fire({
            title: 'Not allowed!',
            text: 'This permission is assigned to one or more roles. Remove it from those roles first.',
            icon: 'warning',
            confirmButtonText: 'OK',
        });
        return;
    }

    const id = btn.dataset.id;
    const { isConfirmed } = await Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to delete the permission "${btn.dataset.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
    });

    if (isConfirmed) {
        try {
            const response = await fetch(`/permissions/${id}`, {
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
                    text: result.message || 'Failed to delete the permission.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error deleting permission:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error deleting the permission.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    }
}
</script>