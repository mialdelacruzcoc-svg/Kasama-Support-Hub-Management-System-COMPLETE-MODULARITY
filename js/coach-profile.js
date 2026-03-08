// JavaScript for coach-profile.php

// ============================================
    // SECTION TOGGLE
    // ============================================
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        section.classList.toggle('collapsed');
    }

    // ============================================
    // TOAST NOTIFICATION
    // ============================================
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        toast.textContent = message;
        toast.className = 'toast ' + type;
        
        // Trigger reflow
        void toast.offsetWidth;
        toast.classList.add('show');
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // ============================================
    // SAVE PROFILE (Name & Email)
    // ============================================
    async function saveProfile(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSaveProfile');
        const name = document.getElementById('profileName').value.trim();
        const email = document.getElementById('profileEmail').value.trim();
        
        if (!name || !email) {
            showToast('Please fill in all fields.', 'error');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const formData = new FormData();
            formData.append('name', name);
            formData.append('email', email);

            const response = await fetch('../../api/update-coach-profile.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();

            if (data.success) {
                showToast('✅ Profile updated successfully!');
                // Update hero section
                document.getElementById('heroName').textContent = 'Coach ' + name;
                // Update header name
                document.querySelector('.user-name').textContent = 'Coach ' + name;
                // Update avatar initials
                const words = name.split(' ');
                let initials = '';
                words.forEach(w => { if (w) initials += w[0].toUpperCase(); });
                initials = initials.substring(0, 2);
                document.querySelectorAll('.user-avatar, .profile-avatar-large').forEach(el => {
                    el.textContent = initials;
                });
            } else {
                showToast('❌ ' + (data.message || 'Update failed'), 'error');
            }
        } catch (err) {
            showToast('❌ Network error. Please try again.', 'error');
        }

        btn.disabled = false;
        btn.textContent = '💾 Save Changes';
    }

    // ============================================
    // CHANGE PASSWORD
    // ============================================
    function checkPasswordStrength(password) {
        const bar = document.getElementById('strengthBar');
        const text = document.getElementById('strengthText');
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        const levels = [
            { width: '0%', bg: '#eee', label: '', color: '#999' },
            { width: '20%', bg: '#ef4444', label: 'Very Weak', color: '#ef4444' },
            { width: '40%', bg: '#f97316', label: 'Weak', color: '#f97316' },
            { width: '60%', bg: '#eab308', label: 'Fair', color: '#eab308' },
            { width: '80%', bg: '#22c55e', label: 'Strong', color: '#22c55e' },
            { width: '100%', bg: '#16a34a', label: 'Very Strong', color: '#16a34a' }
        ];

        const level = levels[strength];
        bar.style.width = level.width;
        bar.style.background = level.bg;
        text.textContent = level.label;
        text.style.color = level.color;
    }

    async function changePassword(e) {
        e.preventDefault();
        const btn = document.getElementById('btnChangePassword');
        const current = document.getElementById('currentPassword').value;
        const newPass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (newPass.length < 8) {
            showToast('❌ New password must be at least 8 characters.', 'error');
            return;
        }
        if (newPass !== confirm) {
            showToast('❌ Passwords do not match.', 'error');
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Updating...';

        try {
            const formData = new FormData();
            formData.append('current_password', current);
            formData.append('new_password', newPass);

            const response = await fetch('../../api/update-coach-password.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            });
            const data = await response.json();

            if (data.success) {
                showToast('✅ Password changed successfully!');
                document.getElementById('passwordForm').reset();
                document.getElementById('strengthBar').style.width = '0';
                document.getElementById('strengthText').textContent = '';
            } else {
                showToast('❌ ' + (data.message || 'Password change failed'), 'error');
            }
        } catch (err) {
            showToast('❌ Network error. Please try again.', 'error');
        }

        btn.disabled = false;
        btn.textContent = '🔑 Change Password';
    }

    // ============================================
    // NOTIFICATION PREFERENCES
    // ============================================
    let prefsTimeout = null;
    async function saveNotifPrefs() {
        // Debounce rapid toggles
        if (prefsTimeout) clearTimeout(prefsTimeout);
        prefsTimeout = setTimeout(async () => {
            const prefs = {
                email_new_concern: document.getElementById('prefConcern').checked,
                email_student_reply: document.getElementById('prefReply').checked,
                email_appointment: document.getElementById('prefAppointment').checked
            };

            try {
                const formData = new FormData();
                formData.append('prefs', JSON.stringify(prefs));

                const response = await fetch('../../api/update-notification-prefs.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const data = await response.json();

                if (data.success) {
                    showToast('✅ Preferences saved!');
                } else {
                    showToast('❌ ' + (data.message || 'Failed to save'), 'error');
                }
            } catch (err) {
                showToast('❌ Network error.', 'error');
            }
        }, 300);
    }
