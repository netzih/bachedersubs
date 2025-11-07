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

    // Time calculation on change
    document.getElementById('startTime').addEventListener('change', calculateHours);
    document.getElementById('endTime').addEventListener('change', calculateHours);

    // Calculate initial hours with default values
    calculateHours();

    // Log hours form
    document.getElementById('logHoursForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        clearMessages();

        const teacherId = document.getElementById('teacherSelect').value;
        const workDate = document.getElementById('workDate').value;
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        const notes = document.getElementById('workNotes').value;

        if (!startTime || !endTime) {
            showError('logHoursError', 'Please enter both start and end times');
            return;
        }

        try {
            const response = await fetch('/api/time_entries.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    teacher_id: teacherId,
                    work_date: workDate,
                    start_time: startTime,
                    end_time: endTime,
                    notes: notes
                })
            });

            const data = await response.json();

            if (data.success) {
                showSuccess('logHoursSuccess', 'Hours logged successfully!');
                this.reset();
                setTodayDate();
                document.getElementById('calculatedHours').textContent = 'Select start and end times';
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
            document.getElementById('totalOwed').textContent = parseFloat(stats.unpaid_hours || 0).toFixed(1);
            document.getElementById('totalPaid').textContent = parseFloat(stats.paid_hours || 0).toFixed(1);
            document.getElementById('totalHours').textContent = parseFloat(stats.total_hours || 0).toFixed(1);
            document.getElementById('totalEntries').textContent = stats.total_entries || 0;
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
        tbody.innerHTML = '<tr><td colspan="8" style="text-align: center;">No entries found</td></tr>';
        return;
    }

    entries.forEach(entry => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${formatDate(entry.work_date)}</td>
            <td>${escapeHtml(entry.teacher_name)}</td>
            <td>${formatTime(entry.start_time)}</td>
            <td>${formatTime(entry.end_time)}</td>
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

function formatTime(timeStr) {
    if (!timeStr) return '-';
    // Convert 24-hour time to 12-hour format
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const hour12 = hour % 12 || 12;
    return `${hour12}:${minutes} ${ampm}`;
}

function calculateHours() {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    const display = document.getElementById('calculatedHours');

    if (!startTime || !endTime) {
        display.textContent = 'Select start and end times';
        return;
    }

    // Parse times
    const [startHour, startMin] = startTime.split(':').map(Number);
    const [endHour, endMin] = endTime.split(':').map(Number);

    // Convert to minutes since midnight
    let startMinutes = startHour * 60 + startMin;
    let endMinutes = endHour * 60 + endMin;

    // Handle overnight shifts
    if (endMinutes < startMinutes) {
        endMinutes += 24 * 60; // Add 24 hours
    }

    // Calculate difference in minutes
    const diffMinutes = endMinutes - startMinutes;

    if (diffMinutes <= 0) {
        display.textContent = 'End time must be after start time';
        display.style.color = '#dc2626';
        return;
    }

    // Convert to hours
    const hours = Math.floor(diffMinutes / 60);
    const minutes = diffMinutes % 60;

    // Display
    let text = '';
    if (hours > 0) {
        text += `${hours} hour${hours !== 1 ? 's' : ''}`;
    }
    if (minutes > 0) {
        if (hours > 0) text += ' ';
        text += `${minutes} minute${minutes !== 1 ? 's' : ''}`;
    }

    const decimal = (diffMinutes / 60).toFixed(2);
    text += ` (${decimal} hours)`;

    display.textContent = text;
    display.style.color = '#0369a1';
}
