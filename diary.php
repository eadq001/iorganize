<?php
require_once 'config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diary - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/diary.css">
    <link rel="stylesheet" href="https://cdn.quilljs.com/1.3.6/quill.snow.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo"><?php echo SITE_NAME; ?></h1>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="calendar.php" class="nav-link">Calendar</a>
                <a href="diary.php" class="nav-link active">Diary</a>
                <a href="settings.php" class="nav-link">Settings</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo escapeOutput(getCurrentUsername()); ?></span>
            </div>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="diary-header">
            <h2>Diary</h2>
            <div class="diary-header-actions">
                <button id="lockEntryButton" class="btn btn-secondary" onclick="openLockModal('lock')" style="display:none;">Lock</button>
                <button id="unlockEntryButton" class="btn btn-secondary" onclick="openLockModal('unlock')" style="display:none;">Unlock</button>
                <button id="deleteEntryButton" class="btn btn-danger ghost" onclick="deleteCurrentEntry()" style="display:none;">Delete</button>
                <button class="btn btn-primary" onclick="openEntryModal()">+ New Entry</button>
            </div>
        </div>
        
        <div class="diary-container">
            <div class="diary-sidebar">
                <h3>Entries</h3>
                <div class="diary-filter">
                    <input type="date" id="filterStartDate" onchange="loadEntries()">
                    <button class="btn btn-small" onclick="resetFilter()">Reset</button>
                </div>
                <div id="diaryEntriesList" class="diary-entries-list">
                    <p class="loading">Loading entries...</p>
                </div>
            </div>
            
            <div class="diary-content">
                <div id="diaryEditor" class="diary-editor notebook">
                    <p class="placeholder">Select an entry or create a new one to start writing.</p>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Entry Modal -->
    <div id="entryModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEntryModal()">&times;</span>
            <h3 id="modalTitle">New Diary Entry</h3>
            <form id="entryForm">
                <input type="hidden" id="entryId">
                
                <div class="form-group">
                    <label for="entryTitle">Title *</label>
                    <input type="text" id="entryTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="entryDate">Date *</label>
                    <input type="date" id="entryDate" name="entry_date" required>
                </div>
                
                <div class="form-group">
                    <label for="entryMood">Mood</label>
                    <select id="entryMood" name="mood">
                        <option value="">Select mood</option>
                        <?php foreach (getMoodOptions() as $key => $mood): ?>
                            <option value="<?php echo $key; ?>"><?php echo $mood; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="entryContent">Content *</label>
                    <div id="entryContentEditor" style="height: 400px;"></div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEntryModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Lock Modal -->
    <div id="lockModal" class="modal">
        <div class="modal-content modal-sm">
            <span class="close" onclick="closeLockModal()">&times;</span>
            <h3 id="lockModalTitle">Secure Entry</h3>
            <p id="lockModalDescription" class="modal-subtitle">Set a 4 to 8 digit PIN for this entry.</p>
            <form id="lockForm">
                <input type="hidden" id="lockAction">
                
                <div class="form-group">
                    <label for="lockPin">PIN *</label>
                    <input type="password" id="lockPin" maxlength="8" inputmode="numeric" pattern="\d{4,8}" required>
                    <small class="helper-text">Digits only, 4-8 characters.</small>
                </div>
                
                <div class="form-group" id="lockConfirmGroup">
                    <label for="lockPinConfirm">Confirm PIN *</label>
                    <input type="password" id="lockPinConfirm" maxlength="8" inputmode="numeric" pattern="\d{4,8}">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Confirm</button>
                    <button type="button" class="btn btn-secondary" onclick="closeLockModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script src="assets/js/diary.js"></script>
</body>
</html>

