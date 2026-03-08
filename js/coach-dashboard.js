// JavaScript for coach-dashboard.php

// ============================================
        // APPOINTMENT FUNCTIONS
        // ============================================
        function openReschedModal(id) {
            document.getElementById('modal_apt_id').value = id;
            document.getElementById('reschedModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('reschedModal').style.display = 'none';
        }

        async function updateAptStatus(id, status) {
            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', status);
            
            try {
                const response = await fetch('../../api/update-appointment-status.php', { method: 'POST', body: formData });
                const res = await response.json();
                if(res.success) { 
                    alert('Status updated to ' + status); 
                    location.reload(); 
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (e) { alert('System error occurred.'); }
        }

        async function submitReschedule() {
            const id = document.getElementById('modal_apt_id').value;
            const msg = document.getElementById('modal_message').value;
            
            if(!msg) return alert('Please provide a message for the student.');

            const formData = new FormData();
            formData.append('id', id);
            formData.append('status', 'Reschedule Requested');
            formData.append('message', msg);
            
            try {
                const response = await fetch('../../api/update-appointment-status.php', { method: 'POST', body: formData });
                const res = await response.json();
                if(res.success) { 
                    alert('Reschedule request sent!'); 
                    location.reload(); 
                } else {
                    alert('Error: ' + res.message);
                }
            } catch (e) { alert('System error occurred.'); }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('reschedModal')) closeModal();
        }

        // ============================================
        // PROFILE DROPDOWN
        // ============================================
        (function() {
            const wrapper = document.getElementById('profileWrapper');
            const toggle = document.getElementById('profileToggle');
            const dropdown = document.getElementById('profileDropdown');

            toggle.addEventListener('click', function(e) {
                e.stopPropagation();
                wrapper.classList.toggle('open');
                dropdown.classList.toggle('show');
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('#profileWrapper')) {
                    wrapper.classList.remove('open');
                    dropdown.classList.remove('show');
                }
            });
        })();
