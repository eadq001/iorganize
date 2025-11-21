// Calendar JavaScript

let currentView = 'month';
let currentDate = new Date();
let events = [];

// Load calendar
async function loadCalendar() {
    try {
        const startDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const endDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        
        const response = await fetch(`api/calendar.php?start=${formatDate(startDate)}&end=${formatDate(endDate)}`, {
            credentials: 'include'
        });
        const data = await response.json();
        
        if (data.success) {
            events = data.data;
            renderCalendar();
        }
    } catch (error) {
        console.error('Error loading calendar:', error);
        document.getElementById('calendarContainer').innerHTML = '<p class="error">Failed to load calendar</p>';
    }
}

// Render calendar
function renderCalendar() {
    const container = document.getElementById('calendarContainer');
    
    if (currentView === 'month') {
        container.innerHTML = renderMonthView();
    } else {
        container.innerHTML = renderWeekView();
    }
    
    // Add event listeners
    attachEventListeners();
}

// Render month view
function renderMonthView() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const daysInMonth = lastDay.getDate();
    const startingDayOfWeek = firstDay.getDay();
    
    let html = '<div class="calendar-month">';
    html += '<div class="calendar-navigation">';
    html += `<button onclick="previousMonth()">← Previous</button>`;
    html += `<div class="calendar-current-month">${formatMonthYear(currentDate)}</div>`;
    html += `<button onclick="nextMonth()">Next →</button>`;
    html += '</div>';
    
    // Day headers
    html += '<div class="calendar-month-header">';
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    dayNames.forEach(day => {
        html += `<div class="calendar-day-header">${day}</div>`;
    });
    html += '</div>';
    
    // Calendar days
    html += '<div class="calendar-weeks">';
    
    let day = 1;
    for (let i = 0; i < 6; i++) {
        html += '<div class="calendar-week">';
        for (let j = 0; j < 7; j++) {
            if (i === 0 && j < startingDayOfWeek) {
                // Empty cells before first day
                html += '<div class="calendar-day other-month"></div>';
            } else if (day > daysInMonth) {
                // Empty cells after last day
                html += '<div class="calendar-day other-month"></div>';
            } else {
                const date = new Date(year, month, day);
                const dateString = formatDate(date);
                const dayEvents = events.filter(e => e.date === dateString);
                const isToday = isTodayDate(date);
                
                html += `<div class="calendar-day ${isToday ? 'today' : ''}" data-date="${dateString}">`;
                html += `<div class="calendar-day-number">${day}</div>`;
                html += '<div class="calendar-day-events">';
                dayEvents.forEach(event => {
                    html += `<div class="calendar-event" style="background-color: ${event.color}" onclick="editEvent(${event.id})">${escapeHtml(event.title)}</div>`;
                });
                html += '</div>';
                html += '</div>';
                day++;
            }
        }
        html += '</div>';
        if (day > daysInMonth) break;
    }
    
    html += '</div>';
    html += '</div>';
    
    return html;
}

// Render week view
function renderWeekView() {
    const weekStart = getWeekStart(new Date(currentDate));
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate() + 6);
    
    let html = '<div class="calendar-week-view">';
    html += '<div class="calendar-navigation">';
    html += `<button onclick="previousWeek()">← Previous</button>`;
    html += `<div class="calendar-current-month">${formatDate(weekStart)} - ${formatDate(weekEnd)}</div>`;
    html += `<button onclick="nextWeek()">Next →</button>`;
    html += '</div>';
    
    // Day headers
    html += '<div class="calendar-week-days">';
    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    for (let i = 0; i < 7; i++) {
        const date = new Date(weekStart);
        date.setDate(date.getDate() + i);
        const dateString = formatDate(date);
        const dayEvents = events.filter(e => e.date === dateString);
        
        html += `<div class="calendar-week-day">`;
        html += `<div class="calendar-week-day-header">${dayNames[i]} ${date.getDate()}</div>`;
        html += '<div class="calendar-week-day-events">';
        dayEvents.forEach(event => {
            html += `<div class="calendar-week-event" style="background-color: ${event.color}" onclick="editEvent(${event.id})">${escapeHtml(event.title)} ${event.time ? event.time : ''}</div>`;
        });
        html += '</div>';
        html += '</div>';
    }
    html += '</div>';
    
    html += '</div>';
    
    return html;
}

// Switch view
function switchView(view) {
    currentView = view;
    loadCalendar();
}

// Previous month
function previousMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    loadCalendar();
}

// Next month
function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    loadCalendar();
}

// Previous week
function previousWeek() {
    currentDate.setDate(currentDate.getDate() - 7);
    loadCalendar();
}

// Next week
function nextWeek() {
    currentDate.setDate(currentDate.getDate() + 7);
    loadCalendar();
}

// Get week start
function getWeekStart(date) {
    const d = new Date(date);
    const day = d.getDay();
    const diff = d.getDate() - day;
    return new Date(d.setDate(diff));
}

