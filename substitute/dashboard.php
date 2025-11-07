<?php
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireLogin();

// Redirect admin to admin dashboard
if ($auth->isAdmin()) {
    header('Location: /admin/dashboard.php');
    exit;
}

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Substitute Dashboard - Bay Area Cheder</title>
    <link rel="icon" type="image/jpeg" href="../assets/images/favicon.jpg">
    <link rel="apple-touch-icon" href="../assets/images/favicon.jpg">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <img src="../assets/images/logo.jpeg" alt="Bay Area Cheder Logo" class="logo">
                <span class="badge badge-substitute">Substitute</span>
            </div>
            <div class="nav-menu">
                <span class="nav-user">Welcome, <?php echo htmlspecialchars($user['name']); ?></span>
                <button id="logoutBtn" class="btn btn-secondary">Logout</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Hours Owed</h3>
                <p class="stat-value" id="totalOwed">0</p>
            </div>
            <div class="stat-card">
                <h3>Hours Paid</h3>
                <p class="stat-value" id="totalPaid">0</p>
            </div>
            <div class="stat-card">
                <h3>Total Hours Worked</h3>
                <p class="stat-value" id="totalHours">0</p>
            </div>
            <div class="stat-card">
                <h3>Total Entries</h3>
                <p class="stat-value" id="totalEntries">0</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="logHours">Log Hours</button>
            <button class="tab-btn" data-tab="myEntries">My Entries</button>
            <button class="tab-btn" data-tab="profile">Profile</button>
        </div>

        <!-- Log Hours Tab -->
        <div id="logHoursTab" class="tab-content active">
            <div class="card">
                <h2>Log Hours</h2>
                <form id="logHoursForm">
                    <div class="form-group">
                        <label for="teacherSelect">Teacher</label>
                        <select id="teacherSelect" required>
                            <option value="">-- Select Teacher --</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="workDate">Date</label>
                        <input type="date" id="workDate" required>
                    </div>
                    <div class="form-group">
                        <label for="startTime">Start Time</label>
                        <input type="time" id="startTime" value="08:00" required>
                    </div>
                    <div class="form-group">
                        <label for="endTime">End Time</label>
                        <input type="time" id="endTime" value="15:00" required>
                    </div>
                    <div class="form-group">
                        <label>Calculated Hours</label>
                        <div id="calculatedHours" style="padding: 10px; background: #f0f9ff; border-radius: 6px; font-weight: 500; color: #0369a1;">
                            Select start and end times
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="workNotes">Notes (Optional)</label>
                        <textarea id="workNotes" rows="3" placeholder="Any additional details..."></textarea>
                    </div>
                    <div class="form-error" id="logHoursError"></div>
                    <div class="form-success" id="logHoursSuccess"></div>
                    <button type="submit" class="btn btn-primary btn-block">Log Hours</button>
                </form>
            </div>
        </div>

        <!-- My Entries Tab -->
        <div id="myEntriesTab" class="tab-content">
            <div class="section-header">
                <h2>My Time Entries</h2>
                <div class="filters">
                    <select id="filterStatus">
                        <option value="">All Statuses</option>
                        <option value="false">Unpaid</option>
                        <option value="true">Paid</option>
                    </select>
                    <button id="applyFilter" class="btn btn-primary">Apply</button>
                </div>
            </div>

            <div class="table-container">
                <table id="entriesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Teacher</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="entriesBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Profile Tab -->
        <div id="profileTab" class="tab-content">
            <div class="card">
                <h2>My Profile</h2>
                <form id="profileForm">
                    <div class="form-group">
                        <label for="profileName">Full Name</label>
                        <input type="text" id="profileName" required>
                    </div>
                    <div class="form-group">
                        <label for="profileEmail">Email</label>
                        <input type="email" id="profileEmail" readonly>
                        <small>Email cannot be changed</small>
                    </div>
                    <div class="form-group">
                        <label for="profileZelle">Zelle Email or Phone</label>
                        <input type="text" id="profileZelle" placeholder="For payment purposes">
                    </div>
                    <div class="form-error" id="profileError"></div>
                    <div class="form-success" id="profileSuccess"></div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Time Entry Modal -->
    <div id="editTimeEntryModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="editTimeEntryModal">&times;</span>
            <h3>Edit Time Entry</h3>
            <form id="editTimeEntryForm">
                <input type="hidden" id="editEntryId">
                <div class="form-group">
                    <label for="editTeacherSelect">Teacher</label>
                    <select id="editTeacherSelect" required>
                        <option value="">-- Select Teacher --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editWorkDate">Date</label>
                    <input type="date" id="editWorkDate" required>
                </div>
                <div class="form-group">
                    <label for="editStartTime">Start Time</label>
                    <input type="time" id="editStartTime" required>
                </div>
                <div class="form-group">
                    <label for="editEndTime">End Time</label>
                    <input type="time" id="editEndTime" required>
                </div>
                <div class="form-group">
                    <label>Calculated Hours</label>
                    <div id="editCalculatedHours" style="padding: 10px; background: #f0f9ff; border-radius: 6px; font-weight: 500; color: #0369a1;">
                        Select start and end times
                    </div>
                </div>
                <div class="form-group">
                    <label for="editWorkNotes">Notes (Optional)</label>
                    <textarea id="editWorkNotes" rows="3"></textarea>
                </div>
                <div class="form-error" id="editTimeEntryError"></div>
                <button type="submit" class="btn btn-primary">Update Entry</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/substitute.js"></script>
</body>
</html>
