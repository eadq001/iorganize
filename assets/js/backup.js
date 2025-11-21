// Backup and Import functionality

async function createBackup() {
    try {
        // Show loading state
        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Creating backup...';
        
        console.log('Starting backup creation...');
        
        const response = await fetch('api/backup.php?action=create', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', Array.from(response.headers.entries()));
        
        if (!response.ok) {
            let errorMessage = 'Failed to create backup';
            try {
                const errorData = await response.json();
                errorMessage = errorData.message || errorMessage;
            } catch (e) {
                const text = await response.text();
                console.error('Response text:', text);
                errorMessage = 'Server error: ' + response.statusText;
            }
            throw new Error(errorMessage);
        }
        
        // Get the blob and create download
        const blob = await response.blob();
        console.log('Blob received, size:', blob.size);
        
        if (blob.size === 0) {
            throw new Error('Backup file is empty');
        }
        
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        
        // Get filename from Content-Disposition header if available
        const contentDisposition = response.headers.get('content-disposition');
        let filename = `backup_${new Date().toISOString().slice(0, 10)}.json`;
        if (contentDisposition) {
            console.log('Content-Disposition:', contentDisposition);
            const matches = contentDisposition.match(/filename="([^"]+)"/);
            if (matches) {
                filename = matches[1];
            }
        }
        
        console.log('Downloading file:', filename);
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        
        // Cleanup
        setTimeout(() => {
            window.URL.revokeObjectURL(url);
            a.remove();
        }, 100);
        
        showBackupMessage('Backup created and downloaded successfully!', 'success');
        btn.disabled = false;
        btn.textContent = originalText;
    } catch (error) {
        console.error('Error creating backup:', error);
        showBackupMessage('Failed to create backup: ' + error.message, 'error');
        if (event.target) {
            event.target.disabled = false;
            event.target.textContent = originalText;
        }
    }
}

async function importBackup() {
    try {
        const fileInput = document.getElementById('backupFile');
        const mergeCheckbox = document.getElementById('importOption');
        
        if (!fileInput.files.length) {
            showBackupMessage('Please select a backup file', 'error');
            return;
        }
        
        const file = fileInput.files[0];
        
        // Validate file type
        if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
            showBackupMessage('Please select a valid JSON file', 'error');
            return;
        }
        
        // Show loading state
        const btn = event.target;
        const originalText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Importing backup...';
        
        const formData = new FormData();
        formData.append('action', 'import');
        formData.append('backup_file', file);
        formData.append('merge', mergeCheckbox.checked ? '1' : '0');
        
        const response = await fetch('api/backup.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showBackupMessage(`Successfully imported ${data.items_count} items!`, 'success');
            fileInput.value = '';
            
            // Refresh page after 2 seconds to show updated data
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            showBackupMessage('Import failed: ' + data.message, 'error');
        }
        
        btn.disabled = false;
        btn.textContent = originalText;
    } catch (error) {
        console.error('Error importing backup:', error);
        showBackupMessage('Import error: ' + error.message, 'error');
        event.target.disabled = false;
        event.target.textContent = originalText;
    }
}

function showBackupMessage(message, type) {
    const messageDiv = document.getElementById('backupMessage');
    messageDiv.textContent = message;
    messageDiv.className = 'alert alert-' + type;
    messageDiv.style.display = 'block';
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            messageDiv.style.display = 'none';
        }, 5000);
    }
}
