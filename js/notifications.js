// ============================================
// NOTIFICATION SYSTEM
// Shared notification bell dropdown widget
// Used in: coach-dashboard.php, concerns-table.php, coach-notifications.php
// ============================================
(function() {
            const bell = document.getElementById('notifBell');
            if (!bell) return; // Only run if notification bell exists on this page
            const dropdown = document.getElementById('notifDropdown');
            const badge = document.getElementById('notifBadge');
            const list = document.getElementById('notifList');
            const markAllBtn = document.getElementById('markAllBtn');
            
            let isOpen = false;
            
            // Toggle dropdown
            bell.addEventListener('click', function(e) {
                e.stopPropagation();
                isOpen = !isOpen;
                
                if (isOpen) {
                    dropdown.classList.add('show');
                    loadNotifications();
                } else {
                    dropdown.classList.remove('show');
                }
            });
            
            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.notif-wrapper')) {
                    dropdown.classList.remove('show');
                    isOpen = false;
                }
            });
            
            // Mark all read
            markAllBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                markAllAsRead();
            });
            
            // Load notifications
            async function loadNotifications() {
                list.innerHTML = '<div class="notif-empty">Loading...</div>';
                
                try {
                    const response = await fetch('../../api/get-notifications.php?limit=10', {
                        method: 'GET',
                        credentials: 'same-origin'
                    });
                    
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        renderNotifications(data.data);
                        updateBadge(data.unread_count);
                    } else {
                        list.innerHTML = '<div class="notif-empty">Error: ' + (data.message || 'Unknown error') + '</div>';
                    }
                } catch (err) {
                    console.error('Fetch error:', err);
                    list.innerHTML = '<div class="notif-empty">Failed to load notifications</div>';
                }
            }
            
            // Render notifications
            function renderNotifications(notifications) {
                if (!notifications || notifications.length === 0) {
                    list.innerHTML = '<div class="notif-empty">🔔 No notifications yet</div>';
                    return;
                }
                
                let html = '';
                notifications.forEach(function(n) {
                    const unreadClass = n.is_read == 0 ? 'unread' : '';
                    const safeUrl = n.url ? n.url.replace(/\\/g, '\\\\').replace(/'/g, "\\'") : '';
                    
                    html += '<div class="notif-item ' + unreadClass + '" onclick="openNotification(' + n.id + ', \'' + safeUrl + '\', ' + n.is_read + ')">';
                    html += '<span style="font-size: 20px;">' + n.icon + '</span>';
                    html += '<div style="flex: 1; min-width: 0;">';
                    html += '<div style="font-weight: 600; font-size: 13px; color: #333;">' + escapeHtml(n.title) + '</div>';
                    html += '<div style="font-size: 12px; color: #666; margin-top: 2px;">' + escapeHtml(n.message) + '</div>';
                    html += '<div style="font-size: 11px; color: #999; margin-top: 4px;">' + n.time_ago + '</div>';
                    html += '</div></div>';
                });
                
                list.innerHTML = html;
            }
            
            // Update badge
            function updateBadge(count) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
            
            // Mark all as read
            async function markAllAsRead() {
                try {
                    const formData = new FormData();
                    formData.append('mark_all', 'true');
                    
                    await fetch('../../api/mark-notification-read.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    });
                    
                    loadNotifications();
                    updateBadge(0);
                } catch (err) {
                    console.error('Error:', err);
                }
            }
            
            // Escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            // Initial badge count
            async function fetchBadgeCount() {
                try {
                    const response = await fetch('../../api/get-notifications.php?limit=1', {
                        credentials: 'same-origin'
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        updateBadge(data.unread_count);
                    }
                } catch (err) {
                    console.error('Badge fetch error:', err);
                }
            }
            
            // Load badge on page load
            fetchBadgeCount();
            
            // Refresh every 60 seconds
            setInterval(fetchBadgeCount, 60000);
        })();
        
        // Global function for notification click
        async function openNotification(id, url, isRead) {
            if (isRead == 0) {
                const formData = new FormData();
                formData.append('notification_id', id);
                await fetch('../../api/mark-notification-read.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
            }
            
            if (url) {
                window.location.href = url;
            }
        }