// Open event modal
function openEventModal(eventId = null, date = null) {
    const modal = document.getElementById('eventModal');
    const form = document.getElementById('eventForm');
    const modalTitle = document.getElementById('modalTitle');
    const dateInput = document.getElementById('eventDate');
    
    // Set minimum date to today to prevent selecting past dates
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    dateInput.setAttribute('min', formatDate(today));
    
    if (eventId) {
        // Edit event
        const event = events.find(e => e.id === eventId);
        if (event) {
            document.getElementById('eventId').value = event.id;
            document.getElementById('eventTitle').value = event.title;
            document.getElementById('eventDescription').value = event.description || '';
            document.getElementById('eventDate').value = event.date;
            document.getElementById('eventTime').value = event.time || '';
            document.getElementById('eventCategory').value = event.category || 'personal';
            document.getElementById('eventColor').value = event.color || '#3498db';
            modalTitle.textContent = 'Edit Event';
        }
    } else {
        // New event
        form.reset();
        document.getElementById('eventId').value = '';
        // Re-set min attribute after reset
        dateInput.setAttribute('min', formatDate(today));
        if (date) {
            // Only set the date if it's not in the past
            const [year, month, dayNum] = date.split('-').map(Number);
            const selectedDate = new Date(year, month - 1, dayNum);
            selectedDate.setHours(0, 0, 0, 0);
            
            if (selectedDate >= today) {
                document.getElementById('eventDate').value = date;
            } else {
                // If the clicked date is in the past, set to today instead
                document.getElementById('eventDate').value = formatDate(today);
            }
        }
        modalTitle.textContent = 'Add Event';
    }
    
    modal.style.display = 'block';
}

// Close event modal
function closeEventModal() {
    const modal = document.getElementById('eventModal');
    modal.style.display = 'none';
    document.getElementById('eventForm').reset();
}

// Edit event
function editEvent(eventId) {
    openEventModal(eventId);
}

// Save event
async function saveEvent(eventData) {
    try {
        const method = eventData.id ? 'PUT' : 'POST';
        const url = eventData.id ? 'api/calendar.php' : 'api/calendar.php';
        
        console.log('Saving event with data:', eventData);
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(eventData)
        });
        
        console.log('Response status:', response.status);
        
        const data = await response.json();
        console.log('Response data:', data);
        
        if (data.success) {
            closeEventModal();
            await loadCalendar();
        } else {
            alert('Failed to save event: ' + (data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving event:', error);
        alert('Failed to save event: ' + error.message);
    }
}

// Delete event
async function deleteEvent(eventId) {
    if (!confirm('Are you sure you want to delete this event?')) {
        return;
    }
    
    try {
        const response = await fetch(`api/calendar.php?id=${eventId}`, {
            method: 'DELETE',
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadCalendar();
        } else {
            alert('Failed to delete event');
        }
    } catch (error) {
        console.error('Error deleting event:', error);
        alert('Failed to delete event');
    }
}

// Attach event listeners
function attachEventListeners() {
    // Day click to add event
    const days = document.querySelectorAll('.calendar-day');
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to compare dates only
    
    days.forEach(day => {
        day.addEventListener('click', (e) => {
            if (e.target.classList.contains('calendar-day') || e.target.classList.contains('calendar-day-number')) {
                const date = day.dataset.date;
                if (date) {
                    // Parse date string (YYYY-MM-DD) and create date object
                    const [year, month, dayNum] = date.split('-').map(Number);
                    const clickedDate = new Date(year, month - 1, dayNum);
                    clickedDate.setHours(0, 0, 0, 0);
                    
                    // Only allow adding events for today or future dates
                    if (clickedDate >= today) {
                        openEventModal(null, date);
                    } else {
                        // Show message when trying to add event to past date
                        alert('Cannot add events to past dates. Please select today or a future date.');
                    }
                }
            }
        });
    });
}

// Initialize static event listeners (called once on page load)
function initializeEventListeners() {
    // Event form
    const form = document.getElementById('eventForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const eventData = {
                title: document.getElementById('eventTitle').value,
                description: document.getElementById('eventDescription').value,
                date: document.getElementById('eventDate').value,
                time: document.getElementById('eventTime').value,
                category: document.getElementById('eventCategory').value,
                color: document.getElementById('eventColor').value
            };
            
            const eventId = document.getElementById('eventId').value;
            if (eventId) {
                eventData.id = parseInt(eventId);
            }
            
            await saveEvent(eventData);
        });
    }
    
    // Category change updates color
    const categorySelect = document.getElementById('eventCategory');
    if (categorySelect) {
        categorySelect.addEventListener('change', (e) => {
            const option = e.target.options[e.target.selectedIndex];
            const color = option.dataset.color;
            if (color) {
                document.getElementById('eventColor').value = color;
            }
        });
    }
    
    // Close modal on outside click
    const modal = document.getElementById('eventModal');
    if (modal) {
        window.onclick = function(event) {
            if (event.target === modal) {
                closeEventModal();
            }
        };
    }
}

// Helper functions
function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

function formatMonthYear(date) {
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
}

function formatHour(hour) {
    return `${hour}:00`;
}

function isTodayDate(date) {
    const today = new Date();
    return date.getDate() === today.getDate() &&
           date.getMonth() === today.getMonth() &&
           date.getFullYear() === today.getFullYear();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Load calendar on page load
document.addEventListener('DOMContentLoaded', () => {
    loadCalendar();
    initializeEventListeners();
});

