<?php
require_once 'config/config.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/calendar.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo"><?php echo SITE_NAME; ?></h1>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link">Dashboard</a>
                <a href="calendar.php" class="nav-link active">Calendar</a>
                <a href="diary.php" class="nav-link">Diary</a>
                <a href="settings.php" class="nav-link">Settings</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo escapeOutput(getCurrentUsername()); ?></span>
            </div>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="calendar-header">
            <h2>Calendar</h2>
            <button class="btn btn-primary" onclick="openEventModal()">+ Add Event</button>
        </div>
        
        <div class="calendar-view-controls">
            <button class="btn btn-small" onclick="switchView('month')">Month</button>
            <button class="btn btn-small" onclick="switchView('week')">Week</button>
        </div>
        
        <div id="calendarContainer" class="calendar-container">
            <div class="calendar-loading">Loading calendar...</div>
        </div>
    </main>
    
    <!-- Event Modal -->
    <div id="eventModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEventModal()">&times;</span>
            <h3 id="modalTitle">Add Event</h3>
            <form id="eventForm">
                <input type="hidden" id="eventId">
                
                <div class="form-group">
                    <label for="eventTitle">Title *</label>
                    <input type="text" id="eventTitle" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="eventDescription">Description</label>
                    <textarea id="eventDescription" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="eventDate">Date *</label>
                    <input type="date" id="eventDate" name="date" required>
                </div>
                
                <div class="form-group">
                    <label for="eventTime">Time</label>
                    <input type="time" id="eventTime" name="time">
                </div>
                
                <div class="form-group">
                    <label for="eventCategory">Category</label>
                    <select id="eventCategory" name="category">
                        <?php foreach (getCalendarCategories() as $key => $cat): ?>
                            <option value="<?php echo $key; ?>" data-color="<?php echo $cat['color']; ?>">
                                <?php echo $cat['name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="eventColor">Color</label>
                    <input type="color" id="eventColor" name="color" value="#3498db">
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <button type="button" class="btn btn-secondary" onclick="closeEventModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/calendar.js"></script>
</body>
</html>

