document.addEventListener("DOMContentLoaded", () => {
    const loader = document.getElementById("loader");
    const table = document.getElementById("salesTable");
    const salesBody = document.getElementById("salesBody");
    const shipFilter = document.getElementById("shipFilter");
    const companyFilter = document.getElementById("companyFilter");
    const journeyDateFilter = document.getElementById("journeyDateFilter");
    const clearFiltersBtn = document.getElementById("clearFilters");

    let dataTableInitialized = false;
    let dataTable;
    let ships = [];
    let companies = [];
    function copyToClipboard(text) {
        if (!text) return;

        navigator.clipboard.writeText(text)
            .then(() => {
                showCopiedMessage(); // call toast
            })
            .catch(err => {
                console.error("Copy failed:", err);
            });
    }
    function showCopiedMessage() {
        // Create toast element
        const toast = document.createElement("div");
        toast.textContent = "Copied!";
        toast.className = "fixed bottom-4 right-4 bg-black text-white px-4 py-2 rounded shadow-lg opacity-0 transition-opacity duration-300 z-50";

        document.body.appendChild(toast);

        // Trigger fade-in
        requestAnimationFrame(() => {
            toast.classList.add("opacity-100");
        });

        // Remove after 1.5s
        setTimeout(() => {
            toast.classList.remove("opacity-100");
            setTimeout(() => toast.remove(), 300);
        }, 1500);
    }
    document.querySelectorAll(".copyBtn").forEach((btn) => {
        btn.addEventListener("click", (e) => {
            e.stopPropagation();
            copyToClipboard(btn.dataset.copy); // এবার toast দেখাবে
        });
    });


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

    function initializeTabs() {
        const tabs = document.querySelectorAll(".status-tab");
        const statusElement = document.getElementById("statusFilter");
        const title = document.querySelector("h2");

        tabs.forEach((tab) => {
            tab.addEventListener("click", () => {
                tabs.forEach((t) => {
                    t.classList.remove("bg-blue-950", "text-white");
                    t.classList.add("bg-gray-200", "text-gray-900");
                });

                tab.classList.remove("bg-gray-200", "text-gray-900");
                tab.classList.add("bg-blue-950", "text-white");

                const newStatus = tab.dataset.status;
                statusElement.dataset.status = newStatus;

                title.textContent = `Ship Ticket Sales (${newStatus})`;

                getList();
            });
        });
    }

    function initializeModalEvents() {
        document.getElementById("closeModalX").addEventListener("click", () => modal.hide());
        document.getElementById("cancelBtn").addEventListener("click", () => modal.hide());
        modal.element.addEventListener("click", (e) => {
            if (e.target === modal.element) modal.hide();
        });
    }

    async function fetchShips() {
        try {
            const response = await fetch("/ships");
            ships = await response.json();
            populateDropdown(shipFilter, ships, "All Ships");
            populateEditShipDropdown?.();
        } catch (error) {
            console.error("Error fetching ships:", error);
        }
    }

    async function fetchCompanies() {
        try {
            const response = await fetch("/companies");
            companies = await response.json();
            populateDropdown(companyFilter, companies, "All Companies");
            populateEditCompanyDropdown?.();
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

    clearFiltersBtn.addEventListener("click", () => {
        shipFilter.value = "";
        companyFilter.value = "";
        journeyDateFilter.value = "";
        getList();
    });

    async function getList() {
        try {
            loader.style.display = "block";

            const selectedShipId = shipFilter.value;
            const selectedCompanyId = companyFilter.value;
            const selectedJourneyDate = journeyDateFilter.value;
            const statusElement = document.getElementById("statusFilter");
            const status = statusElement ? statusElement.dataset.status : "pending";

            if (dataTableInitialized && dataTable) {
                dataTable.destroy();
                dataTableInitialized = false;
            }

            salesBody.innerHTML = "";
            loader.style.display = "none";
            table.classList.remove("hidden");

            dataTable = $("#salesTable").DataTable({
                processing: true,
                serverSide: true,
                ordering: false,
                ajax: {
                    url: `/sales/${status}`,
                    type: 'GET',
                    data: function (d) {
                        d.ship_id = selectedShipId;
                        d.company_id = selectedCompanyId;
                        d.journey_date = selectedJourneyDate;
                    }
                },
                columns: [
                    // ID Column with Copy Icon
                    {
                        data: 'id',
                        render: function (data) {
                            return `
                                <div class="flex items-center gap-2 justify-center">
                                    <span>${data}</span>
                                    <button class="copyBtn text-gray-500 hover:text-blue-950" data-copy="${data}" title="Copy">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>`;
                        }
                    },
                    {
                        data: 'customer_name',
                        render: function (data) {
                            return `
                                <div class="flex items-center gap-2 justify-center">
                                    <span>${data}</span>
                                    <button class="copyBtn text-gray-500 hover:text-blue-950" data-copy="${data}" title="Copy">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>`;
                        }
                    },

                    // Customer Mobile with Copy Icon
                    {
                        data: 'customer_mobile',
                        render: function (data) {
                            if (!data) return 'N/A';
                            return `
                                <div class="flex items-center gap-2">
                                    <span>${data}</span>
                                    <button class="copyBtn text-gray-500 hover:text-blue-950" data-copy="${data}" title="Copy">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>`;
                        }
                    },
                    {
                        data: null,
                        render: function (data) {
                            return data.ship?.name || data.ships?.name || 'Not available';
                        }
                    },
                    {
                        data: 'journey_date',
                        render: function (data) {
                            return new Date(data).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            });
                        }
                    },
                    { data: 'ticket_fee' },
                    {
                        data: 'companies.name',
                        render: function (data, type, row) {
                            return data || row.company?.name || 'Not available';
                        }
                    },
                    { data: 'status' },
                    {
                        data: null,
                        orderable: false,
                        render: function (data, type, row) {
                            return createActionButtons(row);
                        }
                    }
                ],
                dom: "lBfrtip",
                lengthChange: true,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                buttons: ['copy', 'excel', 'csv', 'pdf', 'print', 'colvis'],
                drawCallback: function () {
                    attachEventListeners();
                }
            });

            dataTableInitialized = true;

        } catch (error) {
            console.error("Error initializing DataTable:", error);
            loader.textContent = "Failed to load data. Please try again later.";
        }
    }

    function createActionButtons(sale) {
        const verifyByName = sale.verifyby?.length > 0 ? sale.verifyby[0].verified_by_user?.name : 'Unknown';

        let html = `
        <div class="flex gap-2 items-center justify-center">
            <a href="/ship-ticket-sales/${sale.id}">
                <button class="fas fa-edit text-blue-950 px-2 py-1 rounded editBtn" title="Edit"></button>
            </a>
            <button class="fas fa-trash text-red-500 px-2 py-1 border border-gray-300 rounded deleteBtn" data-id="${sale.id}" title="Delete"></button>
        `;

        const due = Number(sale.due_amount);
        if (due > 0) {
            html += `
            <button class="bg-yellow-500 text-black px-2 py-1 rounded dueBtn"
                data-id="${sale.id}"
                data-due_amount="${sale.due_amount}"
                title="Due Amount: ${sale.due_amount}">
               Pay Due
            </button>`;
        }

        html += createStatusButton(sale, verifyByName);
        html += `</div>`;

        return html;
    }

    function createStatusButton(sale, verifyByName) {
        const statusButtons = {
            'pending': `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn" 
                data-id="${sale.id}" data-status="payment-verified" 
                title="Sold by: ${sale.sold_by}">Verify Payment</button>`,
            'payment-verified': `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn" 
                data-id="${sale.id}" data-status="ticket-issued" 
                title="payment-verified by: ${verifyByName}">Ticket Issued</button>`,
            'ticket-issued': `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn" 
                data-id="${sale.id}" data-status="ticket-printed" 
                title="ticket-issued by: ${verifyByName}">Ticket Printed</button>
                
                <button class="fa-solid fa-print px-2 py-1 rounded printBtn" 
                data-id="${sale.id}" data-status="ticket-printed" 
                >  </button>`,

            'ticket-printed': `<button class="bg-red-500 text-white px-2 py-1 rounded shipmentIdEntryBtn" 
                data-id="${sale.id}" data-status="shipment_id_entered" 
                title="ticket-printed by: ${verifyByName}">Entry Shipment ID</button>`,
            'shipment_id_entered': `<button class="bg-red-500 text-white px-2 py-1 rounded verifyBtn" 
                data-id="${sale.id}" data-status="shipped" 
                title="shipment_id_entered by: ${verifyByName}">Shipped</button>`
        };
        return statusButtons[sale.status] || '';
    }

    function attachEventListeners() {
        // Verify buttons
        document.querySelectorAll(".verifyBtn").forEach((btn) => {
            btn.addEventListener("click", () => varifySale(btn, getList));
        });

        // Shipment ID buttons
        document.querySelectorAll(".shipmentIdEntryBtn").forEach((btn) => {
            btn.addEventListener("click", () => varifyShipment(btn, getList));
        });

        // Due buttons
        document.querySelectorAll(".dueBtn").forEach((btn) => {
            btn.addEventListener("click", () => due(btn, getList));
        });

        // Copy buttons
        document.querySelectorAll(".copyBtn").forEach((btn) => {
            btn.addEventListener("click", (e) => {
                e.stopPropagation();
                copyToClipboard(btn.dataset.copy);
            });
        });

        // Print buttons
        // document.querySelectorAll(".printBtn").forEach((btn) => {
        //     btn.addEventListener("click", () => {
        //         const saleId = btn.dataset.id;
        //         if (!saleId) return;

        //         // Use your Laravel route
        //         const pdfUrl = `/print-pdf/${saleId}`;

        //         // Open PDF in a new tab
        //         window.open(pdfUrl, "_blank");
        //     });
        // });

        document.querySelectorAll(".printBtn").forEach((btn) => {
            btn.addEventListener("click", () => {
                const saleId = btn.dataset.id;
                if (!saleId) return;

                const pdfUrl = `/print-pdf/${saleId}`; 

                // Create a hidden iframe
                const iframe = document.createElement("iframe");
                iframe.style.display = "none";
                iframe.src = pdfUrl;

                document.body.appendChild(iframe);

                iframe.onload = () => {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                    // Optional: remove iframe after printing
                    setTimeout(() => iframe.remove(), 1000);
                };
            });
        });
    }

    async function initializePage() {
        initializeModalEvents();
        await fetchShips();
        initializeTabs();
        await fetchCompanies();
        getList();
        
    }

    initializePage();
});
