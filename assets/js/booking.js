// Booking System JavaScript
let currentStep = 1;
let selectedEngineerName = '';
let selectedServiceName  = '';
let selectedDate  = '';
let selectedTime  = '';
let currentMonth  = new Date().getMonth() + 1;
let currentYear   = new Date().getFullYear();
let calendarSlots = {};   // date -> array of slot objects (from API)

// ── Init ──────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (typeof PRESELECTED_ENGINEER !== 'undefined' && PRESELECTED_ENGINEER) {
        const card = document.querySelector(`.engineer-select-card input[value="${PRESELECTED_ENGINEER}"]`)?.closest('.engineer-select-card');
        if (card) selectedEngineerName = card.querySelector('strong').textContent;
    }
    if (typeof PRESELECTED_DATE !== 'undefined' && PRESELECTED_DATE) {
        selectedDate = PRESELECTED_DATE;
    }
});

// ── Step navigation ───────────────────────────────────────────
function nextStep(step) {
    if (step === 2 && !document.querySelector('input[name="engineer_id"]:checked')) {
        showStepError('Please select an engineer to continue.'); return;
    }
    if (step === 3 && !document.querySelector('input[name="service_type"]:checked')) {
        showStepError('Please select a service type.'); return;
    }
    if (step === 3 && !document.querySelector('input[name="location"]').value.trim()) {
        showStepError('Please enter the survey location.'); return;
    }
    if (step === 4 && !document.getElementById('selectedDate').value) {
        showStepError('Please select a date from the calendar.'); return;
    }
    if (step === 4 && !document.getElementById('selectedTime').value) {
        showStepError('Please select a time slot.'); return;
    }

    document.querySelectorAll('.booking-step-content').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + step).classList.add('active');
    document.querySelectorAll('.step').forEach((el, i) => {
        el.classList.toggle('active', i < step);
    });
    currentStep = step;

    if (step === 3) loadCalendarWithSlots();
    if (step === 4) updateSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(step) { nextStep(step); }

