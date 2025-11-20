<?php
require_once 'config/config.php';
requireLogin();

$currentUser = getCurrentUserId();
$username = getCurrentUsername();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <h1 class="nav-logo"><?php echo SITE_NAME; ?></h1>
            <div class="nav-menu">
                <a href="dashboard.php" class="nav-link active">Dashboard</a>
                <a href="calendar.php" class="nav-link">Calendar</a>
                <a href="diary.php" class="nav-link">Diary</a>
                <a href="settings.php" class="nav-link">Settings</a>
                <a href="logout.php" class="nav-link">Logout</a>
            </div>
            <div class="nav-user">
                <span>Welcome, <?php echo escapeOutput($username); ?></span>
            </div>
        </div>
    </nav>
    
    <main class="main-content">
        <div class="dashboard-header">
            <h2>Dashboard</h2>
            <p>Manage your calendar, notes, and diary</p>
        </div>
        
        <div class="dashboard-grid">
            <!-- Quick Stats -->
            <div class="dashboard-card">
                <h3>Quick Stats</h3>
                <div class="stats-grid" id="quickStats">
                    <div class="stat-item">
                        <div class="stat-value" id="eventsCount">-</div>
                        <div class="stat-label">Events This Month</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="notesCount">-</div>
                        <div class="stat-label">Sticky Notes</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value" id="diaryCount">-</div>
                        <div class="stat-label">Diary Entries</div>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Events -->
            <div class="dashboard-card">
                <h3>Upcoming Events</h3>
                <div id="upcomingEvents">
                    <p class="loading">Loading...</p>
                </div>
            </div>
            
            <!-- Sticky Notes -->
            <div class="dashboard-card full-width">
                <div class="card-header">
                    <h3>Sticky Notes</h3>
                    <button class="btn btn-small btn-primary" onclick="addStickyNote()">+ Add Note</button>
                </div>
                <div id="stickyNotesContainer" class="sticky-notes-container">
                    <p class="loading">Loading...</p>
                </div>
            </div>
        </div>
    </main>
    
    <script src="assets/js/dashboard.js"></script>
</body>
</html>

