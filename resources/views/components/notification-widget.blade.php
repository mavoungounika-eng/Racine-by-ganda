<!-- Notification Widget - RACINE BY GANDA -->
<style>
    .notification-widget {
        position: relative;
    }

    .notification-bell {
        position: relative;
        background: transparent;
        border: none;
        cursor: pointer;
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }

    .notification-bell:hover {
        background: rgba(75, 29, 242, 0.1);
    }

    .notification-bell svg {
        width: 22px;
        height: 22px;
        fill: currentColor;
    }

    .notification-badge {
        position: absolute;
        top: 2px;
        right: 2px;
        min-width: 18px;
        height: 18px;
        background: #dc3545;
        color: white;
        font-size: 11px;
        font-weight: 600;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
        animation: notif-bounce 0.3s ease;
    }

    @keyframes notif-bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }

    .notification-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 360px;
        max-height: 480px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.2s ease;
        z-index: 1000;
        overflow: hidden;
    }

    .notification-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .notification-header {
        padding: 16px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8f9fa;
    }

    .notification-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #11001F;
    }

    .notification-header-actions {
        display: flex;
        gap: 8px;
    }

    .notification-header-btn {
        background: transparent;
        border: none;
        color: #4B1DF2;
        font-size: 0.8rem;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 4px;
        transition: background 0.2s;
    }

    .notification-header-btn:hover {
        background: rgba(75, 29, 242, 0.1);
    }

    .notification-list {
        max-height: 380px;
        overflow-y: auto;
    }

    .notification-list::-webkit-scrollbar {
        width: 4px;
    }

    .notification-list::-webkit-scrollbar-thumb {
        background: #ddd;
        border-radius: 2px;
    }

    .notification-item {
        display: flex;
        gap: 12px;
        padding: 14px 20px;
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.2s;
        cursor: pointer;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item.unread {
        background: rgba(75, 29, 242, 0.04);
        border-left: 3px solid #4B1DF2;
    }

    .notification-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .notification-icon.info { background: #e7f3ff; }
    .notification-icon.success { background: #e6f9ee; }
    .notification-icon.warning { background: #fff8e6; }
    .notification-icon.danger { background: #ffe6e6; }
    .notification-icon.order { background: #f0e6ff; }
    .notification-icon.stock { background: #e6f7ff; }
    .notification-icon.system { background: #f0f0f0; }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        font-size: 0.9rem;
        color: #11001F;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .notification-message {
        font-size: 0.8rem;
        color: #666;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .notification-time {
        font-size: 0.7rem;
        color: #999;
        margin-top: 4px;
    }

    .notification-empty {
        padding: 40px 20px;
        text-align: center;
        color: #999;
    }

    .notification-empty svg {
        width: 48px;
        height: 48px;
        fill: #ddd;
        margin-bottom: 12px;
    }

    .notification-footer {
        padding: 12px 20px;
        border-top: 1px solid #eee;
        text-align: center;
    }

    .notification-footer a {
        color: #4B1DF2;
        font-size: 0.85rem;
        text-decoration: none;
        font-weight: 500;
    }

    .notification-footer a:hover {
        text-decoration: underline;
    }

    /* Mobile */
    @media (max-width: 480px) {
        .notification-dropdown {
            width: calc(100vw - 32px);
            right: -60px;
        }
    }
</style>

<div class="notification-widget" id="notification-widget">
    <!-- Bell Button -->
    <button class="notification-bell" id="notification-bell" aria-label="Notifications">
        <svg viewBox="0 0 24 24">
            <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2zm-2 1H8v-6c0-2.48 1.51-4.5 4-4.5s4 2.02 4 4.5v6z"/>
        </svg>
        <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
    </button>

    <!-- Dropdown -->
    <div class="notification-dropdown" id="notification-dropdown">
        <div class="notification-header">
            <h3>🔔 Notifications</h3>
            <div class="notification-header-actions">
                <button class="notification-header-btn" id="mark-all-read" title="Tout marquer comme lu">
                    ✓ Tout lire
                </button>
            </div>
        </div>

        <div class="notification-list" id="notification-list">
            <div class="notification-empty">
                <svg viewBox="0 0 24 24">
                    <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                </svg>
                <p>Aucune notification</p>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const widget = document.getElementById('notification-widget');
    const bell = document.getElementById('notification-bell');
    const dropdown = document.getElementById('notification-dropdown');
    const badge = document.getElementById('notification-badge');
    const list = document.getElementById('notification-list');
    const markAllBtn = document.getElementById('mark-all-read');

    let isOpen = false;
    let notifications = [];

    // Toggle dropdown
    bell.addEventListener('click', (e) => {
        e.stopPropagation();
        isOpen = !isOpen;
        dropdown.classList.toggle('open', isOpen);
        if (isOpen) {
            loadNotifications();
        }
    });

    // Close on outside click
    document.addEventListener('click', (e) => {
        if (!widget.contains(e.target)) {
            isOpen = false;
            dropdown.classList.remove('open');
        }
    });

    // Mark all as read
    markAllBtn.addEventListener('click', async () => {
        try {
            await fetch('{{ route("notifications.read-all") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
            updateBadge(0);
            loadNotifications();
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    });

    // Load notifications
    async function loadNotifications() {
        try {
            const response = await fetch('{{ route("notifications.index") }}?limit=10', {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            notifications = data.notifications || [];
            updateBadge(data.unread_count || 0);
            renderNotifications();
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    // Update badge
    function updateBadge(count) {
        if (count > 0) {
            badge.style.display = 'flex';
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            badge.style.display = 'none';
        }
    }

    // Render notifications
    function renderNotifications() {
        if (notifications.length === 0) {
            list.innerHTML = `
                <div class="notification-empty">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.63-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.64 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"/>
                    </svg>
                    <p>Aucune notification</p>
                </div>
            `;
            return;
        }

        list.innerHTML = notifications.map(n => `
            <div class="notification-item ${n.is_read ? '' : 'unread'}" data-id="${n.id}" onclick="handleNotificationClick(${n.id}, '${n.action_url || ''}')">
                <div class="notification-icon ${n.type}">
                    ${n.display_icon || '🔔'}
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(n.title)}</div>
                    <div class="notification-message">${escapeHtml(n.message)}</div>
                    <div class="notification-time">${n.time_ago || ''}</div>
                </div>
            </div>
        `).join('');
    }

    // Handle notification click
    window.handleNotificationClick = async function(id, actionUrl) {
        // Mark as read
        try {
            await fetch(`/notifications/${id}/read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            });
        } catch (error) {
            console.error('Error marking as read:', error);
        }

        // Navigate if action URL exists
        if (actionUrl) {
            window.location.href = actionUrl;
        } else {
            loadNotifications();
        }
    };

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial load
    loadNotifications();

    // Poll for new notifications every 30 seconds
    setInterval(() => {
        if (!isOpen) {
            fetch('{{ route("notifications.count") }}', {
                headers: { 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => updateBadge(data.count || 0))
            .catch(() => {});
        }
    }, 30000);
})();
</script>

