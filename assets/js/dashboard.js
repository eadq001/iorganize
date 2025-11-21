// Dashboard JavaScript

let stickyNotes = [];
let isDragging = false;
let currentNote = null;
let offsetX = 0;
let offsetY = 0;

// Load dashboard data
async function loadDashboard() {
    try {
        // Load stats
        const statsResponse = await fetch('api/stats.php');
        const statsData = await statsResponse.json();
        
        if (statsData.success) {
            document.getElementById('eventsCount').textContent = statsData.data.events_count;
            document.getElementById('notesCount').textContent = statsData.data.notes_count;
            document.getElementById('diaryCount').textContent = statsData.data.diary_count;
            
            // Load upcoming events
            const eventsContainer = document.getElementById('upcomingEvents');
            if (statsData.data.upcoming_events.length > 0) {
                eventsContainer.innerHTML = statsData.data.upcoming_events.map(event => `
                    <div class="event-item" style="border-left-color: ${event.color}">
                        <div class="event-title">${escapeHtml(event.title)}</div>
                        <div class="event-date">${formatDate(event.date)} ${event.time ? event.time : ''}</div>
                    </div>
                `).join('');
            } else {
                eventsContainer.innerHTML = '<p class="no-data">No upcoming events</p>';
            }
        }
        
        // Load sticky notes
        await loadStickyNotes();
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// Load sticky notes
async function loadStickyNotes() {
    try {
        const response = await fetch('api/notes.php');
        const data = await response.json();
        
        if (data.success) {
            stickyNotes = data.data;
            renderStickyNotes();
        }
    } catch (error) {
        console.error('Error loading sticky notes:', error);
        document.getElementById('stickyNotesContainer').innerHTML = '<p class="error">Failed to load notes</p>';
    }
}

// Render sticky notes
function renderStickyNotes() {
    const container = document.getElementById('stickyNotesContainer');
    
    if (stickyNotes.length === 0) {
        container.innerHTML = '<p class="no-data">No sticky notes. Click "Add Note" to create one.</p>';
        return;
    }
    
    container.innerHTML = stickyNotes.map(note => `
        <div class="sticky-note" 
             style="left: ${note.position_x}px; top: ${note.position_y}px; background-color: ${note.color}"
             data-id="${note.id}">
            <div class="sticky-note-header">
                <span class="note-time">${formatDateTime(note.updated_at)}</span>
                <button onclick="deleteStickyNote(${note.id})" class="btn-delete">×</button>
            </div>
            <div class="sticky-note-content" 
                 contenteditable="true" 
                 onblur="updateStickyNote(${note.id}, this.textContent)"
                 data-id="${note.id}">
                ${escapeHtml(note.content)}
            </div>
            <div class="sticky-note-actions">
                <button onclick="changeNoteColor(${note.id})">Change Color</button>
            </div>
        </div>
    `).join('');
    
    // Make notes draggable
    makeNotesDraggable();
}

// Make notes draggable
function makeNotesDraggable() {
    const notes = document.querySelectorAll('.sticky-note');
    
    notes.forEach(note => {
        note.addEventListener('mousedown', startDrag);
    });
}

// Start dragging
function startDrag(e) {
    if (e.target.classList.contains('sticky-note-content') || e.target.classList.contains('btn-delete')) {
        return;
    }
    
    isDragging = true;
    currentNote = e.currentTarget;
    
    const rect = currentNote.getBoundingClientRect();
    const container = document.getElementById('stickyNotesContainer');
    const containerRect = container.getBoundingClientRect();
    
    offsetX = e.clientX - rect.left;
    offsetY = e.clientY - rect.top;
    
    currentNote.style.cursor = 'grabbing';
    currentNote.style.zIndex = '1000';
    
    document.addEventListener('mousemove', drag);
    document.addEventListener('mouseup', stopDrag);
}

// Drag
function drag(e) {
    if (!isDragging || !currentNote) return;
    
    const container = document.getElementById('stickyNotesContainer');
    const containerRect = container.getBoundingClientRect();
    
    let x = e.clientX - containerRect.left - offsetX;
    let y = e.clientY - containerRect.top - offsetY;
    
    // Constrain to container
    x = Math.max(0, Math.min(x, containerRect.width - currentNote.offsetWidth));
    y = Math.max(0, Math.min(y, containerRect.height - currentNote.offsetHeight));
    
    currentNote.style.left = x + 'px';
    currentNote.style.top = y + 'px';
}

// Stop dragging
function stopDrag() {
    if (isDragging && currentNote) {
        const noteId = parseInt(currentNote.dataset.id);
        const x = parseInt(currentNote.style.left);
        const y = parseInt(currentNote.style.top);
        
        updateStickyNotePosition(noteId, x, y);
        
        currentNote.style.cursor = 'move';
        currentNote.style.zIndex = '10';
        currentNote = null;
        isDragging = false;
    }
    
    document.removeEventListener('mousemove', drag);
    document.removeEventListener('mouseup', stopDrag);
}

// Add sticky note
async function addStickyNote() {
    try {
        const response = await fetch('api/notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                content: 'New Note',
                position_x: 100 + Math.random() * 200,
                position_y: 100 + Math.random() * 200,
                color: getRandomColor()
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadStickyNotes();
        }
    } catch (error) {
        console.error('Error adding sticky note:', error);
        alert('Failed to add note');
    }
}

// Update sticky note
async function updateStickyNote(noteId, content) {
    try {
        const note = stickyNotes.find(n => n.id === noteId);
        if (!note) return;
        
        const response = await fetch('api/notes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: noteId,
                content: content,
                position_x: note.position_x,
                position_y: note.position_y,
                color: note.color
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadStickyNotes();
        }
    } catch (error) {
        console.error('Error updating sticky note:', error);
    }
}

// Update sticky note position
async function updateStickyNotePosition(noteId, x, y) {
    try {
        const note = stickyNotes.find(n => n.id === noteId);
        if (!note) return;
        
        const response = await fetch('api/notes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: noteId,
                content: note.content,
                position_x: x,
                position_y: y,
                color: note.color
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadStickyNotes();
        }
    } catch (error) {
        console.error('Error updating sticky note position:', error);
    }
}

// Delete sticky note
async function deleteStickyNote(noteId) {
    if (!confirm('Are you sure you want to delete this note?')) {
        return;
    }
    
    try {
        const response = await fetch(`api/notes.php?id=${noteId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            await loadStickyNotes();
        } else {
            alert('Failed to delete note');
        }
    } catch (error) {
        console.error('Error deleting sticky note:', error);
        alert('Failed to delete note');
    }
}

// Change note color
function changeNoteColor(noteId) {
    const note = stickyNotes.find(n => n.id === noteId);
    if (!note) return;
    
    const noteElement = document.querySelector(`.sticky-note[data-id="${noteId}"]`);
    
    // Create modal dialog
    const modal = document.createElement('div');
    modal.className = 'color-picker-modal';
    modal.innerHTML = `
        <div class="color-picker-content">
            <h4>Select Note Color</h4>
            <div class="color-picker-wrapper">
                <input type="color" id="colorInput" value="${note.color}" class="color-input">
                <div class="color-preview" id="colorPreview" style="background-color: ${note.color}"></div>
            </div>
            <div class="color-picker-actions">
                <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBtn">Save</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    const colorInput = document.getElementById('colorInput');
    const colorPreview = document.getElementById('colorPreview');
    const cancelBtn = document.getElementById('cancelBtn');
    const saveBtn = document.getElementById('saveBtn');
    
    let selectedColor = note.color;
    
    // Update preview as user selects color
    colorInput.addEventListener('input', (e) => {
        selectedColor = e.target.value;
        colorPreview.style.backgroundColor = selectedColor;
        if (noteElement) {
            noteElement.style.backgroundColor = selectedColor;
        }
    });
    
    // Cancel button
    cancelBtn.addEventListener('click', () => {
        if (noteElement) {
            noteElement.style.backgroundColor = note.color;
        }
        modal.remove();
    });
    
    // Save button
    saveBtn.addEventListener('click', async () => {
        await saveNoteColor(noteId, selectedColor);
        modal.remove();
    });
    
    // Focus the color input to open picker
    setTimeout(() => colorInput.click(), 100);
}

// Close color picker
function closeColorPicker() {
    const modal = document.querySelector('.color-picker-modal');
    if (modal) {
        modal.remove();
    }
}

// Save note color
async function saveNoteColor(noteId, color) {
    try {
        const note = stickyNotes.find(n => n.id === noteId);
        if (!note) return;
        
        // Update the local array immediately
        note.color = color;
        
        const response = await fetch('api/notes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: noteId,
                content: note.content,
                position_x: note.position_x,
                position_y: note.position_y,
                color: color
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Refresh to sync with server
            await loadStickyNotes();
        } else {
            alert('Failed to save color');
        }
    } catch (error) {
        console.error('Error updating sticky note color:', error);
        alert('Failed to save color');
    }
}

// Update sticky note color
async function updateStickyNoteColor(noteId, color) {
    try {
        const note = stickyNotes.find(n => n.id === noteId);
        if (!note) return;
        
        // Update the local array immediately
        note.color = color;
        
        const response = await fetch('api/notes.php', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: noteId,
                content: note.content,
                position_x: note.position_x,
                position_y: note.position_y,
                color: color
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Refresh to sync with server, but color change is already applied
            await loadStickyNotes();
        }
    } catch (error) {
        console.error('Error updating sticky note color:', error);
    }
}

// Helper functions
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function getRandomColor() {
    const colors = ['#f1c40f', '#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#1abc9c', '#f39c12'];
    return colors[Math.floor(Math.random() * colors.length)];
}

// Load dashboard on page load
document.addEventListener('DOMContentLoaded', loadDashboard);

