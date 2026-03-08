// JavaScript for book-appointment.php

let selectedDate = null;
        let selectedTime = null;
        let currentMonth = 0; // January
        let currentYear = 2026;
        const timeSlots = ['8:00 AM', '9:30 AM', '11:00 AM', '1:30 PM', '3:00 PM', '4:30 PM'];

        function generateCalendar() {
            const calendar = document.getElementById('calendar');
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonth').textContent = monthNames[currentMonth] + ' ' + currentYear;
            
            const headers = `<div class="calendar-day-header">Sun</div><div class="calendar-day-header">Mon</div><div class="calendar-day-header">Tue</div><div class="calendar-day-header">Wed</div><div class="calendar-day-header">Thu</div><div class="calendar-day-header">Fri</div><div class="calendar-day-header">Sat</div>`;
            calendar.innerHTML = headers;

            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
            const today = new Date(); today.setHours(0,0,0,0);

            for (let i = 0; i < firstDay; i++) calendar.appendChild(document.createElement('div'));

            for (let day = 1; day <= daysInMonth; day++) {
                const dayEl = document.createElement('div');
                dayEl.className = 'calendar-day';
                const dateObj = new Date(currentYear, currentMonth, day);
                
                if (dateObj < today) dayEl.classList.add('past');
                else if (dateObj.getDay() === 0 || dateObj.getDay() === 6) dayEl.classList.add('disabled');
                
                dayEl.innerHTML = `<span class="day-number">${day}</span>`;
                
                if (!dayEl.classList.contains('past') && !dayEl.classList.contains('disabled')) {
                    dayEl.onclick = () => {
                        document.querySelectorAll('.calendar-day').forEach(el => el.classList.remove('selected'));
                        dayEl.classList.add('selected');
                        selectedDate = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                        showTimeSlots();
                    };
                }
                calendar.appendChild(dayEl);
            }
        }

        function showTimeSlots() {
            const container = document.getElementById('timeSlots');
            container.innerHTML = '<p style="color:#888;">Loading available slots...</p>';
            document.getElementById('timeSlotsSection').style.display = 'block';
            document.getElementById('bookingForm').style.display = 'none';
            selectedTime = null;

            fetch('../../api/get-blocked-slots.php?date=' + selectedDate)
                .then(r => r.json())
                .then(data => {
                    const blocked = data.blocked || [];
                    const booked  = data.booked  || [];
                    container.innerHTML = '';

                    timeSlots.forEach(time => {
                        const slot = document.createElement('div');
                        const isBlocked = blocked.includes(time);
                        const isBooked  = booked.includes(time);

                        if (isBlocked) {
                            slot.className = 'time-slot blocked';
                            slot.innerHTML = time + '<span class="slot-label">Blocked</span>';
                        } else if (isBooked) {
                            slot.className = 'time-slot booked';
                            slot.innerHTML = time + '<span class="slot-label">Already Booked</span>';
                        } else {
                            slot.className = 'time-slot';
                            slot.textContent = time;
                            slot.onclick = () => {
                                document.querySelectorAll('.time-slot').forEach(el => el.classList.remove('selected'));
                                slot.classList.add('selected');
                                selectedTime = time;
                                document.getElementById('displayDate').textContent = selectedDate;
                                document.getElementById('displayTime').textContent = time;
                                document.getElementById('bookingForm').style.display = 'block';
                                document.getElementById('bookingForm').scrollIntoView({ behavior: 'smooth' });
                            };
                        }
                        container.appendChild(slot);
                    });
                })
                .catch(() => {
                    container.innerHTML = '<p style="color:red;">Failed to load availability. Please try again.</p>';
                });
        }

        async function confirmBooking() {
            const purpose = document.getElementById('appointmentPurpose').value;
            if (!selectedDate || !selectedTime) { alert('Palihug pagpili una og petsa ug oras.'); return; }
            if (!purpose || purpose.trim().length < 5) { alert('Palihug paghatag og rason.'); return; }

            const formData = new FormData();
            formData.append('date', selectedDate);
            formData.append('time', selectedTime);
            formData.append('reason', purpose);
            formData.append('linked_concern', document.getElementById('linkConcern').value);

            try {
                const response = await fetch('../../api/save-appointment.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    alert('✅ Appointment successfully booked!');
                    window.location.href = 'dashboard.php';
                } else { alert('❌ ' + result.message); }
            } catch (e) { alert('System error occurred.'); }
        }

        function previousMonth() { currentMonth--; if(currentMonth < 0) {currentMonth=11; currentYear--;} generateCalendar(); }
        function nextMonth() { currentMonth++; if(currentMonth > 11) {currentMonth=0; currentYear++;} generateCalendar(); }

        generateCalendar();
