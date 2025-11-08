<div id="sidebar" class=" bg-slate-200 h-[100%] border-r border-[#006172]">
    <!-- Sidebar Container -->
    <div class="flex flex-col  h-full transition-all duration-300 ease-in-out" id="sidebar-container">
        <!-- Sidebar Header -->
        <div id="divHide" class="flex items-center w-60 justify-between h-16 px-4 bg-blue-900 shadow-md">
            <span id="sidebar-logo-text"
                class="text-white text-xl font-semibold whitespace-nowrap transition-all duration-300 sidebar-text truncate">Ship
                Booking</span>
            <button id="sidebar-toggle"
                class="p-2 rounded-md text-white hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-300"
                title="Collapse sidebar">
                <svg class="w-5 h-5" id="toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <div class="flex flex-col flex-grow px-2 py-4 overflow-y-auto scrollbar-hide" id="nav-container">
            <nav class="flex-1 space-y-1">


                <!-- Sell Section -->
                <div class="px-2 pt-2">
                    <div id="sell-dropdown" class="mb-1 relative">
                        <button
                            class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                                    </path>
                                </svg>
                                <span
                                    class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Sell</span>
                            </div>
                            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                id="sell-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="sell-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                            <a href="/ship-ticket-sales/create"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Create
                                    Tickets</span>
                            </a>
                            <a href="/sales/status/pending"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Pending
                                    Tickets</span>
                            </a>
                            <a href="/sales/status/payment-verified"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Payment
                                    Varified Tickets</span>
                            </a>
                            <a href="/sales/status/ticket-issued"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Issued
                                    Tickets</span>
                            </a>
                            <a href="/sales/status/ticket-printed"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Printed
                                    Tickets</span>
                            </a>
                            <a href="/sales/status/shipment_id_entered"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Ready
                                    for Courier</span>
                            </a>
                            <a href="/sales/status/shipped"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Shipped
                                    Tickets</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="px-2 pt-2">
                    <div id="refund-dropdown" class="mb-1 relative">
                        <button
                            class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                            <div class="flex items-center">
                                <i class="fas text-blue-600 fa-undo-alt"></i>

                                <span
                                    class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Refund</span>
                            </div>
                            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                id="create-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="refund-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                            <!-- Make Refund Link -->
                            <a href="/refunds/create"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition 
              {{ request()->is('refunds/create') ? 'bg-blue-100 text-blue-600' : '' }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Make
                                    Refund</span>
                            </a>

                            <!-- Refunded Sell Link -->
                            <a href="/refunded"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Refunded
                                    Sell</span>
                            </a>


                        </div>


                    </div>
                </div>

                <div class="px-2 pt-2">
                    <div id="create-dropdown" class="mb-1 relative">
                        <button
                            class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span
                                    class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Create</span>
                            </div>
                            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                id="create-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="create-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                            <a href="/ships-details"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">New
                                    Ship</span>
                            </a>
                            <a href="/companies-details"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">New
                                    Company</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Reports Section -->
                <div class="px-2 pt-2">
                    <div id="reports-dropdown" class="mb-1 relative">
                        <button
                            class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v7m3-2h6">
                                    </path>
                                </svg>
                                <span
                                    class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Show
                                    Reports</span>
                            </div>
                            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                id="reports-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="reports-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                            <a href="/admin/sales-reports"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Sales
                                    Reports</span>
                            </a>
                            <a href="/admin/booking-reports"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Booking
                                    Reports</span>
                            </a>
                        </div>
                    </div>
                </div>
            </nav>
        </div>

        <!-- Sidebar Footer (User Profile) -->

    </div>
</div>



<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarContainer = document.getElementById('sidebar-container');
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const toggleIcon = document.getElementById('toggle-icon');
        const logoText = document.getElementById('sidebar-logo-text');
        const divHide = document.getElementById('divHide');
        const sidebarTexts = document.querySelectorAll('.sidebar-text:not(#sidebar-logo-text)');
        const dropdownContents = document.querySelectorAll('[id$="-dropdown-list"]');
        const dropdownIcons = document.querySelectorAll('[id$="-dropdown-icon"]');
        const navLinks = document.querySelectorAll('#nav-container a[href]');

        // Check localStorage for saved state
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

        // Initialize sidebar state
        if (isCollapsed) {
            collapseSidebar();
        } else {
            expandSidebar();
        }

        // Toggle sidebar
        sidebarToggle.addEventListener('click', function() {
            const isCollapsed = sidebarContainer.classList.contains('sidebar-collapsed');

            if (isCollapsed) {
                expandSidebar();
                localStorage.setItem('sidebarCollapsed', 'false');
            } else {
                collapseSidebar();
                localStorage.setItem('sidebarCollapsed', 'true');
            }
        });

        function collapseSidebar() {
            sidebarContainer.classList.add('sidebar-collapsed');
            sidebarContainer.classList.remove('sidebar-expanded');
            sidebarContainer.classList.add('w-20');
            sidebarContainer.classList.remove('w-60');
            divHide.classList.remove('w-60');
            divHide.classList.add('w-20');

            // Change icon to double arrow right
            toggleIcon.innerHTML =
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>';

            // Hide logo text
            logoText.classList.add('hidden');

            // Close all dropdowns
            dropdownContents.forEach(dropdown => dropdown.classList.add('hidden'));
            dropdownIcons.forEach(icon => icon.classList.remove('rotate-180'));

            // Remove any existing expanded menus
            document.querySelectorAll('.expanded-menu').forEach(menu => menu.remove());
        }

        function expandSidebar() {
            sidebarContainer.classList.remove('sidebar-collapsed');
            sidebarContainer.classList.add('sidebar-expanded');
            sidebarContainer.classList.remove('w-20');
            sidebarContainer.classList.add('w-60');
            divHide.classList.remove('w-20');
            divHide.classList.add('w-60');

            // Change icon to double arrow left
            toggleIcon.innerHTML =
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>';

            // Show logo text
            logoText.classList.remove('hidden');

            // Remove any existing expanded menus
            document.querySelectorAll('.expanded-menu').forEach(menu => menu.remove());
        }

        // Dropdown toggle functionality - only for expanded sidebar
        document.querySelectorAll('[id$="-dropdown"] button').forEach(button => {
            button.addEventListener('click', function(e) {
                // Only toggle if sidebar is expanded
                if (sidebarContainer.classList.contains('sidebar-expanded')) {
                    e.stopPropagation();
                    const dropdownId = this.parentElement.id;
                    const contentId = `${dropdownId}-list`;
                    const iconId = `${dropdownId}-icon`;

                    const content = document.getElementById(contentId);
                    const icon = document.getElementById(iconId);

                    // Toggle current dropdown
                    content.classList.toggle('hidden');
                    icon.classList.toggle('rotate-180');

                    // Close all other dropdowns
                    document.querySelectorAll('[id$="-dropdown-list"]').forEach(
                        otherContent => {
                            if (otherContent.id !== contentId) {
                                otherContent.classList.add('hidden');
                                const otherIconId = otherContent.id.replace('-list',
                                    '-icon');
                                const otherIcon = document.getElementById(otherIconId);
                                if (otherIcon) otherIcon.classList.remove('rotate-180');
                            }
                        });
                }
            });
        });

        // Close dropdowns when clicking outside - only for expanded sidebar
        document.addEventListener('click', function(e) {
            if (sidebarContainer.classList.contains('sidebar-expanded') &&
                !e.target.closest('[id$="-dropdown"]') &&
                !e.target.closest('[id$="-dropdown-list"]')) {
                dropdownContents.forEach(content => content.classList.add('hidden'));
                dropdownIcons.forEach(icon => icon.classList.remove('rotate-180'));
            }
        });

        // Handle hover effects for collapsed sidebar
        document.querySelectorAll('[id$="-dropdown"]').forEach(item => {
            item.addEventListener('mouseenter', function() {
                if (sidebarContainer.classList.contains('sidebar-collapsed')) {
                    // Remove any existing expanded menus first
                    document.querySelectorAll('.expanded-menu').forEach(menu => menu.remove());

                    // Create expanded menu container
                    const expandedMenu = document.createElement('div');
                    expandedMenu.className = 'expanded-menu';

                    // Get the main item
                    const mainItem = this.querySelector('button');

                    // Create main menu item
                    const mainMenuItem = document.createElement('div');
                    mainMenuItem.className = 'expanded-menu-item font-medium text-gray-700';

                    const mainIcon = mainItem.querySelector('svg').cloneNode(true);
                    mainIcon.className = 'w-5 h-5 flex-shrink-0 text-blue-600';

                    mainMenuItem.appendChild(mainIcon);

                    const mainTextSpan = document.createElement('span');
                    mainTextSpan.className = 'ml-3';
                    mainTextSpan.textContent = mainItem.querySelector('.sidebar-text')
                        ?.textContent || '';
                    mainMenuItem.appendChild(mainTextSpan);

                    expandedMenu.appendChild(mainMenuItem);

                    // Add divider
                    const divider = document.createElement('div');
                    divider.className = 'expanded-menu-divider';
                    expandedMenu.appendChild(divider);

                    // Add dropdown items
                    const dropdownList = this.querySelector('[id$="-dropdown-list"]');
                    if (dropdownList) {
                        const dropdownItems = dropdownList.querySelectorAll('a');
                        dropdownItems.forEach(item => {
                            const clonedItem = item.cloneNode(true);
                            clonedItem.className =
                                'expanded-menu-item text-gray-600 hover:text-blue-600';

                            // Remove any existing classes and add our own
                            const iconSpan = clonedItem.querySelector(
                                'span:first-child');
                            if (iconSpan) {
                                iconSpan.className =
                                    'w-1.5 h-1.5 rounded-full bg-blue-500 mr-3';
                            }

                            const textSpan = clonedItem.querySelector(
                                'span:last-child');
                            if (textSpan) {
                                textSpan.className = 'whitespace-nowrap';
                            }

                            expandedMenu.appendChild(clonedItem);
                        });
                    }

                    // Position the expanded menu
                    const rect = this.getBoundingClientRect();
                    expandedMenu.style.top = `${rect.top}px`;
                    expandedMenu.style.left = `${rect.right}px`;

                    // Add to DOM
                    document.body.appendChild(expandedMenu);

                    // Close when mouse leaves
                    expandedMenu.addEventListener('mouseleave', function() {
                        this.remove();
                    });

                    // Also close when leaving the original item
                    this.addEventListener('mouseleave', function() {
                        setTimeout(() => {
                            if (!expandedMenu.matches(':hover')) {
                                expandedMenu.remove();
                            }
                        }, 100);
                    });
                }
            });
        });

        // Highlight active menu item based on current URL
        function setActiveMenuItem() {
            const currentPath = window.location.pathname;

            navLinks.forEach(link => {
                const linkPath = link.getAttribute('href');
                if (currentPath === linkPath || (linkPath !== '/' && currentPath.startsWith(
                        linkPath))) {
                    link.classList.add('bg-blue-50', 'text-blue-700');
                    const icon = link.querySelector('svg');
                    if (icon) {
                        icon.classList.add('text-blue-700');
                        icon.classList.remove('text-blue-600');
                    }

                    // If this is a dropdown item, open its parent dropdown
                    const dropdownItem = link.closest('[id$="-dropdown-list"]');
                    if (dropdownItem) {
                        const dropdownId = dropdownItem.id.replace('-list', '');
                        const dropdownButton = document.querySelector(`#${dropdownId} button`);
                        if (dropdownButton) {
                            const dropdownIcon = document.getElementById(`${dropdownId}-icon`);
                            dropdownItem.classList.remove('hidden');
                            if (dropdownIcon) dropdownIcon.classList.add('rotate-180');
                        }
                    }
                }
            });
        }

        setActiveMenuItem();
    });
</script>
