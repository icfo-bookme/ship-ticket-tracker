<!-- Add Whatsapp Modal -->
<div id="add-modal" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-full sm:max-w-md">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700">
            <!-- Header -->
            <div
                class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Add WhatsApp Details
                </h3>
                <button data-modal-hide="add-modal" type="button"
                    class="text-gray-500 hover:bg-gray-200 rounded-lg p-2.5">
                    ✕
                </button>
            </div>

            <!-- Form -->
            <form id="createWhatsappForm">
                <div class="px-6 py-4 space-y-4">

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tag</label>
                        <input type="text" id="tag" required
                            class="mt-1 w-full px-4 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Whatsapp Number</label>
                        <input type="number" id="whatsapp_number" required
                            class="mt-1 w-full px-4 py-2 border rounded-md"
                            placeholder="8801XXXXXXXXX">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Form No</label>
                        <input type="text" id="form_no" required
                            class="mt-1 w-full px-4 py-2 border rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Form URL</label>
                        <input type="url" id="url" required
                            class="mt-1 w-full px-4 py-2 border rounded-md">
                    </div>

                    <div class="text-right pt-2">
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Save
                        </button>
                        <button type="button" data-modal-hide="add-modal"
                            class="ml-2 px-4 py-2 bg-gray-500 text-white rounded">
                            Cancel
                        </button>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('createWhatsappForm');
    const closeBtn = document.querySelector('[data-modal-hide="add-modal"]');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const data = {
            tag: document.getElementById('tag').value,
            whatsapp_number: document.getElementById('whatsapp_number').value,
            form_no: document.getElementById('form_no').value,
            url: document.getElementById('url').value,
        };

        fetch('/whatsapp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        })
        .then(res => {
            if (!res.ok) throw new Error('Failed');
            return res.json();
        })
        .then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Whatsapp details added successfully'
            });

            form.reset();
            closeBtn.click();
            getList(); // reload table
        })
        .catch(err => {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Something went wrong!'
            });
            console.error(err);
        });
    });
});
</script>
