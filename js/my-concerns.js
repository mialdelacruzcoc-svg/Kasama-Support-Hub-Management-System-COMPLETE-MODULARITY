// JavaScript for my-concerns.php

var allConcerns = [];
    var currentFilter = 'all';

    document.addEventListener('DOMContentLoaded', function() {
        loadMyConcerns();
        
        document.querySelectorAll('.filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(function(b) { b.classList.remove('active'); });
                this.classList.add('active');
                currentFilter = this.getAttribute('data-filter');
                renderConcerns();
            });
        });
    });

    function loadMyConcerns() {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../../api/get-my-concerns.php', true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    var data = JSON.parse(xhr.responseText);
                    if (data.success) {
                        allConcerns = data.data;
                        updateStats();
                        renderConcerns();
                    } else {
                        document.getElementById('concernsList').innerHTML = '<div class="empty-state"><p>Error: ' + data.message + '</p></div>';
                    }
                } catch(e) {
                    document.getElementById('concernsList').innerHTML = '<div class="empty-state"><p>Error loading concerns</p></div>';
                }
            }
        };
        xhr.send();
    }

    function updateStats() {
        document.getElementById('totalCount').textContent = allConcerns.length;
        document.getElementById('pendingCount').textContent = allConcerns.filter(function(c) { return c.status === 'Pending'; }).length;
        document.getElementById('progressCount').textContent = allConcerns.filter(function(c) { return c.status === 'In Progress'; }).length;
        document.getElementById('resolvedCount').textContent = allConcerns.filter(function(c) { return c.status === 'Resolved'; }).length;
    }

    function renderConcerns() {
        var concerns = currentFilter === 'all' ? allConcerns : allConcerns.filter(function(c) { return c.status === currentFilter; });
        var container = document.getElementById('concernsList');
        
        if (concerns.length === 0) {
            container.innerHTML = '<div class="empty-state"><h3>📭 No Concerns Found</h3><p>No concerns match this filter.</p></div>';
            return;
        }
        
        var html = '';
        concerns.forEach(function(concern) {
            var statusClass = concern.status.toLowerCase().replace(/\s+/g, '-');
            html += '<div class="concern-card status-' + statusClass + '">';
            html += '<div class="concern-header"><h3 class="concern-title">' + escapeHtml(concern.subject) + '</h3><span class="concern-id">' + concern.tracking_id + '</span></div>';
            html += '<div class="concern-meta"><span>📁 ' + concern.category + '</span><span>📅 ' + concern.created_at_formatted + '</span>';
            if (concern.is_anonymous == 1) html += '<span>🔒 Anonymous</span>';
            html += '</div>';
            html += '<div class="concern-description">' + escapeHtml(concern.description).substring(0, 150) + '...</div>';
            html += '<div class="concern-footer"><span class="badge badge-' + statusClass + '">' + concern.status + '</span>';
            html += '<div class="concern-actions"><button class="btn-view" onclick="viewConcern(\'' + concern.tracking_id + '\')">👁️ View</button>';
            html += '<button class="btn-delete" onclick="openDeleteModal(\'' + concern.tracking_id + '\')">🗑️ Delete</button></div></div></div>';
        });
        container.innerHTML = html;
    }

    function escapeHtml(text) { if (!text) return ''; var div = document.createElement('div'); div.textContent = text; return div.innerHTML; }
    function viewConcern(trackingId) { window.location.href = 'concern-details.php?id=' + trackingId; }
    function openDeleteModal(trackingId) { document.getElementById('deleteTrackingId').value = trackingId; document.getElementById('deleteModal').style.display = 'block'; }
    function closeDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }

    function confirmDelete() {
        var trackingId = document.getElementById('deleteTrackingId').value;
        var formData = new FormData();
        formData.append('tracking_id', trackingId);
        
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '../../api/delete-concern.php', true);
        xhr.onload = function() {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success) {
                    closeDeleteModal();
                    allConcerns = allConcerns.filter(function(c) { return c.tracking_id !== trackingId; });
                    updateStats();
                    renderConcerns();
                    alert('✅ Concern deleted successfully!');
                } else {
                    alert('❌ Error: ' + data.message);
                }
            } catch(e) { alert('❌ Error deleting concern'); }
        };
        xhr.send(formData);
    }

    window.onclick = function(event) { if (event.target == document.getElementById('deleteModal')) closeDeleteModal(); };
