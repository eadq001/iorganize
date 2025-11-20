// Diary JavaScript

let entries = [];
let currentEntry = null;
let quill = null;

// Initialize Quill editor
document.addEventListener('DOMContentLoaded', function() {
    const editorContainer = document.getElementById('entryContentEditor');
    if (editorContainer) {
        quill = new Quill('#entryContentEditor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline', 'strike'],
                    ['blockquote', 'code-block'],
                    [{ 'header': 1 }, { 'header': 2 }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'script': 'sub'}, { 'script': 'super' }],
                    [{ 'indent': '-1'}, { 'indent': '+1' }],
                    [{ 'direction': 'rtl' }],
                    [{ 'size': ['small', false, 'large', 'huge'] }],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'font': [] }],
                    [{ 'align': [] }],
                    ['clean'],
                    ['link', 'image']
                ]
            }
        });
    }
    
    loadEntries();
});

// Load entries
async function loadEntries() {
    try {
        const startDate = document.getElementById('filterStartDate')?.value;
        const endDate = document.getElementById('filterEndDate')?.value;
        
        let url = 'api/diary.php';
        if (startDate && endDate) {
            url += `?start=${startDate}&end=${endDate}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            entries = data.data;
            renderEntriesList();
        }
    } catch (error) {
        console.error('Error loading entries:', error);
        document.getElementById('diaryEntriesList').innerHTML = '<p class="error">Failed to load entries</p>';
    }
}

// Render entries list
function renderEntriesList() {
    const container = document.getElementById('diaryEntriesList');
    
    if (entries.length === 0) {
        container.innerHTML = '<p class="no-data">No diary entries found.</p>';
        return;
    }
    
    container.innerHTML = entries.map(entry => `
        <div class="diary-entry-item ${currentEntry && currentEntry.id === entry.id ? 'active' : ''}" 
             onclick="loadEntry(${entry.id})">
            <div class="entry-title">${escapeHtml(entry.title)}</div>
            <div class="entry-date">${formatDate(entry.entry_date)}</div>
            ${entry.mood ? `<div class="entry-mood">${getMoodEmoji(entry.mood)}</div>` : ''}
        </div>
    `).join('');
}

// Load entry
async function loadEntry(entryId) {
    try {
        const response = await fetch(`api/diary.php?id=${entryId}`);
        const data = await response.json();
        
        if (data.success) {
            currentEntry = data.data;
            displayEntry(currentEntry);
            renderEntriesList();
        }
    } catch (error) {
        console.error('Error loading entry:', error);
        alert('Failed to load entry');
    }
}

// Display entry
function displayEntry(entry) {
    const editor = document.getElementById('diaryEditor');
    
    if (quill) {
        quill.setContents(quill.clipboard.convert(entry.content));
    } else {
        editor.innerHTML = entry.content;
    }
    
    // Update entry form if modal is open
    if (document.getElementById('entryId')) {
        document.getElementById('entryId').value = entry.id;
        document.getElementById('entryTitle').value = entry.title;
        document.getElementById('entryDate').value = entry.entry_date;
        document.getElementById('entryMood').value = entry.mood || '';
    }
}

// Open entry modal
function openEntryModal(entryId = null) {
    const modal = document.getElementById('entryModal');
    const form = document.getElementById('entryForm');
    const modalTitle = document.getElementById('modalTitle');
    
    if (entryId) {
        // Edit entry
        loadEntry(entryId).then(() => {
            if (quill && currentEntry) {
                quill.setContents(quill.clipboard.convert(currentEntry.content));
            }
            modalTitle.textContent = 'Edit Diary Entry';
            modal.style.display = 'block';
        });
    } else {
        // New entry
        form.reset();
        document.getElementById('entryId').value = '';
        document.getElementById('entryDate').value = formatDate(new Date());
        if (quill) {
            quill.setContents('');
        }
        modalTitle.textContent = 'New Diary Entry';
        modal.style.display = 'block';
    }
}

// Close entry modal
function closeEntryModal() {
    const modal = document.getElementById('entryModal');
    modal.style.display = 'none';
    document.getElementById('entryForm').reset();
    if (quill) {
        quill.setContents('');
    }
}

// Save entry
async function saveEntry(entryData) {
    try {
        if (quill) {
            entryData.content = quill.root.innerHTML;
        }
        
        const method = entryData.id ? 'PUT' : 'POST';
        const url = 'api/diary.php';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(entryData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeEntryModal();
            await loadEntries();
            if (entryData.id) {
                await loadEntry(entryData.id);
            }
        } else {
            alert('Failed to save entry: ' + data.message);
        }
    } catch (error) {
        console.error('Error saving entry:', error);
        alert('Failed to save entry');
    }
}

// Delete entry
async function deleteEntry(entryId) {
    if (!confirm('Are you sure you want to delete this entry?')) {
        return;
    }
    
    try {
        const response = await fetch(`api/diary.php?id=${entryId}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            if (currentEntry && currentEntry.id === entryId) {
                currentEntry = null;
                document.getElementById('diaryEditor').innerHTML = '<p class="placeholder">Select an entry or create a new one to start writing.</p>';
            }
            await loadEntries();
        } else {
            alert('Failed to delete entry');
        }
    } catch (error) {
        console.error('Error deleting entry:', error);
        alert('Failed to delete entry');
    }
}

// Reset filter
function resetFilter() {
    document.getElementById('filterStartDate').value = '';
    document.getElementById('filterEndDate').value = '';
    loadEntries();
}

// Get mood emoji
function getMoodEmoji(mood) {
    const moods = {
        'happy': '😊',
        'sad': '😢',
        'excited': '🤩',
        'anxious': '😰',
        'calm': '😌',
        'angry': '😠',
        'grateful': '🙏',
        'tired': '😴',
        'motivated': '💪',
        'neutral': '😐'
    };
    return moods[mood] || '😐';
}

// Helper functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Entry form
    const form = document.getElementById('entryForm');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const entryData = {
                title: document.getElementById('entryTitle').value,
                date: document.getElementById('entryDate').value,
                mood: document.getElementById('entryMood').value,
                content: quill ? quill.root.innerHTML : ''
            };
            
            const entryId = document.getElementById('entryId').value;
            if (entryId) {
                entryData.id = parseInt(entryId);
            }
            
            await saveEntry(entryData);
        });
    }
    
    // Close modal on outside click
    const modal = document.getElementById('entryModal');
    if (modal) {
        window.onclick = function(event) {
            if (event.target === modal) {
                closeEntryModal();
            }
        };
    }
});

