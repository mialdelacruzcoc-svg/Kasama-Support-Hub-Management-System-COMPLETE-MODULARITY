// JavaScript for coach-appointments.php

// Filter appointments
    function filterAppointments(status) {
        document.querySelectorAll('.filter-tab').forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
        
        document.querySelectorAll('#appointmentsTable tr').forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Update status
    async function updateStatus(id, status) {
        if (!confirm('Update this appointment to "' + status + '"?')) return;
        
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', status);
        
        try {
            const response = await fetch('../../api/update-appointment-status.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                alert('✅ ' + result.message);
                location.reload();
            } else {
                alert('❌ Error: ' + result.message);
            }
        } catch (e) {
            alert('❌ Connection error');
        }
    }
    
    // View details
    function viewDetails(id, name, date, time, reason, status) {
        const statusClass = status.toLowerCase().replace(' ', '-');
        document.getElementById('detailsContent').innerHTML = `
            <div class="student-info" style="margin-bottom:20px;">
                <div class="student-avatar">${name.substring(0,2).toUpperCase()}</div>
                <div>
                    <div class="student-name">${name}</div>
                    <span class="status-badge status-${statusClass}">${status}</span>
                </div>
            </div>
            <p><strong>📅 Date:</strong> ${date}</p>
            <p><strong>🕐 Time:</strong> ${time}</p>
            <div class="reason-box">
                <div class="reason-label">📝 Reason for Appointment</div>
                <div class="reason-text">${reason}</div>
            </div>
        `;
        document.getElementById('detailsModal').style.display = 'block';
    }
    
    // Reschedule modal
    function openReschedModal(id) {
        document.getElementById('resched_apt_id').value = id;
        document.getElementById('resched_message').value = '';
        document.getElementById('reschedModal').style.display = 'block';
    }
    
    async function submitReschedule() {
        const id = document.getElementById('resched_apt_id').value;
        const message = document.getElementById('resched_message').value.trim();
        
        if (!message) {
            alert('Please write a message to the student.');
            return;
        }
        
        const formData = new FormData();
        formData.append('id', id);
        formData.append('status', 'Reschedule Requested');
        formData.append('message', message);
        
        try {
            const response = await fetch('../../api/update-appointment-status.php', { method: 'POST', body: formData });
            const result = await response.json();
            
            if (result.success) {
                alert('✅ Reschedule request sent!');
                location.reload();
            } else {
                alert('❌ Error: ' + result.message);
            }
        } catch (e) {
            alert('❌ Connection error');
        }
    }
    
    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modal on outside click
    window.onclick = function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    }
