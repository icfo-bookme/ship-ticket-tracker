<nav x-data="{ open: false }" class="bg-[#003366] border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <!-- Left Side -->
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-white" />
                    </a>
                </div>
            </div>

            <!-- Right Side -->
            <div class="hidden sm:flex sm:items-center gap-4">
                <!-- Documentation Button -->
                <a href="{{ url('/documentation') }}"
                    class="text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-md transition">
                    Documentation
                </a>

                <!-- Notification Icon -->
                <div class="relative">
                    <button id="notificationButton" class="relative p-2 text-white hover:bg-[#004d5a] rounded-full"
                        data-dropdown-toggle="notificationDropdown" data-dropdown-placement="bottom-end">

                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0h6z" />
                        </svg>
                        <span id="notificationBadge"
                            class="hidden absolute -top-1 -right-1 bg-[#F37021] text-white text-xs font-bold px-1.5 py-0.5 rounded-full"></span>
                    </button>

                    <div id="notificationDropdown" class="hidden z-50 w-80 bg-white rounded-lg shadow border">

                        <!-- Header -->
                        <div class="px-4 py-3 border-b">
                            <h3 class="font-semibold text-gray-800">Notifications</h3>
                        </div>

                        <!-- Content -->
                        <div id="notificationContent" class="max-h-96 overflow-y-auto"></div>

                    </div>
                </div>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-900 focus:outline-none transition">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Profile
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Log Out
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger Menu -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-300 hover:text-white hover:bg-gray-700 focus:outline-none transition">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': !open }" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': !open, 'inline-flex': open }" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Menu -->
    <div :class="{ 'block': open, 'hidden': !open }" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>

            <x-responsive-nav-link href="{{ url('/documentation') }}">
                Documentation
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Profile
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Log Out
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

<script>
    let notifications = [];
    let notificationCount = 0;

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('notificationButton')
            .addEventListener('click', () => {
                setTimeout(loadNotifications, 100);
            });

        checkNewNotifications();
        setInterval(checkNewNotifications, 15000);
    });

    function loadNotifications() {
        const content = document.getElementById('notificationContent');

        content.innerHTML = `<p class="p-4 text-center text-gray-500">Loading...</p>`;

        fetch('/notifications')
            .then(res => res.json())
            .then(data => {
                notifications = data.data || [];
                notificationCount = data.count || 0;
                updateNotificationBadge();
                displayNotifications(content);
            });
    }

    function displayNotifications(content) {
        if (notifications.length === 0) {
            content.innerHTML = `
                <p class="p-6 text-center text-gray-500">
                    No notifications
                </p>`;
            return;
        }

        let html = '';

        notifications.forEach(n => {
            const active = (n.isActive === 0 || n.isActive === true);

            html += `
                <div class="notification-item px-4 py-3 border-b ${active ? 'bg-blue-900 text-gray-200' : 'hover:bg-gray-50'}"
                    data-id="${n.id}">
                    <a href="/notification/verify/${n.id}" class="block">
                        <p class="text-sm font-medium">${n.notification}</p>
                        <p class="text-xs ${active ? 'text-blue-100' : 'text-gray-500'}">
                            Ticket(s) purchased ${getTimeAgo(n.created_at)}
                        </p>
                    </a>
                </div>
            `;
        });

        content.innerHTML = html;

        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                markAsRead(btn.dataset.id);
            });
        });
    }

    function markAsRead(id) {
        const notificationItem = document.querySelector(`.notification-item[data-id="${id}"]`);
        if (notificationItem) {
            notificationItem.remove();
            notificationCount--;
            updateNotificationBadge();

            fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });
        }
    }

    function updateNotificationBadge() {
        const badge = document.getElementById('notificationBadge');
        if (notificationCount > 0) {
            badge.textContent = notificationCount;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function checkNewNotifications() {
        fetch('/notifications')
            .then(res => res.json())
            .then(data => {
                if (data.count !== notificationCount) {
                    notificationCount = data.count;
                    updateNotificationBadge();
                }
            });
    }

    function getTimeAgo(date) {
        const diff = (new Date() - new Date(date)) / 60000;
        if (diff < 1) return 'Just now';
        if (diff < 60) return Math.floor(diff) + ' min ago';
        if (diff < 1440) return Math.floor(diff / 60) + ' hours ago';
        return Math.floor(diff / 1440) + ' days ago';
    }
</script>
