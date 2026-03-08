// JavaScript for coach-notifications.php

// State
    let currentFilter = 'all';
    let currentPage = 0;
    let isLoading = false;
    let hasMore = true;
    let selectedIds = new Set();
    const ITEMS_PER_PAGE = 20;

    // Icons for notification types
    const typeIcons = {
        'new_concern': '📩',
        'concern_submitted': '📩',
        'student_reply': '💬',
        'response_added': '✉️',
        'status_changed': '🔄',
        'new_appointment': '📅',
        'appointment_booked': '📅',
        'appointment_confirmed': '✅',
        'appointment_reschedule': '📅',
        'appointment_completed': '✔️',
        'new_faq': '📚',
        'default': '🔔'
    };

    const typeClasses = {
        'new_concern': 'concern',
        'concern_submitted': 'concern',
        'student_reply': 'reply',
        'response_added': 'reply',
        'status_changed': 'status',
        'new_appointment': 'appointment',
        'appointment_booked': 'appointment',
        'appointment_confirmed': 'appointment',
        'appointment_reschedule': 'appointment',
        'appointment_completed': 'appointment',
        'new_faq': 'faq',
        'default': 'default'
    };

    const typeLabels = {
        'new_concern': 'Concern',
        'concern_submitted': 'Concern',
        'student_reply': 'Reply',
        'response_added': 'Reply',
        'status_changed': 'Status',
        'new_appointment': 'Appointment',
        'appointment_booked': 'Appointment',
        'appointment_confirmed': 'Appointment',
        'appointment_reschedule': 'Appointment',
        'appointment_completed': 'Appointment',
        'new_faq': 'FAQ',
        'default': 'Notification'
    };

    // Load notifications
    function loadNotifications(reset = false) {
        if (isLoading || (!hasMore && !reset)) return;
        
        if (reset) {
            currentPage = 0;
            hasMore = true;
            document.getElementById('notificationList').innerHTML = '';
            document.getElementById('endOfList').style.display = 'none';
        }

        isLoading = true;
        document.getElementById('loadingSpinner').classList.add('active');

        const offset = currentPage * ITEMS_PER_PAGE;
        
        fetch(`../../api/get-notifications-page.php?filter=${currentFilter}&limit=${ITEMS_PER_PAGE}&offset=${offset}`)
            .then(response => response.json())
            .then(data => {
                isLoading = false;
                document.getElementById('loadingSpinner').classList.remove('active');

                if (data.success) {
                    const list = document.getElementById('notificationList');
                    
                    if (data.notifications.length === 0 && currentPage === 0) {
                        document.getElementById('emptyState').style.display = 'block';
                        list.style.display = 'none';
                    } else {
                        document.getElementById('emptyState').style.display = 'none';
                        list.style.display = 'block';
                        
                        data.notifications.forEach(notif => {
                            list.appendChild(createNotificationItem(notif));
                        });

                        if (data.notifications.length < ITEMS_PER_PAGE) {
                            hasMore = false;
                            document.getElementById('endOfList').style.display = 'block';
                        }
                        
                        currentPage++;
                    }
                }
            })
            .catch(err => {
                isLoading = false;
                document.getElementById('loadingSpinner').classList.remove('active');
                console.error('Error loading notifications:', err);
            });
    }

    // Create notification item HTML
    function createNotificationItem(notif) {
        const div = document.createElement('div');
        div.className = 'notification-item' + (notif.is_read == 0 ? ' unread' : '');
        div.dataset.id = notif.id;
        
        const icon = typeIcons[notif.type] || typeIcons['default'];
        const iconClass = typeClasses[notif.type] || typeClasses['default'];
        const typeLabel = typeLabels[notif.type] || typeLabels['default'];
        const timeAgo = formatTimeAgo(notif.created_at);
        
        div.innerHTML = `
            <input type="checkbox" class="notif-checkbox" onchange="toggleSelect(${notif.id}, this.checked)" ${selectedIds.has(notif.id) ? 'checked' : ''}>
            <div class="notif-icon ${iconClass}">${icon}</div>
            <div class="notif-content" onclick="openNotification(${notif.id}, '${notif.link || ''}')">
                <div class="notif-title">
                    ${notif.is_read == 0 ? '<span class="unread-dot"></span>' : ''}
                    ${escapeHtml(notif.title)}
                </div>
                <div class="notif-message">${escapeHtml(notif.message)}</div>
                <div class="notif-meta">
                    <span class="notif-type">${typeLabel}</span>
                    <span>📅 ${timeAgo}</span>
                </div>
            </div>
            <div class="notif-actions">
                ${notif.is_read == 0 ? `<button class="notif-action-btn" onclick="event.stopPropagation(); markAsRead(${notif.id})">✓ Read</button>` : ''}
                <button class="notif-action-btn delete" onclick="event.stopPropagation(); deleteNotification(${notif.id})">🗑️</button>
            </div>
        `;
        
        return div;
    }

    // Format time ago
    function formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
        if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
        if (seconds < 604800) return Math.floor(seconds / 86400) + 'd ago';
        return date.toLocaleDateString();
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Open notification
    function openNotification(id, link) {
        // Mark as read first
        fetch('../../api/mark-notification-read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        }).then(() => {
            if (link) {
                window.location.href = link;
            } else {
                // Just mark as read visually
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const dot = item.querySelector('.unread-dot');
                    if (dot) dot.remove();
                    const readBtn = item.querySelector('.notif-action-btn:not(.delete)');
                    if (readBtn) readBtn.remove();
                }
                updateCounts();
            }
        });
    }

    // Mark single as read
    function markAsRead(id) {
        fetch('../../api/mark-notification-read.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('unread');
                    const dot = item.querySelector('.unread-dot');
                    if (dot) dot.remove();
                    const readBtn = item.querySelector('.notif-action-btn:not(.delete)');
                    if (readBtn) readBtn.remove();
                }
                updateCounts();
            }
        });
    }

    // Mark all as read
    function markAllAsRead() {
        if (!confirm('Mark all notifications as read?')) return;
        
        fetch('../../api/mark-all-notifications-read.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        const dot = item.querySelector('.unread-dot');
                        if (dot) dot.remove();
                        const readBtn = item.querySelector('.notif-action-btn:not(.delete)');
                        if (readBtn) readBtn.remove();
                    });
                    updateCounts();
                }
            });
    }

    // Delete notification
    function deleteNotification(id) {
        if (!confirm('Delete this notification?')) return;
        
        fetch('../../api/delete-notification.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                if (item) {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(100px)';
                    setTimeout(() => item.remove(), 300);
                }
                updateCounts();
            }
        });
    }

    // Delete all read
    function deleteAllRead() {
        if (!confirm('Delete all read notifications? This cannot be undone.')) return;
        
        fetch('../../api/delete-read-notifications.php', { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.querySelectorAll('.notification-item:not(.unread)').forEach(item => {
                        item.remove();
                    });
                    updateCounts();
                    alert(`${data.deleted} notifications deleted`);
                }
            });
    }

    // Selection handling
    function toggleSelect(id, checked) {
        if (checked) {
            selectedIds.add(id);
        } else {
            selectedIds.delete(id);
        }
        updateBulkActions();
    }

    function updateBulkActions() {
        const bulkBar = document.getElementById('bulkActions');
        const countSpan = document.getElementById('selectedCount');
        
        if (selectedIds.size > 0) {
            bulkBar.classList.add('active');
            countSpan.textContent = selectedIds.size;
        } else {
            bulkBar.classList.remove('active');
        }
    }

    function clearSelection() {
        selectedIds.clear();
        document.querySelectorAll('.notif-checkbox').forEach(cb => cb.checked = false);
        updateBulkActions();
    }

    function markSelectedAsRead() {
        const ids = Array.from(selectedIds);
        
        fetch('../../api/mark-notifications-read-bulk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                ids.forEach(id => {
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        const dot = item.querySelector('.unread-dot');
                        if (dot) dot.remove();
                    }
                });
                clearSelection();
                updateCounts();
            }
        });
    }

    function deleteSelected() {
        if (!confirm(`Delete ${selectedIds.size} selected notifications?`)) return;
        
        const ids = Array.from(selectedIds);
        
        fetch('../../api/delete-notifications-bulk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ids })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                ids.forEach(id => {
                    const item = document.querySelector(`.notification-item[data-id="${id}"]`);
                    if (item) item.remove();
                });
                clearSelection();
                updateCounts();
            }
        });
    }

    // Update counts
    function updateCounts() {
        fetch('../../api/get-notification-counts.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalCount').textContent = data.total;
                    document.getElementById('unreadCount').textContent = data.unread;
                    document.getElementById('readCount').textContent = data.total - data.unread;
                    
                    const badge = document.querySelector('.unread-badge');
                    if (data.unread > 0) {
                        if (badge) {
                            badge.textContent = data.unread + ' unread';
                        }
                    } else if (badge) {
                        badge.remove();
                    }
                }
            });
    }

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentFilter = this.dataset.filter;
            loadNotifications(true);
        });
    });

    // Infinite scroll
    window.addEventListener('scroll', () => {
        if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 200) {
            loadNotifications();
        }
    });

    // Initial load
    loadNotifications();
