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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>Bay Area Cheder</h1>
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
                <h3>Total Owed</h3>
                <p class="stat-value" id="totalOwed">$0.00</p>
            </div>
            <div class="stat-card">
                <h3>Unpaid Hours</h3>
                <p class="stat-value" id="unpaidHours">0</p>
            </div>
            <div class="stat-card">
                <h3>Total Hours Worked</h3>
                <p class="stat-value" id="totalHours">0</p>
            </div>
            <div class="stat-card">
                <h3>Total Paid</h3>
                <p class="stat-value" id="totalPaid">$0.00</p>
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
                        <label for="hoursWorked">Hours Worked</label>
                        <input type="number" id="hoursWorked" step="0.5" min="0.5" required>
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

    <script src="../assets/js/substitute.js"></script>
</body>
</html>