function showStepError(msg) {
    let el = document.getElementById('stepError');
    if (!el) {
        el = document.createElement('div');
        el.id = 'stepError';
        el.className = 'alert alert-error';
        el.style.cssText = 'margin-bottom:16px;animation:fadeSlideUp .3s ease both';
        document.querySelector('.booking-step-content.active .booking-card')?.prepend(el);
    }
    el.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${msg}`;
    setTimeout(() => el?.remove(), 3500);
}

// ── Engineer / Service selection ──────────────────────────────
function selectEngineer(id, name) {
    document.querySelectorAll('.engineer-select-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    event.currentTarget.querySelector('input').checked = true;
    selectedEngineerName = name;
    calendarSlots = {};   // reset cached slots when engineer changes
}

function selectService(name) {
    document.querySelectorAll('.service-select-card').forEach(c => c.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    event.currentTarget.querySelector('input').checked = true;
    selectedServiceName = name;
}

// ── Calendar with real green/red highlighting ─────────────────
async function loadCalendarWithSlots() {
    const engineerId = document.querySelector('input[name="engineer_id"]:checked')?.value;
    if (!engineerId) return;

    const title = document.getElementById('calendarTitle');
    const grid  = document.getElementById('calendarGrid');
    const monthNames = ['January','February','March','April','May','June',
                        'July','August','September','October','November','December'];
    title.textContent = monthNames[currentMonth - 1] + ' ' + currentYear;

    // Show loading spinner in grid
    grid.innerHTML = '<div style="text-align:center;padding:24px;color:#9ca3af"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';

    // Fetch schedule data for this engineer/month/year
    try {
        const res  = await fetch(`${BASE_URL}/api/appointments.php?action=get_available_slots&engineer_id=${engineerId}&month=${currentMonth}&year=${currentYear}`);
        const data = await res.json();

        // Build a map: date -> {available: bool, slots: [...]}
        calendarSlots = {};
        if (data.success && data.slots) {
            data.slots.forEach(slot => {
                if (!calendarSlots[slot.date]) calendarSlots[slot.date] = { available: false, slots: [] };
                if (parseInt(slot.is_available)) calendarSlots[slot.date].available = true;
                calendarSlots[slot.date].slots.push(slot);
            });
        }
    } catch(e) {
        calendarSlots = {};
    }

    renderCalendar(grid);
}

function renderCalendar(grid) {
    const firstDay    = new Date(currentYear, currentMonth - 1, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
    const today       = new Date(); today.setHours(0,0,0,0);
    const todayStr    = `${today.getFullYear()}-${String(today.getMonth()+1).padStart(2,'0')}-${String(today.getDate()).padStart(2,'0')}`;

    let html = '<div class="calendar-days-header">';
    ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => { html += `<div>${d}</div>`; });
    html += '</div><div class="calendar-days">';

    for (let i = 0; i < firstDay; i++) html += '<div class="calendar-day empty"></div>';

    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${currentYear}-${String(currentMonth).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
        const dateObj = new Date(currentYear, currentMonth - 1, day);
        const isPast  = dateObj < today;
        const isToday = dateStr === todayStr;
        const isSelected = dateStr === selectedDate;

        let cls = 'calendar-day';
        let onclick = '';
        let title = '';

        if (isPast) {
            cls += ' disabled';
            title = 'Past date';
        } else if (calendarSlots[dateStr]) {
            if (calendarSlots[dateStr].available) {
                cls += ' available';
                title = `${calendarSlots[dateStr].slots.filter(s=>parseInt(s.is_available)).length} slot(s) available`;
                onclick = `selectDate('${dateStr}')`;
            } else {
                cls += ' unavailable';
                title = 'Fully booked';
            }
        } else {
            // No schedule data = no slots set = treat as unavailable
            cls += ' unavailable';
            title = 'No slots available';
        }

        if (isToday) cls += ' today';
        if (isSelected) cls += ' selected';

        html += `<div class="${cls}" onclick="${onclick}" title="${title}">${day}</div>`;
    }

    html += '</div>';
    grid.innerHTML = html;
}

function changeMonth(delta) {
    currentMonth += delta;
    if (currentMonth > 12) { currentMonth = 1;  currentYear++; }
    if (currentMonth < 1)  { currentMonth = 12; currentYear--; }
    loadCalendarWithSlots();
}

// ── Date & Time selection ─────────────────────────────────────
function selectDate(date) {
    selectedDate = date;
    document.getElementById('selectedDate').value = date;

    // Re-render calendar to update selected highlight
    renderCalendar(document.getElementById('calendarGrid'));

    loadTimeSlots(date);
}

function loadTimeSlots(date) {
    const engineerId = document.querySelector('input[name="engineer_id"]:checked')?.value;
    const container  = document.getElementById('timeSlots');

    container.innerHTML = '<div class="time-loading"><i class="fas fa-spinner fa-spin"></i> Loading slots…</div>';

    fetch(`${BASE_URL}/api/appointments.php?action=get_time_slots&engineer_id=${engineerId}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && data.slots && data.slots.length > 0) {
                let html = '';
                data.slots.forEach(slot => {
                    const start = slot.start_time.substring(0, 5);
                    const end   = slot.end_time.substring(0, 5);
                    html += `<div class="time-slot-card" onclick="selectTime('${start}', this)">
                        <i class="fas fa-clock"></i>
                        <span>${formatTime(start)} – ${formatTime(end)}</span>
                        <small style="margin-left:auto;font-size:11px;color:#9ca3af">${ucFirst(slot.slot_type?.replace('_',' ') || '')}</small>
                    </div>`;
                });
                container.innerHTML = html;

                // AI suggestion
                const aiBox  = document.getElementById('aiTimeSuggestion');
                const aiText = document.getElementById('aiSuggestionText');
                if (aiBox && aiText) {
                    aiBox.style.display = 'flex';
                    const tips = {
                        'Boundary Survey':     'Morning slots (8AM–12PM) are best for boundary surveys — better light and fewer site distractions.',
                        'Topographic Survey':  'Full-day slots are recommended for topographic surveys to ensure complete coverage.',
                        'Construction Layout': 'Early morning slots avoid site traffic and give the engineer more working time.',
                        'Geodetic Survey':     'Full-day slots are ideal for geodetic surveys requiring extensive measurements.',
                    };
                    aiText.textContent = tips[selectedServiceName] || 'Morning slots generally offer the best conditions for accurate surveying work.';
                }
            } else {
                container.innerHTML = `<div class="time-placeholder">
                    <i class="fas fa-calendar-times"></i>
                    <p>No available time slots for this date.<br>Please select a green date on the calendar.</p>
                </div>`;
            }
        })
        .catch(() => {
            container.innerHTML = '<div class="time-placeholder"><i class="fas fa-exclamation-circle"></i><p>Error loading slots. Please try again.</p></div>';
        });
}

function selectTime(time, el) {
    selectedTime = time;
    document.getElementById('selectedTime').value = time;
    document.querySelectorAll('.time-slot-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
}

// ── Helpers ───────────────────────────────────────────────────
function formatTime(time) {
    const [h, m] = time.split(':');
    const hour = parseInt(h);
    return `${hour % 12 || 12}:${m} ${hour >= 12 ? 'PM' : 'AM'}`;
}

function ucFirst(str) {
    return str ? str.charAt(0).toUpperCase() + str.slice(1) : '';
}

// ── Summary ───────────────────────────────────────────────────
function updateSummary() {
    document.getElementById('summary-engineer').textContent = selectedEngineerName;
    document.getElementById('summary-service').textContent  = selectedServiceName;
    document.getElementById('summary-date').textContent     = selectedDate
        ? new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', {year:'numeric',month:'long',day:'numeric'})
        : '—';
    document.getElementById('summary-time').textContent     = selectedTime ? formatTime(selectedTime) : '—';
    document.getElementById('summary-location').textContent = document.querySelector('input[name="location"]')?.value || '—';

    const prices = {
        'Boundary Survey':5000,'Topographic Survey':8000,'Construction Layout':6000,
        'Subdivision Survey':15000,'Route Survey':12000,'Hydrographic Survey':20000,
        'Geodetic Survey':25000,'As-Built Survey':7000
    };
    document.getElementById('summary-total').textContent = '₱' + (prices[selectedServiceName] || 5000).toLocaleString();
}

// ── Form submit ───────────────────────────────────────────────
document.getElementById('bookingForm')?.addEventListener('submit', function() {
    const btn = document.getElementById('confirmBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';
    btn.disabled = true;
});
