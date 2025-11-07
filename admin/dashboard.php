<?php
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$auth = new Auth();
$auth->requireAdmin();

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bay Area Cheder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <h1>Bay Area Cheder</h1>
                <span class="badge badge-admin">Admin</span>
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
                <h3>Unpaid Entries</h3>
                <p class="stat-value" id="unpaidEntries">0</p>
            </div>
            <div class="stat-card">
                <h3>Total Hours (Unpaid)</h3>
                <p class="stat-value" id="unpaidHours">0</p>
            </div>
            <div class="stat-card">
                <h3>Active Substitutes</h3>
                <p class="stat-value" id="activeSubstitutes">0</p>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" data-tab="timeEntries">Time Entries</button>
            <button class="tab-btn" data-tab="substitutes">Substitutes</button>
            <button class="tab-btn" data-tab="teachers">Teachers</button>
            <button class="tab-btn" data-tab="reports">Reports</button>
        </div>

        <!-- Time Entries Tab -->
        <div id="timeEntriesTab" class="tab-content active">
            <div class="section-header">
                <h2>Time Entries</h2>
                <div class="filters">
                    <input type="date" id="filterStartDate" placeholder="Start Date">
                    <input type="date" id="filterEndDate" placeholder="End Date">
                    <select id="filterTeacher">
                        <option value="">All Teachers</option>
                    </select>
                    <select id="filterPaidStatus">
                        <option value="">All Statuses</option>
                        <option value="false">Unpaid</option>
                        <option value="true">Paid</option>
                    </select>
                    <button id="applyFilters" class="btn btn-primary">Apply Filters</button>
                    <button id="clearFilters" class="btn btn-secondary">Clear</button>
                </div>
            </div>

            <div class="table-container">
                <table id="timeEntriesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Substitute</th>
                            <th>Teacher</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Hours</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="timeEntriesBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Substitutes Tab -->
        <div id="substitutesTab" class="tab-content">
            <div class="section-header">
                <h2>Substitutes Management</h2>
            </div>

            <div class="table-container">
                <table id="substitutesTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Zelle Info</th>
                            <th>Hourly Rate</th>
                            <th>Total Owed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="substitutesBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Teachers Tab -->
        <div id="teachersTab" class="tab-content">
            <div class="section-header">
                <h2>Teachers Management</h2>
                <button id="addTeacherBtn" class="btn btn-primary">Add Teacher</button>
            </div>

            <div class="table-container">
                <table id="teachersTable">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Total Hours Used</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teachersBody">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reports Tab -->
        <div id="reportsTab" class="tab-content">
            <div class="section-header">
                <h2>Reports</h2>
            </div>

            <div class="report-filters">
                <h3>Teacher Hours Report</h3>
                <div class="form-group">
                    <label>Date Range:</label>
                    <input type="date" id="reportStartDate">
                    <input type="date" id="reportEndDate">
                    <button id="generateReport" class="btn btn-primary">Generate Report</button>
                </div>
            </div>

            <div id="reportResults" class="report-results"></div>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div id="addTeacherModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="addTeacherModal">&times;</span>
            <h3>Add Teacher</h3>
            <form id="addTeacherForm">
                <div class="form-group">
                    <label for="teacherName">Teacher Name</label>
                    <input type="text" id="teacherName" required>
                </div>
                <div class="form-error" id="addTeacherError"></div>
                <button type="submit" class="btn btn-primary">Add Teacher</button>
            </form>
        </div>
    </div>

    <!-- Edit Rate Modal -->
    <div id="editRateModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="editRateModal">&times;</span>
            <h3>Edit Hourly Rate</h3>
            <form id="editRateForm">
                <input type="hidden" id="editRateSubId">
                <div class="form-group">
                    <label for="editRateSubName">Substitute</label>
                    <input type="text" id="editRateSubName" readonly>
                </div>
                <div class="form-group">
                    <label for="editRateAmount">Hourly Rate ($)</label>
                    <input type="number" id="editRateAmount" step="0.01" min="0" required>
                </div>
                <div class="form-error" id="editRateError"></div>
                <button type="submit" class="btn btn-primary">Save Rate</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
