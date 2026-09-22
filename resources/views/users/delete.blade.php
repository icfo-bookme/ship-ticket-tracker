<script>
async function handleDeleteClick(btn) {
    if (btn.dataset.isSelf === '1') {
        Swal.fire({
            title: 'Not allowed!',
            text: 'You cannot delete your own account.',
            icon: 'warning',
            confirmButtonText: 'OK',
        });
        return;
    }

    if (btn.dataset.isSuperAdmin === '1') {
        Swal.fire({
            title: 'Not allowed!',
            text: 'The Super Admin account cannot be deleted.',
            icon: 'warning',
            confirmButtonText: 'OK',
        });
        return;
    }

    const id = btn.dataset.id;
    const { isConfirmed } = await Swal.fire({
        title: 'Are you sure?',
        text: `Do you want to delete the user "${btn.dataset.name}"?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
    });

    if (isConfirmed) {
        try {
            const response = await fetch(`/users/${id}`, {
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
                    text: result.message || 'Failed to delete the user.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                });
            }
        } catch (error) {
            console.error('Error deleting user:', error);
            Swal.fire({
                title: 'Error!',
                text: 'There was an error deleting the user.',
                icon: 'error',
                confirmButtonText: 'OK',
            });
        }
    }
}
</script>