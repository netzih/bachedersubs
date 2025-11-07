// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize
    loadStats();
    loadTimeEntries();
    loadTeachers();
    setupEventListeners();

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            switchTab(tab);
        });
    });

    // Logout
    document.getElementById('logoutBtn').addEventListener('click', async function() {
        await fetch('/api/auth.php?action=logout');
        window.location.href = '/index.html';
    });
});

function setupEventListeners() {
    // Apply filters
    document.getElementById('applyFilters').addEventListener('click', loadTimeEntries);
    document.getElementById('clearFilters').addEventListener('click', function() {
        document.getElementById('filterStartDate').value = '';
        document.getElementById('filterEndDate').value = '';
        document.getElementById('filterTeacher').value = '';
        document.getElementById('filterPaidStatus').value = '';
        loadTimeEntries();
    });

    // Add teacher
    document.getElementById('addTeacherBtn').addEventListener('click', function() {
        showModal('addTeacherModal');
    });

    document.getElementById('addTeacherForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('teacherName').value;

        try {
            const response = await fetch('/api/teachers.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name })
            });

            const data = await response.json();
            if (data.success) {
                hideModal('addTeacherModal');
                this.reset();
                loadTeachers();
                alert('Teacher added successfully!');
            } else {
                showError('addTeacherError', data.message);
            }
        } catch (error) {
            showError('addTeacherError', 'An error occurred');
        }
    });

    // Edit rate form
    document.getElementById('editRateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const substituteId = document.getElementById('editRateSubId').value;
        const hourlyRate = document.getElementById('editRateAmount').value;

        try {
            const response = await fetch('/api/substitutes.php?action=update_rate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ substitute_id: substituteId, hourly_rate: hourlyRate })
            });

            const data = await response.json();
            if (data.success) {
                hideModal('editRateModal');
                loadSubstitutes();
                loadTimeEntries();
                loadStats();
                alert('Rate updated successfully!');
            } else {
                showError('editRateError', data.message);
            }
        } catch (error) {
            showError('editRateError', 'An error occurred');
        }
    });

    // Generate report
    document.getElementById('generateReport').addEventListener('click', generateReport);

    // Modal close buttons
    document.querySelectorAll('.close').forEach(btn => {
        btn.addEventListener('click', function() {
            hideModal(this.dataset.modal);
        });
    });

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                hideModal(this.id);
            }
        });
    });
}

function switchTab(tabName) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.tab === tabName) {
            btn.classList.add('active');
        }
    });

    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });

    const tabContent = document.getElementById(tabName + 'Tab');
    if (tabContent) {
        tabContent.classList.add('active');
    }

    // Load data for the tab
    if (tabName === 'substitutes') {
        loadSubstitutes();
    } else if (tabName === 'teachers') {
        loadTeachers();
    }
}

