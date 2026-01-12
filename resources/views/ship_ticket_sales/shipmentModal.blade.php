<script>
    function varifyShipment(btn, getList) {
        const saleId = btn.dataset.id;
        const status = btn.dataset.status;

        // Show the confirmation Swal modal directly
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, Verify it!',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'bg-blue-950 text-white',
                cancelButton: 'bg-red-500 text-white'
            }
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`/sale/verify/${saleId}/${status}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({}) // No shipmentId needed
                    });

                    const resultData = await response.json();

                    if (resultData.success) {
                        Swal.fire({
                            title: 'Verified!',
                            text: 'Shipment has been successfully verified.',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'bg-blue-950 text-white'
                            }
                        });
                        getList(); // Refresh the list
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to verify shipment. Please try again later.',
                            icon: 'error',
                            confirmButtonText: 'OK',
                            customClass: {
                                confirmButton: 'bg-red-600 text-white'
                            }
                        });
                    }
                } catch (error) {
                    console.error('Error verifying shipment:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while verifying the shipment.',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        customClass: {
                            confirmButton: 'bg-red-600 text-white'
                        }
                    });
                }
            }
        });
    }

    // Attach the click events
    document.querySelectorAll(".shipmentIdEntryBtn").forEach((btn) => {
        btn.addEventListener("click", () => varifyShipment(btn, getList));
    });
</script>
