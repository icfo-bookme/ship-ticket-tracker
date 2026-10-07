<div id="sidebar" class="bg-slate-200 h-[100%] border-r border-[#006172]">
    <!-- Sidebar Container -->
    <div class="flex flex-col h-full transition-all duration-300 ease-in-out" id="sidebar-container">
        <!-- Sidebar Header -->
        <div id="divHide" class="flex items-center w-48 justify-between h-12 px-2.5 bg-blue-900 shadow-md">
            <span id="sidebar-logo-text"
                class="text-white text-sm font-semibold whitespace-nowrap transition-all duration-300 sidebar-text truncate">Ship
                Booking</span>
            <button id="sidebar-toggle"
                class="p-1.5 rounded-md text-white hover:bg-blue-700 transition focus:outline-none focus:ring-2 focus:ring-blue-300"
                title="Collapse sidebar">
                <svg class="w-4 h-4" id="toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                </svg>
            </button>
        </div>

        <!-- Navigation -->
        <div class="flex flex-col flex-grow px-1.5 py-3 overflow-y-auto scrollbar-hide" id="nav-container">
            <nav class="flex-1 space-y-1">
                <!-- Sell Section -->
                @canany(['sales.create', 'sales.view'])
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
                                @can('sales.create')
                                    <a href="/ship-ticket-sales/create"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Create
                                            Tickets</span>
                                    </a>
                                @endcan
                                @can('sales.view')
                                    <a href="/sales/status/pending"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Sales</span>
                                    </a>
                                @endcan
                                {{-- <a href="/g-drive"
                                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                <span
                                    class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">G. Drive</span>
                            </a> --}}

                            </div>
                        </div>
                    </div>
                @endcanany

                @canany(['refunds.create', 'refunds.view'])
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
                                @can('refunds.create')
                                    <a href="/refunds/create"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition {{ request()->is('refunds/create') ? 'bg-blue-100 text-blue-600' : '' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Make
                                            Refund</span>
                                    </a>
                                @endcan

                                <!-- Refunded Sell Link -->
                                @can('refunds.view')
                                    <a href="/refund-requests"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition {{ request()->is('refund-requests') ? 'bg-blue-100 text-blue-600' : '' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Requested</span>
                                    </a>
                                    <a href="/partner-approved-refunds"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition {{ request()->is('partner-approved-refunds') ? 'bg-blue-100 text-blue-600' : '' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Partner
                                            Approved</span>
                                    </a>
                                    <a href="/payment-details-added-refunds"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition {{ request()->is('payment-details-added-refunds') ? 'bg-blue-100 text-blue-600' : '' }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Payment
                                            Details Added</span>
                                    </a>
                                    <a href="/refunded"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Refunded
                                            Sell</span>
                                    </a>
                                @endcan


                            </div>


                        </div>
                    </div>
                @endcanany

                @canany(['ships.view', 'ships.create', 'companies.view', 'companies.create'])
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
                                @canany(['ships.view', 'ships.create'])
                                    <a href="/ships-details"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">New
                                            Ship</span>
                                    </a>
                                @endcan
                                @canany(['companies.view', 'companies.create'])
                                    <a href="/companies-details"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">New
                                            Company</span>
                                    </a>
                                @endcan

                            </div>
                        </div>
                    </div>
                @endcanany
                <!--<div class="px-2 pt-2">-->
                <!--    <div id="preBooking-dropdown" class="mb-1 relative">-->
                <!--        <button-->
                <!--            class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">-->
                <!--            <div class="flex items-center">-->
                <!--                <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor"-->
                <!--                    viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">-->
                <!--                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"-->
                <!--                        d="M9 17v1a1 1 0 001 1h4a1 1 0 001-1v-1m3-2V8a2 2 0 00-2-2H8a2 2 0 00-2 2v7m3-2h6">-->
                <!--                    </path>-->
                <!--                </svg>-->
                <!--                <span-->
                <!--                    class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Pre-->
                <!--                    Booking</span>-->
                <!--            </div>-->
                <!--            <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"-->
                <!--                id="reports-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"-->
                <!--                xmlns="http://www.w3.org/2000/svg">-->
                <!--                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"-->
                <!--                    d="M19 9l-7 7-7-7"></path>-->
                <!--            </svg>-->
                <!--        </button>-->
                <!--        <div id="preBooking-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">-->
                <!--            <a href="/admin/sales-reports"-->
                <!--                class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">-->
                <!--                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>-->
                <!--                <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">-->
                <!--                    <P>Pre Booking</P>-->
                <!--                </span>-->
                <!--            </a>-->

                <!--        </div>-->
                <!--    </div>-->
                <!--</div>-->

                <!-- Reports Section -->
                @canany(['reports.view', 'extra_received.view', 'cash_collections.view'])
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
                                @can('reports.view')
                                    <a href="/admin/sales-reports"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Sales
                                            Reports</span>
                                    </a>
                                @endcan
                                @can('extra_received.view')
                                    <a href="{{ route('extra-received.index') }}"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Extra
                                            Received</span>
                                    </a>
                                @endcan
                                @can('cash_collections.view')
                                    <a href="/show/cash-collections"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">
                                            Cash Collection</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endcanany


                @can('whatsapp.view')
                    <div class="px-2 pt-2">
                        <div id="whatsapp-dropdown" class="mb-1 relative">
                            <button
                                class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                                <div class="flex items-center">
                                    <i class="fa-brands fa-whatsapp text-blue-600"></i>
                                    <span
                                        class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Whatsapp</span>
                                </div>
                                <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                    id="whatsapp-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="whatsapp-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                                @can('whatsapp.view')
                                    <a href="/admin/whatsapp"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Whatsapp
                                            Details</span>
                                    </a>
                                @endcan

                            </div>
                        </div>
                    </div>
                @endcan

                @canany(['sale_drafts.view', 'sale_drafts.create'])
                    <div class="px-2 pt-2">
                        <div id="drafts-dropdown" class="mb-1 relative">
                            <button
                                class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5h6m-6 4h6m-6 4h6m-9 6h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <span
                                        class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Drafts</span>
                                </div>
                                <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                    id="drafts-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="drafts-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                                <a href="{{ route('sale-drafts.manage') }}"
                                    class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition {{ request()->routeIs('sale-drafts.manage') ? 'bg-blue-100 text-blue-700' : '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                    <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Sale Drafts</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcanany

                <!-- Admin Section (Users / Roles / Permissions) -->
                @canany(['users.view', 'roles.view', 'permissions.view', 'excel.view'])
                    <div class="px-2 pt-2">
                        <div id="admin-dropdown" class="mb-1 relative">
                            <button
                                class="flex items-center justify-between w-full px-3 py-2.5 text-sm font-medium text-gray-700 rounded-lg hover:bg-blue-50 group transition focus:outline-none">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 flex-shrink-0 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                        </path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span
                                        class="ml-3 whitespace-nowrap transition-all duration-300 sidebar-text truncate text-left">Admin</span>
                                </div>
                                <svg class="w-4 h-4 flex-shrink-0 transition-transform duration-200 text-gray-500"
                                    id="admin-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="admin-dropdown-list" class="mt-1 space-y-1 pl-8 hidden">
                                @can('excel.view')
                                    <a href="/excel"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 mr-3"></span>
                                        <span class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Excel</span>
                                    </a>
                                @endcan
                                @can('users.view')
                                    <a href="/users-details"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Users</span>
                                    </a>
                                @endcan
                                @can('roles.view')
                                    <a href="/roles-details"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Roles</span>
                                    </a>
                                @endcan
                                @can('permissions.view')
                                    <a href="/permissions-details"
                                        class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 rounded-lg hover:bg-blue-50 group transition">
                                        <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-3"></span>
                                        <span
                                            class="whitespace-nowrap transition-all duration-300 sidebar-text truncate">Permissions</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                @endcanany



            </nav>
        </div>

        <!-- Sidebar Footer (User Profile) -->

    </div>
</div>



@vite(['resources/js/layout/sidebar.js'])
