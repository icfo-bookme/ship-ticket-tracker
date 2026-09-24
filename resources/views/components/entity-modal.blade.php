@props([
    'id',
    'title',
    'submitText' => 'Save',
    'formId' => null,
    'maxWidth' => 'md',
    'hideFooter' => false,
    'submitClasses' => 'px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-400 disabled:opacity-50 disabled:cursor-not-allowed',
    'cancelClasses' => 'px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400',
])

@php
$maxWidthClass = [
    'sm' => 'sm:max-w-sm',
    'md' => 'sm:max-w-md',
    'lg' => 'sm:max-w-lg',
    'xl' => 'sm:max-w-xl',
    '2xl' => 'sm:max-w-2xl',
][$maxWidth];
@endphp

<div id="{{ $id }}" tabindex="-1" aria-hidden="true"
    class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative p-4 w-full max-w-2xl max-h-[calc(100vh-2rem)] {{ $maxWidthClass }}">
        <div class="relative bg-white rounded-lg shadow-sm dark:bg-gray-700 flex flex-col max-h-[calc(100vh-2rem)]">
            <!-- Header (fixed at top) -->
            <div
                class="flex items-center justify-between p-4 md:p-5 border-b rounded-t dark:border-gray-600 border-gray-200 shrink-0">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $title }}</h3>
                <button data-modal-hide="{{ $id }}" type="button"
                    class="text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg p-2.5">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd"
                            d="M6.293 4.293a1 1 0 0 1 1.414 0L10 6.586l2.293-2.293a1 1 0 1 1 1.414 1.414L11.414 8l2.293 2.293a1 1 0 1 1-1.414 1.414L10 9.414l-2.293 2.293a1 1 0 1 1-1.414-1.414L8.586 8 6.293 5.707a1 1 0 0 1 0-1.414z"
                            clip-rule="evenodd"></path>
                    </svg>
                    <span class="sr-only">Close modal</span>
                </button>
            </div>

            <!-- Body (scrollable content area) -->
            <div class="overflow-y-auto grow">
                {{ $slot }}
            </div>

            @unless ($hideFooter)
                <!-- Footer (fixed at bottom, never scrolls away) -->
                <div
                    class="flex justify-end gap-2 px-4 md:px-5 py-4 border-t border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-b shrink-0">
                    <button type="button" data-modal-hide="{{ $id }}" data-close-modal="{{ $id }}" class="{{ $cancelClasses }}">
                        Cancel
                    </button>
                    <button type="submit" {{ $formId ? 'form=' . $formId : '' }} class="{{ $submitClasses }}" data-submit-btn
                        data-saving-text="{{ $submitText === 'Update' || $submitText === 'Save Changes' ? 'Updating...' : 'Saving...' }}">
                        <span data-submit-label>{{ $submitText }}</span>
                    </button>
                </div>
            @endunless
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById(@json($id));

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            // Also call the page's own closeModal() if it exists
            // (keeps old page scripts' state in sync).
            if (typeof window.closeModal === 'function') {
                try { window.closeModal(); } catch (e) { /* ignore */ }
            }
        }
        modal._closeModal = closeModal;

        // Cancel / close buttons (also covers the header X button).
        modal.querySelectorAll('[data-modal-hide], [data-close-modal]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                closeModal();
            });
        });

        // Click outside the modal (on the backdrop) closes it.
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // Submit button loading state: the label switches to "Saving..." /
        // "Updating..." and the button is disabled from the moment the form is
        // submitted until the page script calls the reset helper.
        const submitBtn = modal.querySelector('[data-submit-btn]');
        const form = $formId ? document.getElementById(@json($formId)) : null;

        function showSubmitLoading() {
            if (!submitBtn) return;
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70', 'cursor-wait');
            const label = submitBtn.querySelector('[data-submit-label]');
            if (label) {
                label.dataset.originalText = label.textContent;
                label.textContent = submitBtn.dataset.savingText || 'Saving...';
            }
        }

        function resetSubmitLoading() {
            if (!submitBtn) return;
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-70', 'cursor-wait');
            const label = submitBtn.querySelector('[data-submit-label]');
            if (label && label.dataset.originalText) {
                label.textContent = label.dataset.originalText;
                delete label.dataset.originalText;
            }
        }
        // Auto-reset: page scripts don't need to call anything manually.
        // Whenever getList() runs (called on every success) or the modal
        // closes, the submit button returns to its normal state.
        window.getList = window.getList || function () {};
        const previousGetList = window.getList;
        window.getList = function () {
            previousGetList();
            resetSubmitLoading();
        };

        const observer = new MutationObserver(function () {
            if (modal.classList.contains('hidden')) {
                resetSubmitLoading();
            }
        });
        observer.observe(modal, { attributes: true, attributeFilter: ['class'] });

        // Covers the error path: when a SweetAlert popup appears (shown on
        // every failure), the submission is over — restore the button.
        const swalObserver = new MutationObserver(function () {
            if (document.querySelector('.swal2-container')) {
                resetSubmitLoading();
            }
        });
        swalObserver.observe(document.body, { childList: true, subtree: true });

        // Global helper so page scripts can reset the state after their
        // fetch succeeds/fails: window.resetSubmitLoading('add-modal')
        window.resetSubmitLoading = window.resetSubmitLoading || function () {};
        const previousReset = window.resetSubmitLoading;
        window.resetSubmitLoading = function (modalId) {
            previousReset(modalId);
            if (!modalId || modalId === @json($id)) { resetSubmitLoading(); }
        };

        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                showSubmitLoading();
            });
        }
    })();
</script>
