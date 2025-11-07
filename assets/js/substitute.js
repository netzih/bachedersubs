// Substitute Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize
    loadStats();
    loadTeachers();
    loadMyEntries();
    loadProfile();
    setTodayDate();

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

    // Log hours form
    document.getElementById('logHoursForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        clearMessages();

        const teacherId = document.getElementById('teacherSelect').value;
        const workDate = document.getElementById('workDate').value;
        const hours = document.getElementById('hoursWorked').value;
        const notes = document.getElementById('workNotes').value;

        try {
            const response = await fetch('/api/time_entries.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    work_date: workDate,
                    hours: hours,
                    notes: notes
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('logHoursSuccess', 'Hours logged successfully!');
                this.reset();
                setTodayDate();
                loadStats();
                loadMyEntries();
            } else {
                showError('logHoursError', data.message || 'Failed to log hours');
            }
        } catch (error) {
            showError('logHoursError', 'An error occurred. Please try again.');
        }
    });

    // Profile form
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        clearMessages();

        const name = document.getElementById('profileName').value;
        const zelleInfo = document.getElementById('profileZelle').value;

        try {
            const response = await fetch('/api/substitutes.php?action=update_profile', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name, zelle_info: zelleInfo })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('profileSuccess', 'Profile updated successfully!');
            } else {
                showError('profileError', data.message || 'Failed to update profile');
            }
        } catch (error) {
            showError('profileError', 'An error occurred. Please try again.');
        }
    });

    // Apply filter
    document.getElementById('applyFilter').addEventListener('click', loadMyEntries);
});

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
}

function setTodayDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('workDate').value = today;
}

async function loadStats() {
    try {
        const response = await fetch('/api/time_entries.php?action=stats');
        const data = await response.json();

        if (data.success) {
            const stats = data.stats;
            document.getElementById('totalOwed').textContent = formatCurrency(stats.unpaid_amount || 0);
            document.getElementById('unpaidHours').textContent = parseFloat(stats.total_hours || 0).toFixed(1);
            document.getElementById('totalHours').textContent = parseFloat(stats.total_hours || 0).toFixed(1);
            document.getElementById('totalPaid').textContent = formatCurrency(stats.paid_amount || 0);
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
}

async function loadTeachers() {
    try {
        const response = await fetch('/api/teachers.php?action=list');
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('teacherSelect');
            select.innerHTML = '<option value="">-- Select Teacher --</option>';

            data.teachers.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = teacher.name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }
}

async function loadMyEntries() {
    try {
        const isPaid = document.getElementById('filterStatus').value;
        let url = '/api/time_entries.php?action=list';
        if (isPaid) {
            url += `&is_paid=${isPaid}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            renderEntries(data.entries);
        }
    } catch (error) {
        console.error('Error loading entries:', error);
    }
}

function renderEntries(entries) {
    const tbody = document.getElementById('entriesBody');
    tbody.innerHTML = '';

    if (entries.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align: center;">No entries found</td></tr>';
        return;
    }

    entries.forEach(entry => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(entry.work_date)}</td>
            <td>${escapeHtml(entry.teacher_name)}</td>
            <td>${entry.hours}</td>
            <td>
                <span class="badge ${entry.is_paid ? 'badge-paid' : 'badge-unpaid'}">
                    ${entry.is_paid ? 'Paid' : 'Unpaid'}
                </span>
            </td>
            <td>${escapeHtml(entry.notes || '-')}</td>
            <td>
                ${!entry.is_paid ? `<button class="btn btn-sm btn-danger" onclick="deleteEntry(${entry.id})">Delete</button>` : '-'}
            </td>
        `;
        tbody.appendChild(row);
    });
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
            loadMyEntries();
            loadStats();
        } else {
            alert('Error: ' + data.message);
        }
    } catch (error) {
        alert('An error occurred');
    }
}

async function loadProfile() {
    try {
        const response = await fetch('/api/substitutes.php?action=profile');
        const data = await response.json();

        if (data.success) {
            document.getElementById('profileName').value = data.profile.name;
            document.getElementById('profileEmail').value = data.profile.email;
            document.getElementById('profileZelle').value = data.profile.zelle_info || '';
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

// Utility functions
function showError(elementId, message) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.classList.add('show');
}

function showSuccess(elementId, message) {
    const el = document.getElementById(elementId);
    el.textContent = message;
    el.classList.add('show');

    // Auto-hide after 3 seconds
    setTimeout(() => {
        el.classList.remove('show');
    }, 3000);
}

function clearMessages() {
    document.querySelectorAll('.form-error, .form-success').forEach(el => {
        el.textContent = '';
        el.classList.remove('show');
    });
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
