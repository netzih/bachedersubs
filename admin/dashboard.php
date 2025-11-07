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
                <button id="addTimeEntryBtn" class="btn btn-primary">Manually Log Hours</button>
            </div>
            <div class="section-header">
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
                <button id="addSubstituteBtn" class="btn btn-primary">Add Substitute</button>
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

    <!-- Add Substitute Modal -->
    <div id="addSubstituteModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="addSubstituteModal">&times;</span>
            <h3>Add Substitute</h3>
            <form id="addSubstituteForm">
                <div class="form-group">
                    <label for="substituteName">Full Name</label>
                    <input type="text" id="substituteName" required>
                </div>
                <div class="form-group">
                    <label for="substituteEmail">Email</label>
                    <input type="email" id="substituteEmail" required>
                </div>
                <div class="form-group">
                    <label for="substitutePassword">Password</label>
                    <input type="password" id="substitutePassword" required minlength="6">
                    <small>Minimum 6 characters</small>
                </div>
                <div class="form-group">
                    <label for="substituteZelle">Zelle Email or Phone</label>
                    <input type="text" id="substituteZelle">
                </div>
                <div class="form-group">
                    <label for="substituteRate">Hourly Rate ($)</label>
                    <input type="number" id="substituteRate" step="0.01" min="0" value="0.00" required>
                </div>
                <div class="form-error" id="addSubstituteError"></div>
                <button type="submit" class="btn btn-primary">Add Substitute</button>
            </form>
        </div>
    </div>

    <!-- Add Time Entry Modal (Admin) -->
    <div id="addTimeEntryModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="addTimeEntryModal">&times;</span>
            <h3>Manually Log Hours</h3>
            <form id="addTimeEntryForm">
                <div class="form-group">
                    <label for="entrySubstitute">Substitute</label>
                    <select id="entrySubstitute" required>
                        <option value="">-- Select Substitute --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="entryTeacher">Teacher</label>
                    <select id="entryTeacher" required>
                        <option value="">-- Select Teacher --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="entryDate">Date</label>
                    <input type="date" id="entryDate" required>
                </div>
                <div class="form-group">
                    <label for="entryStartTime">Start Time</label>
                    <input type="time" id="entryStartTime" value="08:00" required>
                </div>
                <div class="form-group">
                    <label for="entryEndTime">End Time</label>
                    <input type="time" id="entryEndTime" value="15:00" required>
                </div>
                <div class="form-group">
                    <label>Calculated Hours</label>
                    <div id="entryCalculatedHours" style="padding: 10px; background: #f0f9ff; border-radius: 6px; font-weight: 500; color: #0369a1;">
                        Select start and end times
                    </div>
                </div>
                <div class="form-group">
                    <label for="entryNotes">Notes (Optional)</label>
                    <textarea id="entryNotes" rows="3"></textarea>
                </div>
                <div class="form-error" id="addTimeEntryError"></div>
                <button type="submit" class="btn btn-primary">Log Hours</button>
            </form>
        </div>
    </div>

    <!-- Substitute Details Modal -->
    <div id="substituteDetailsModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <span class="close" data-modal="substituteDetailsModal">&times;</span>
            <h3 id="subDetailsName">Substitute Details</h3>

            <div class="details-section">
                <h4>Contact Information</h4>
                <p><strong>Email:</strong> <span id="subDetailsEmail">-</span></p>
                <p><strong>Zelle:</strong> <span id="subDetailsZelle">-</span></p>
                <p><strong>Hourly Rate:</strong> <span id="subDetailsRate">-</span></p>
            </div>

            <div class="details-section">
                <h4>Summary</h4>
                <div class="details-stats">
                    <div class="detail-stat">
                        <span class="label">Total Hours Worked:</span>
                        <span class="value" id="subDetailsTotalHours">0</span>
                    </div>
                    <div class="detail-stat">
                        <span class="label">Hours Unpaid:</span>
                        <span class="value" id="subDetailsUnpaidHours">0</span>
                    </div>
                    <div class="detail-stat">
                        <span class="label">Amount Owed:</span>
                        <span class="value" id="subDetailsOwed">$0.00</span>
                    </div>
                    <div class="detail-stat">
                        <span class="label">Total Paid:</span>
                        <span class="value" id="subDetailsPaid">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="details-section">
                <h4>Recent Time Entries</h4>
                <div class="table-container">
                    <table id="subDetailsEntriesTable">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Teacher</th>
                                <th>Hours</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="subDetailsEntriesBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Time Entry Modal (Admin) -->
    <div id="editTimeEntryModal" class="modal">
        <div class="modal-content">
            <span class="close" data-modal="editTimeEntryModal">&times;</span>
            <h3>Edit Time Entry</h3>
            <form id="editTimeEntryForm">
                <input type="hidden" id="editEntryId">
                <div class="form-group">
                    <label for="editEntrySubstitute">Substitute</label>
                    <input type="text" id="editEntrySubstitute" readonly>
                </div>
                <div class="form-group">
                    <label for="editEntryTeacher">Teacher</label>
                    <select id="editEntryTeacher" required>
                        <option value="">-- Select Teacher --</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editEntryDate">Date</label>
                    <input type="date" id="editEntryDate" required>
                </div>
                <div class="form-group">
                    <label for="editEntryStartTime">Start Time</label>
                    <input type="time" id="editEntryStartTime" required>
                </div>
                <div class="form-group">
                    <label for="editEntryEndTime">End Time</label>
                    <input type="time" id="editEntryEndTime" required>
                </div>
                <div class="form-group">
                    <label>Calculated Hours</label>
                    <div id="editEntryCalculatedHours" style="padding: 10px; background: #f0f9ff; border-radius: 6px; font-weight: 500; color: #0369a1;">
                        Select start and end times
                    </div>
                </div>
                <div class="form-group">
                    <label for="editEntryNotes">Notes (Optional)</label>
                    <textarea id="editEntryNotes" rows="3"></textarea>
                </div>
                <div class="form-error" id="editTimeEntryError"></div>
                <button type="submit" class="btn btn-primary">Update Entry</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
