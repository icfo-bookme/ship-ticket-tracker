document.addEventListener('DOMContentLoaded', () => {
    const loader = document.getElementById('loader');
    const table = document.getElementById('salesTable');
    const salesBody = document.getElementById('salesBody');
    const shipFilter = document.getElementById('shipFilter');
    const companyFilter = document.getElementById('companyFilter');
    const journeyDateFilter = document.getElementById('journeyDateFilter');
    const clearFiltersBtn = document.getElementById('clearFilters');

    let dataTableInitialized = false;
    let dataTable;
    let ships = [];
    let companies = [];

    // Reusable modal functions
    const modal = {
        element: document.getElementById('editModal'),

        show: function () {
            this.element.classList.remove('hidden');
            this.element.classList.add('flex');
        },

        hide: function () {
            this.element.classList.add('hidden');
            this.element.classList.remove('flex');
        }
    };


    function initializeModalEvents() {
        // Close modal when clicking the X button
        document.getElementById('closeModalX').addEventListener('click', () => modal.hide());

        // Close modal when clicking the cancel button
        document.getElementById('cancelBtn').addEventListener('click', () => modal.hide());

        // Close modal when clicking outside the modal content
        modal.element.addEventListener('click', (e) => {
            if (e.target === modal.element) {
                modal.hide();
            }
        });
    }


    async function fetchShips() {
        try {
            const response = await fetch('/ships');
            ships = await response.json();
            populateDropdown(shipFilter, ships, 'All Ships');

        } catch (error) {
            console.error('Error fetching ships:', error);
        }
    }

    async function fetchCompanies() {
        try {
            const response = await fetch('/companies');
            companies = await response.json();
            populateDropdown(companyFilter, companies, 'All Companies');

        } catch (error) {
            console.error('Error fetching companies:', error);
        }
    }

    function populateDropdown(selectElement, data, defaultText) {
        if (!selectElement) return;

        selectElement.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = defaultText;
        defaultOption.selected = true;
        selectElement.appendChild(defaultOption);

        data.forEach(item => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name;
            selectElement.appendChild(option);
        });
    }

    // Filter event listeners
    shipFilter.addEventListener('change', getList);
    companyFilter.addEventListener('change', getList);
    journeyDateFilter.addEventListener('change', getList);

    // Clear filters
    clearFiltersBtn.addEventListener('click', () => {
        shipFilter.value = '';
        companyFilter.value = '';
        journeyDateFilter.value = '';
        getList();
    });

    async function getList() {
        try {
            loader.style.display = 'block';

            // Get filter values
            const selectedShipId = shipFilter.value;
            const selectedCompanyId = companyFilter.value;
            const selectedJourneyDate = journeyDateFilter.value;

            const statusElement = document.getElementById('statusFilter');
            const status = statusElement ? statusElement.dataset.status : 'pending';
            // Destroy existing DataTable if initialized
            if (dataTableInitialized && dataTable) {
                dataTable.destroy();
                dataTableInitialized = false;
            }

            // Clear table body
            salesBody.innerHTML = '';


            let url = `/sales/${status}?`;
            const params = new URLSearchParams();

            if (selectedShipId) params.append('ship_id', selectedShipId);
            if (selectedCompanyId) params.append('company_id', selectedCompanyId);
            if (selectedJourneyDate) params.append('journey_date', selectedJourneyDate);

            url += params.toString();

            const response = await fetch(url);
            const data = await response.json();

            loader.style.display = 'none';
            table.classList.remove('hidden');

            // Render sales data into the table
            data.forEach(sale => {
                const tr = document.createElement('tr');
                const formatDate = (field, value) => {
                    if (['journey_date', 'issued_date'].includes(field) && value !== 'Not specified') {
                        return new Date(value).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                    return value;
                };

                tr.innerHTML = `
                <td class="border px-4 py-2">
        <input type="checkbox" class="selectSale" data-id="${sale.id}" />
    </td>
    <td class="border px-4 py-2">${sale.id}</td>
    <td class="border px-4 py-2">${sale.customer_name}</td>
    <td class="border px-4 py-2">${sale.customer_mobile}</td>
    <td class="border px-4 py-2">${sale.ship ? sale.ship.name : (sale.ships ? sale.ships.name : 'Not available')}</td>
    <td class="border px-4 py-2">${formatDate('journey_date', sale.journey_date)}</td>
     <td class="border px-4 py-2">${sale.number_of_ticket}</td>
      <td class="border px-4 py-2">${sale.ticket_fee}</td>
    <td class="border px-4 py-2">${sale.received_amount}</td>
    
    <td class="border px-4 py-2 flex gap-5 items-center justify-center">
        <button class="fas fa-eye  text-blue-950 px-2 py-1 rounded showBtn" 
            data-id="${sale.id}" 
            data-customer="${sale.customer_name}" 
            data-mobile="${sale.customer_mobile}" 
            data-email="${sale.email}" 
            data-nid="${sale.nid}" 
            data-source="${sale.sales_source}"
            data-ship="${sale.ship_id}"
            data-ship-name="${sale.ship ? sale.ship.name : (sale.ships ? sale.ships.name : 'Not available')}"
            data-journeyDate="${sale.journey_date}"
            data-returnDate="${sale.return_date}"
            data-ticketFee="${sale.ticket_fee}"
            data-payment_method="${sale.payment_method}"
            data-number_of_ticket="${sale.number_of_ticket}"
            data-receivedAmount="${sale.received_amount}"
            data-dueAmount="${sale.due_amount}"
            data-companyId="${sale.company_id}"
            data-company-name="${sale.companies.name}"
            data-issuedDate="${sale.issued_date}"
            data-ticket_category="${sale.ticket_category}"
            data-soldBy="${sale.sold_by || ''}"
            data-status="${sale.status}">    
        </button>
       
         ${sale.status === 'shipped' ? `
            <button class="bg-blue-900 text-white px-2 py-1 rounded verifyRefund" 
                data-id="${sale.id}"
                data-received_total_amount="${sale.received_amount}"
                data-number_ticket="${sale.number_of_ticket}"
                data-status="shipped">
               Partial Refund
            </button>
        ` : ''}
    </td>
`;
                salesBody.appendChild(tr);
            });

            // Initialize DataTable
            dataTable = $('#salesTable').DataTable({
                dom: 'lBfrtip',
                lengthChange: true,
                lengthMenu: [
                    [10, 25, 50, 75, 100, 200, 300, 400, 500],
                    [10, 25, 50, 75, 100, 200, 300, 400, 500]
                ],
                language: {
                    lengthMenu: '_MENU_'
                },
                buttons: [
                    'copy', 'excel', 'csv', 'pdf', 'print',
                    {
                        extend: 'colvis',
                        text: 'Column Visibility'
                    }
                ],
                // Add this callback
                drawCallback: function () {
                    attachEventListeners();
                }
            });
            dataTableInitialized = true;

            attachEventListeners();

        } catch (error) {
            console.error('Error fetching sales data:', error);
            loader.textContent = 'Failed to load data. Please try again later.';
        }
    }

    function attachEventListeners() {
        document.querySelectorAll('.showBtn').forEach(btn => {
            btn.addEventListener('click', () => showModal(btn, modal));
        });


        document.querySelectorAll('.verifyRefund').forEach(btn => {
            btn.addEventListener('click', () => refunded(btn, getList));
        });
    }
    document.getElementById('selectAll').addEventListener('change', (e) => {
        const checkboxes = document.querySelectorAll('.selectSale');
        checkboxes.forEach(checkbox => {
            checkbox.checked = e.target.checked;
        });
    });

    document.getElementById('refundSelectedBtn').addEventListener('click', async () => {
        const selectedIds = [];
        document.querySelectorAll('.selectSale:checked').forEach(checkbox => {
            selectedIds.push(checkbox.dataset.id);
        });

        if (selectedIds.length === 0) {
            alert("Please select at least one item to refund.");
            return;
        }

        try {
            const response = await fetch('/full/refunds', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ ids: selectedIds })
            });

            const result = await response.json();
            if (response.ok) {
                alert('Refund successfully processed for selected items.');
                getList();  // Reload the list
            } else {
                alert(`Error: ${result.message}`);
            }
        } catch (error) {
            console.error('Error sending refund request:', error);
            alert('An error occurred. Please try again.');
        }
    });


    // Initialize the page
    async function initializePage() {
        initializeModalEvents();
        await fetchShips();
        await fetchCompanies();
        getList();
    }

    initializePage();
});