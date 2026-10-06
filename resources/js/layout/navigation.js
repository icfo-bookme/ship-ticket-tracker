    const navigationConfig = document.getElementById('navigationConfig');
    const notificationsUrl = navigationConfig?.dataset.notificationsUrl || '';
    const notificationReadUrl = navigationConfig?.dataset.notificationReadUrl || '';
    let notifications = [];
    let notificationCount = 0;

    document.addEventListener('DOMContentLoaded', () => {
        const notificationButton = document.getElementById('notificationButton');
        if (!notificationButton) return;

        notificationButton.addEventListener('click', () => {
            setTimeout(loadNotifications, 100);
        });

        checkNewNotifications();
        setInterval(checkNewNotifications, 15000);
    });

    function loadNotifications() {
        const content = document.getElementById('notificationContent');

        content.innerHTML = `<p class="p-4 text-center text-gray-500">Loading...</p>`;

        fetch(notificationsUrl)
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

            fetch(notificationReadUrl.replace('__ID__', encodeURIComponent(id)), {
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
        fetch(notificationsUrl)
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
