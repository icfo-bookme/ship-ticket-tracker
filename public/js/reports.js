document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const returnDateFilter = document.getElementById("returnDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");
    const paymentMethodFilter = document.getElementById("payment_method");
    const startDateFilter = document.getElementById("startDate");
    const endDateFilter = document.getElementById("endDate");
    const createdDateFilter = document.getElementById("createdDateFilter");
    const startCreateDateFilter = document.getElementById("startCreateDate");
    const endCreateDateFilter = document.getElementById("endCreateDate");





    let dataTableInitialized = false;
    let dataTable;
    let ships = [];
    let companies = [];

    // Reusable modal functions
    const modal = {
        element: document.getElementById("editModal"),

        show: function () {
            this.element.classList.remove("hidden");
            this.element.classList.add("flex");
        },

        hide: function () {
            this.element.classList.add("hidden");
            this.element.classList.remove("flex");
        },
    };

    function initializeModalEvents() {
        // Close modal when clicking the X button
        document
            .getElementById("closeModalX")
            .addEventListener("click", () => modal.hide());

        // Close modal when clicking the cancel button
        document
            .getElementById("cancelBtn")
            .addEventListener("click", () => modal.hide());

        // Close modal when clicking outside the modal content
        modal.element.addEventListener("click", (e) => {
            if (e.target === modal.element) {
                modal.hide();
            }
        });
    }

    async function fetchShips() {
        try {
            const response = await fetch("/ships");
            ships = await response.json();
            populateDropdown(shipFilter, ships, "All Ships");
        } catch (error) {
            console.error("Error fetching ships:", error);
        }
    }

    async function fetchCompanies() {
        try {
            const response = await fetch("/companies");
            companies = await response.json();
            populateDropdown(companyFilter, companies, "All Companies");
        } catch (error) {
            console.error("Error fetching companies:", error);
        }
    }

    function populateDropdown(selectElement, data, defaultText) {
        if (!selectElement) return;

        selectElement.innerHTML = "";
        const defaultOption = document.createElement("option");
        defaultOption.value = "";
        defaultOption.textContent = defaultText;
        defaultOption.selected = true;
        selectElement.appendChild(defaultOption);

        data.forEach((item) => {
            const option = document.createElement("option");
            option.value = item.id;
            option.textContent = item.name;
            selectElement.appendChild(option);
        });
    }

    // Filter event listeners
    shipFilter.addEventListener("change", getList);
    companyFilter.addEventListener("change", getList);
    journeyDateFilter.addEventListener("change", getList);
    returnDateFilter.addEventListener("change", getList);
    paymentMethodFilter.addEventListener("change", getList);
    startDateFilter.addEventListener("change", getList);
    endDateFilter.addEventListener("change", getList);
    createdDateFilter.addEventListener("change", getList);
    startCreateDateFilter.addEventListener("change", getList);
    endCreateDateFilter.addEventListener("change", getList);




    // Clear filters
    clearFiltersBtn.addEventListener("click", () => {
        shipFilter.value = "";
        companyFilter.value = "";
        journeyDateFilter.value = "";
        returnDateFilter.value = "";
        paymentMethodFilter.value = "";
        startDateFilter.value = "";
        endDateFilter.value = "";
        createdDateFilter.value = "";
        startCreateDateFilter.value = "";
        endCreateDateFilter.value = "";
        getList();
    });


    async function getList() {
        try {
            loader.style.display = "block";

            // Get filter values
            const selectedShipId = shipFilter.value;
            const selectedCompanyId = companyFilter.value;
            const selectedJourneyDate = journeyDateFilter.value;
            const selectedReturnDate = returnDateFilter.value;
            const selectedPaymentMethod = paymentMethodFilter.value;
            const selectedStartDate = startDateFilter.value;
            const selectedEndDate = endDateFilter.value;
            const selectedCreatedDate = createdDateFilter.value;
            const selectedStartCreateDate = startCreateDateFilter.value;
            const selectedEndCreateDate = endCreateDateFilter.value;

            const rtotalRefundedTickets = document.getElementById(
                "totalRefundedTickets"
            );
            const rtotalRefundedAmount = document.getElementById(
                "totalRefundedAmount"
            );
             const totalSellTickets = document.getElementById(
                "totalSellTickets"
            );
            const totalSellAmount = document.getElementById(
                "totalSellAmount"
            );

            const statusElement = document.getElementById("statusFilter");
            const status = statusElement
                ? statusElement.dataset.status
                : "pending";

            if (dataTableInitialized && dataTable) {
                dataTable.destroy();
                dataTableInitialized = false;
            }

            // Clear table body
            salesBody.innerHTML = "";

            let url = `/reports?`;
            const params = new URLSearchParams();

            if (selectedShipId) params.append("ship_id", selectedShipId);
            if (selectedCompanyId) params.append("company_id", selectedCompanyId);
            if (selectedJourneyDate) params.append("journey_date", selectedJourneyDate);
            if (selectedReturnDate) params.append("return_date", selectedReturnDate);
            if (selectedPaymentMethod) params.append("payment_method", selectedPaymentMethod);
            if (selectedStartDate) params.append("start_date", selectedStartDate);
            if (selectedEndDate) params.append("end_date", selectedEndDate);
            if (selectedCreatedDate) params.append("created_date", selectedCreatedDate);
            if (selectedStartCreateDate) params.append("start_create_date", selectedStartCreateDate);
            if (selectedEndCreateDate) params.append("end_create_date", selectedEndCreateDate);

            url += params.toString();

            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error: ${response.status}`);

            const result = await response.json();

            const data = result.data || [];
            loader.style.display = "none";
            table.classList.remove("hidden");

            // Render sales data into the table
            data.forEach((sale) => {
                const tr = document.createElement("tr");
                const formatDate = (field, value) => {
                    if (
                        ["journey_date", "issued_date"].includes(field) &&
                        value !== "Not specified"
                    ) {
                        return new Date(value).toLocaleDateString("en-US", {
                            year: "numeric",
                            month: "long",
                            day: "numeric",
                        });
                    }
                    return value;
                };

                tr.innerHTML = `
          <td class="border px-4 py-2  border-gray-300">${sale.id}</td>
          <td class="border px-4 py-2 border-gray-300">${sale.customer_name}</td>
          <td class="border px-4 py-2 border-gray-300">${sale.customer_mobile}</td>
          <td class="border px-4 py-2 border-gray-300">${sale.ship
                        ? sale.ship.name
                        : sale.ships
                            ? sale.ships.name
                            : "Not available"
                    }</td>
          <td class="border px-4 py-2 border-gray-300">${formatDate(
                        "journey_date",
                        sale.journey_date
                    )}</td>
          <td class="border px-4 py-2 border-gray-300">${sale.number_of_ticket}</td>
          <td class="border px-4 py-2 border-gray-300">${sale.ticket_fee}</td>
          <td class="border px-4 py-2 border-gray-300">${sale.received_amount}</td>
          
          <td class="border px-4 py-2 flex gap-5 items-center justify-center border-gray-300">
            <button class="fas fa-eye text-blue-950 px-2 py-1 rounded showBtn" 
              data-id="${sale.id}" 
              data-customer="${sale.customer_name}" 
              data-mobile="${sale.customer_mobile}" 
              data-email="${sale.email}" 
              data-nid="${sale.nid}" 
              data-source="${sale.sales_source}"
              data-ship="${sale.ship_id}"
              data-ship-name="${sale.ship
                        ? sale.ship.name
                        : sale.ships
                            ? sale.ships.name
                            : "Not available"
                    }"
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
              data-soldBy="${sale.sold_by || ""}"
              data-status="${sale.status}">    
            </button>
          </td>
        `;
                salesBody.appendChild(tr);
            });
            rtotalRefundedTickets.textContent = result.total_refunded_tickets;
            rtotalRefundedAmount.textContent = result.total_refunded_amount;
            totalSellTickets.textContent = result.total_sold_tickets;
            totalSellAmount.textContent = result.total_sales_amount
            
            // Initialize DataTable
            dataTable = $("#salesTable").DataTable({
                dom: "lBfrtip",
                lengthChange: true,
                lengthMenu: [
                    [10, 25, 50, 75, 100, 200, 300, 400, 500],
                    [10, 25, 50, 75, 100, 200, 300, 400, 500],
                ],
                language: {
                    lengthMenu: "_MENU_",
                },
                buttons: [
                    "copy",
                    "excel",
                    "csv",
                    "pdf",
                    "print",
                    {
                        extend: "colvis",
                        text: "Column Visibility",
                    },
                ],
                // Add this callback
                drawCallback: function () {
                    attachEventListeners();
                },
            });
            dataTableInitialized = true;
            attachEventListeners();
        } catch (error) {
            console.error("Error fetching sales data:", error);
            loader.textContent = "Failed to load data. Please try again later.";
        }
    }

    function attachEventListeners() {
        document.querySelectorAll(".showBtn").forEach((btn) => {
            btn.addEventListener("click", () => showModal(btn, modal));
        });
    }

    // Initialize the page
    async function initializePage() {
        initializeModalEvents();
        await fetchShips();
        await fetchCompanies();
        getList();
    }

    initializePage();
});