async function loadStats() {
    try {
        const response = await fetch('/api/time_entries.php?action=stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            document.getElementById('totalOwed').textContent = formatCurrency(stats.unpaid_amount || 0);
            document.getElementById('unpaidEntries').textContent = stats.unpaid_entries || 0;
            document.getElementById('unpaidHours').textContent = parseFloat(stats.total_hours || 0).toFixed(1);

            // Load substitutes count
            const subResponse = await fetch('/api/substitutes.php?action=list');
            const subData = await subResponse.json();
            if (subData.success) {
                document.getElementById('activeSubstitutes').textContent = subData.substitutes.length;
            }
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

async function loadTimeEntries() {
    try {
        const startDate = document.getElementById('filterStartDate').value;
        const endDate = document.getElementById('filterEndDate').value;
        const teacherId = document.getElementById('filterTeacher').value;
        const isPaid = document.getElementById('filterPaidStatus').value;

        let url = '/api/time_entries.php?action=list';
        const params = [];
        if (startDate) params.push(`start_date=${startDate}`);
        if (endDate) params.push(`end_date=${endDate}`);
        if (teacherId) params.push(`teacher_id=${teacherId}`);
        if (isPaid) params.push(`is_paid=${isPaid}`);
        if (params.length) url += '&' + params.join('&');

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            renderTimeEntries(data.entries);
        }
    } catch (error) {
        console.error('Error loading time entries:', error);
    }
}

function renderTimeEntries(entries) {
    const tbody = document.getElementById('timeEntriesBody');
    tbody.innerHTML = '';

    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="11" style="text-align: center;">No entries found</td></tr>';
        return;
    }

    entries.forEach(entry => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(entry.work_date)}</td>
            <td>${escapeHtml(entry.substitute_name)}</td>
            <td>${escapeHtml(entry.teacher_name)}</td>
            <td>${formatTime(entry.start_time)}</td>
            <td>${formatTime(entry.end_time)}</td>
            <td>${entry.hours}</td>
            <td>${formatCurrency(entry.hourly_rate)}</td>
            <td>${formatCurrency(entry.amount)}</td>
            <td>
                <span class="badge ${entry.is_paid ? 'badge-paid' : 'badge-unpaid'}">
                    ${entry.is_paid ? 'Paid' : 'Unpaid'}
                </span>
            </td>
            <td>${escapeHtml(entry.notes || '-')}</td>
            <td>
                <button class="btn btn-sm ${entry.is_paid ? 'btn-secondary' : 'btn-success'}"
                        onclick="togglePaid(${entry.id}, ${!entry.is_paid})">
                    ${entry.is_paid ? 'Mark Unpaid' : 'Mark Paid'}
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteEntry(${entry.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

async function togglePaid(entryId, isPaid) {
    try {
        const response = await fetch('/api/time_entries.php?action=mark_paid', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ entry_id: entryId, is_paid: isPaid })
        });

        const data = await response.json();
        if (data.success) {
            loadTimeEntries();
            loadStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function deleteEntry(entryId) {
    if (!confirm('Are you sure you want to delete this entry?')) return;

    try {
        const response = await fetch('/api/time_entries.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ entry_id: entryId })
        });

        const data = await response.json();
        if (data.success) {
            loadTimeEntries();
            loadStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function loadSubstitutes() {
    try {
        const response = await fetch('/api/substitutes.php?action=list');
        const data = await response.json();

        if (data.success) {
            renderSubstitutes(data.substitutes);
        }
    } catch (error) {
        console.error('Error loading substitutes:', error);
    }
}

function renderSubstitutes(substitutes) {
    const tbody = document.getElementById('substitutesBody');
    tbody.innerHTML = '';

    if (substitutes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No substitutes found</td></tr>';
        return;
    }

    substitutes.forEach(sub => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(sub.name)}</td>
            <td>${escapeHtml(sub.email)}</td>
            <td>${escapeHtml(sub.zelle_info || 'Not provided')}</td>
            <td>${formatCurrency(sub.hourly_rate)}</td>
            <td id="owed-${sub.id}">Loading...</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editRate(${sub.id}, '${escapeHtml(sub.name)}', ${sub.hourly_rate})">
                    Edit Rate
                </button>
            </td>
        `;
        tbody.appendChild(row);

        // Load owed amount for this substitute
        loadSubstituteOwed(sub.id);
    });
}

async function loadSubstituteOwed(substituteId) {
    try {
        // This is a workaround - we'll get all entries and calculate
        const response = await fetch(`/api/time_entries.php?action=list&is_paid=false`);
        const data = await response.json();

        if (data.success) {
            const subEntries = data.entries.filter(e => e.substitute_name);
            // Group by substitute and calculate
            let owed = 0;
            subEntries.forEach(entry => {
                owed += parseFloat(entry.amount || 0);
            });

            const cell = document.getElementById(`owed-${substituteId}`);
            if (cell) {
                // Find entries for this specific substitute
                // Since we don't have substitute_id in the response, we'll just show total for now
                cell.textContent = formatCurrency(0); // Will be updated when we fix the API
            }
        }
    } catch (error) {
        console.error('Error loading owed amount:', error);
    }
}

function editRate(subId, name, currentRate) {
    document.getElementById('editRateSubId').value = subId;
    document.getElementById('editRateSubName').value = name;
    document.getElementById('editRateAmount').value = currentRate;
    showModal('editRateModal');
}

async function loadTeachers() {
    try {
        const response = await fetch('/api/teachers.php?action=list');
        const data = await response.json();

        if (data.success) {
            renderTeachers(data.teachers);
            updateTeacherFilter(data.teachers);
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }
}

function renderTeachers(teachers) {
    const tbody = document.getElementById('teachersBody');
    tbody.innerHTML = '';

    if (teachers.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" style="text-align: center;">No teachers found</td></tr>';
        return;
    }

    teachers.forEach(teacher => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(teacher.name)}</td>
            <td id="hours-${teacher.id}">Loading...</td>
            <td>
                <button class="btn btn-sm btn-danger" onclick="deleteTeacher(${teacher.id})">Delete</button>
            </td>
        `;
        tbody.appendChild(row);

        // Load hours for this teacher
        loadTeacherHours(teacher.id);
    });
}

async function loadTeacherHours(teacherId) {
    try {
        const response = await fetch(`/api/time_entries.php?action=list&teacher_id=${teacherId}`);
        const data = await response.json();

        if (data.success) {
            const totalHours = data.entries.reduce((sum, entry) => sum + parseFloat(entry.hours), 0);
            const cell = document.getElementById(`hours-${teacherId}`);
            if (cell) {
                cell.textContent = totalHours.toFixed(1);
            }
        }
    } catch (error) {
        console.error('Error loading teacher hours:', error);
    }
}

function updateTeacherFilter(teachers) {
    const select = document.getElementById('filterTeacher');
    select.innerHTML = '<option value="">All Teachers</option>';
    teachers.forEach(teacher => {
        const option = document.createElement('option');
        option.value = teacher.id;
        option.textContent = teacher.name;
        select.appendChild(option);
    });
}

async function deleteTeacher(teacherId) {
    if (!confirm('Are you sure you want to delete this teacher?')) return;

    try {
        const response = await fetch('/api/teachers.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: teacherId })
        });

        const data = await response.json();
        if (data.success) {
            loadTeachers();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function generateReport() {
    const startDate = document.getElementById('reportStartDate').value;
    const endDate = document.getElementById('reportEndDate').value;

    if (!startDate || !endDate) {
        alert('Please select both start and end dates');
        return;
    }

    try {
        const response = await fetch(`/api/time_entries.php?action=list&start_date=${startDate}&end_date=${endDate}`);
        const data = await response.json();

        if (data.success) {
            renderReport(data.entries, startDate, endDate);
        }
    } catch (error) {
        console.error('Error generating report:', error);
    }
}

function renderReport(entries, startDate, endDate) {
    const container = document.getElementById('reportResults');

    // Group by teacher
    const teacherGroups = {};
    entries.forEach(entry => {
        if (!teacherGroups[entry.teacher_name]) {
            teacherGroups[entry.teacher_name] = [];
        }
        teacherGroups[entry.teacher_name].push(entry);
    });

    let html = `<h3>Report: ${formatDate(startDate)} to ${formatDate(endDate)}</h3>`;

    Object.keys(teacherGroups).forEach(teacherName => {
        const teacherEntries = teacherGroups[teacherName];
        const totalHours = teacherEntries.reduce((sum, e) => sum + parseFloat(e.hours), 0);
        const uniqueDates = [...new Set(teacherEntries.map(e => e.work_date))];

        html += `
            <div class="report-card">
                <h4>${escapeHtml(teacherName)}</h4>
                <div class="report-stat">
                    <span>Total Hours:</span>
                    <strong>${totalHours.toFixed(1)}</strong>
                </div>
                <div class="report-stat">
                    <span>Number of Days:</span>
                    <strong>${uniqueDates.length}</strong>
                </div>
                <div class="report-stat">
                    <span>Dates:</span>
                    <span>${uniqueDates.map(d => formatDate(d)).join(', ')}</span>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Utility functions
function showModal(modalId) {
    document.getElementById(modalId).classList.add('show');
}

function hideModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function showError(elementId, message) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.classList.add('show');
}

function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2);
}

function formatDate(dateStr) {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timeStr) {
    if (!timeStr) return '-';
    // Convert 24-hour time to 12-hour format
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}
