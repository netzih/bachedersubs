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

    // Add substitute
    const addSubBtn = document.getElementById('addSubstituteBtn');
    if (addSubBtn) {
        addSubBtn.addEventListener('click', function() {
            console.log('Add substitute button clicked');
            showModal('addSubstituteModal');
        });
    } else {
        console.error('addSubstituteBtn not found');
    }

    document.getElementById('addSubstituteForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const name = document.getElementById('substituteName').value;
        const email = document.getElementById('substituteEmail').value;
        const password = document.getElementById('substitutePassword').value;
        const zelleInfo = document.getElementById('substituteZelle').value;
        const hourlyRate = document.getElementById('substituteRate').value;

        try {
            const response = await fetch('/api/substitutes.php?action=create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    name,
                    email,
                    password,
                    zelle_info: zelleInfo,
                    hourly_rate: hourlyRate
                })
            });

            const data = await response.json();
            if (data.success) {
                hideModal('addSubstituteModal');
                this.reset();
                loadSubstitutes();
                alert('Substitute added successfully!');
            } else {
                showError('addSubstituteError', data.message);
            }
        } catch (error) {
            showError('addSubstituteError', 'An error occurred');
        }
    });

    // Manually log hours (admin)
    const addTimeEntryBtn = document.getElementById('addTimeEntryBtn');
    if (addTimeEntryBtn) {
        addTimeEntryBtn.addEventListener('click', function() {
            console.log('Add time entry button clicked');
            loadSubstitutesForEntry();
            loadTeachersForEntry();
            showModal('addTimeEntryModal');
            // Calculate hours for default times
            setTimeout(() => calculateAdminEntryHours(), 100);
        });
    } else {
        console.error('addTimeEntryBtn not found');
    }

    // Time calculation for admin entry form
    const entryStartTime = document.getElementById('entryStartTime');
    const entryEndTime = document.getElementById('entryEndTime');
    if (entryStartTime && entryEndTime) {
        entryStartTime.addEventListener('change', calculateAdminEntryHours);
        entryEndTime.addEventListener('change', calculateAdminEntryHours);
    }

    document.getElementById('addTimeEntryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const substituteId = document.getElementById('entrySubstitute').value;
        const teacherId = document.getElementById('entryTeacher').value;
        const workDate = document.getElementById('entryDate').value;
        const startTime = document.getElementById('entryStartTime').value;
        const endTime = document.getElementById('entryEndTime').value;
        const notes = document.getElementById('entryNotes').value;

        try {
            const response = await fetch('/api/time_entries.php?action=admin_create', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    substitute_id: substituteId,
                    teacher_id: teacherId,
                    work_date: workDate,
                    start_time: startTime,
                    end_time: endTime,
                    notes: notes
                })
            });

            const data = await response.json();
            if (data.success) {
                hideModal('addTimeEntryModal');
                this.reset();
                document.getElementById('entryCalculatedHours').textContent = 'Select start and end times';
                loadTimeEntries();
                loadStats();
                alert('Hours logged successfully!');
            } else {
                showError('addTimeEntryError', data.message);
            }
        } catch (error) {
            showError('addTimeEntryError', 'An error occurred');
        }
    });

    // Generate report
    document.getElementById('generateReport').addEventListener('click', generateReport);

    // Edit time entry (admin)
    const editEntryStartTime = document.getElementById('editEntryStartTime');
    const editEntryEndTime = document.getElementById('editEntryEndTime');
    if (editEntryStartTime && editEntryEndTime) {
        editEntryStartTime.addEventListener('change', calculateEditEntryHours);
        editEntryEndTime.addEventListener('change', calculateEditEntryHours);
    }

    document.getElementById('editTimeEntryForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const entryId = document.getElementById('editEntryId').value;
        const teacherId = document.getElementById('editEntryTeacher').value;
        const workDate = document.getElementById('editEntryDate').value;
        const startTime = document.getElementById('editEntryStartTime').value;
        const endTime = document.getElementById('editEntryEndTime').value;
        const notes = document.getElementById('editEntryNotes').value;

        try {
            const response = await fetch('/api/time_entries.php?action=update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    entry_id: entryId,
                    teacher_id: teacherId,
                    work_date: workDate,
                    start_time: startTime,
                    end_time: endTime,
                    notes: notes
                })
            });

            const data = await response.json();
            if (data.success) {
                hideModal('editTimeEntryModal');
                loadTimeEntries();
                loadStats();
                alert('Entry updated successfully!');
            } else {
                showError('editTimeEntryError', data.message);
            }
        } catch (error) {
            showError('editTimeEntryError', 'An error occurred');
        }
    });

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
            document.getElementById('unpaidHours').textContent = parseFloat(stats.unpaid_hours || 0).toFixed(1);

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
                <button class="btn btn-sm btn-primary" onclick="editTimeEntry(${entry.id}, '${escapeHtml(entry.substitute_name)}', ${entry.teacher_id}, '${entry.work_date}', '${entry.start_time}', '${entry.end_time}', '${escapeHtml(entry.notes || '')}')">Edit</button>
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
            <td><span class="clickable-name" onclick="viewSubstituteDetails(${sub.id})">${escapeHtml(sub.name)}</span></td>
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
        loadSubstituteOwed(sub.id, sub.name);
    });
}

async function loadSubstituteOwed(substituteId, substituteName) {
    try {
        // Get all unpaid entries and filter by substitute name
        const response = await fetch(`/api/time_entries.php?action=list&is_paid=false`);
        const data = await response.json();

        if (data.success) {
            // Filter entries for this specific substitute
            const subEntries = data.entries.filter(e => e.substitute_name === substituteName);

            // Calculate total owed for this substitute
            let owed = 0;
            subEntries.forEach(entry => {
                owed += parseFloat(entry.amount || 0);
            });

            const cell = document.getElementById(`owed-${substituteId}`);
            if (cell) {
                cell.textContent = formatCurrency(owed);
            }
        }
    } catch (error) {
        console.error('Error loading owed amount:', error);
        const cell = document.getElementById(`owed-${substituteId}`);
        if (cell) {
            cell.textContent = '$0.00';
        }
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

async function loadSubstitutesForEntry() {
    try {
        const response = await fetch('/api/substitutes.php?action=list');
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('entrySubstitute');
            select.innerHTML = '<option value="">-- Select Substitute --</option>';
            data.substitutes.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.id;
                option.textContent = sub.name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading substitutes:', error);
    }
}

async function loadTeachersForEntry() {
    try {
        const response = await fetch('/api/teachers.php?action=list');
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('entryTeacher');
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

function calculateAdminEntryHours() {
    const startTime = document.getElementById('entryStartTime').value;
    const endTime = document.getElementById('entryEndTime').value;
    const display = document.getElementById('entryCalculatedHours');

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

async function viewSubstituteDetails(substituteId) {
    try {
        // Load substitute basic info
        const subResponse = await fetch('/api/substitutes.php?action=list');
        const subData = await subResponse.json();

        if (!subData.success) {
            alert('Failed to load substitute details');
            return;
        }

        const substitute = subData.substitutes.find(s => s.id === substituteId);
        if (!substitute) {
            alert('Substitute not found');
            return;
        }

        // Load time entries for this substitute
        const entriesResponse = await fetch(`/api/time_entries.php?action=list`);
        const entriesData = await entriesResponse.json();

        if (!entriesData.success) {
            alert('Failed to load time entries');
            return;
        }

        // Filter entries for this substitute
        const substituteEntries = entriesData.entries.filter(e => e.substitute_name === substitute.name);

        // Calculate stats
        const totalHours = substituteEntries.reduce((sum, e) => sum + parseFloat(e.hours), 0);
        const unpaidEntries = substituteEntries.filter(e => !e.is_paid);
        const paidEntries = substituteEntries.filter(e => e.is_paid);
        const unpaidHours = unpaidEntries.reduce((sum, e) => sum + parseFloat(e.hours), 0);
        const amountOwed = unpaidEntries.reduce((sum, e) => sum + parseFloat(e.amount), 0);
        const amountPaid = paidEntries.reduce((sum, e) => sum + parseFloat(e.amount), 0);

        // Populate modal
        document.getElementById('subDetailsName').textContent = substitute.name;
        document.getElementById('subDetailsEmail').textContent = substitute.email;
        document.getElementById('subDetailsZelle').textContent = substitute.zelle_info || 'Not provided';
        document.getElementById('subDetailsRate').textContent = formatCurrency(substitute.hourly_rate);
        document.getElementById('subDetailsTotalHours').textContent = totalHours.toFixed(1);
        document.getElementById('subDetailsUnpaidHours').textContent = unpaidHours.toFixed(1);
        document.getElementById('subDetailsOwed').textContent = formatCurrency(amountOwed);
        document.getElementById('subDetailsPaid').textContent = formatCurrency(amountPaid);

        // Populate recent entries table (last 10 entries)
        const recentEntries = substituteEntries.slice(0, 10);
        const tbody = document.getElementById('subDetailsEntriesBody');
        tbody.innerHTML = '';

        if (recentEntries.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align: center;">No entries found</td></tr>';
        } else {
            recentEntries.forEach(entry => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${formatDate(entry.work_date)}</td>
                    <td>${escapeHtml(entry.teacher_name)}</td>
                    <td>${entry.hours}</td>
                    <td>${formatCurrency(entry.amount)}</td>
                    <td>
                        <span class="badge ${entry.is_paid ? 'badge-paid' : 'badge-unpaid'}">
                            ${entry.is_paid ? 'Paid' : 'Unpaid'}
                        </span>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Show modal
        showModal('substituteDetailsModal');

    } catch (error) {
        console.error('Error loading substitute details:', error);
        alert('An error occurred while loading substitute details');
    }
}

async function editTimeEntry(entryId, substituteName, teacherId, workDate, startTime, endTime, notes) {
    // Strip seconds from time values if present (HH:MM:SS -> HH:MM)
    startTime = startTime.substring(0, 5);
    endTime = endTime.substring(0, 5);

    // Populate the edit form
    document.getElementById('editEntryId').value = entryId;
    document.getElementById('editEntrySubstitute').value = substituteName;
    document.getElementById('editEntryDate').value = workDate;
    document.getElementById('editEntryStartTime').value = startTime;
    document.getElementById('editEntryEndTime').value = endTime;
    document.getElementById('editEntryNotes').value = notes || '';

    // Load teachers for dropdown
    try {
        const response = await fetch('/api/teachers.php?action=list');
        const data = await response.json();

        if (data.success) {
            const select = document.getElementById('editEntryTeacher');
            select.innerHTML = '<option value="">-- Select Teacher --</option>';
            data.teachers.forEach(teacher => {
                const option = document.createElement('option');
                option.value = teacher.id;
                option.textContent = teacher.name;
                if (teacher.id == teacherId) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading teachers:', error);
    }

    // Calculate hours and show modal
    setTimeout(() => calculateEditEntryHours(), 100);
    showModal('editTimeEntryModal');
}

function calculateEditEntryHours() {
    const startTime = document.getElementById('editEntryStartTime').value;
    const endTime = document.getElementById('editEntryEndTime').value;
    const display = document.getElementById('editEntryCalculatedHours');

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

// Utility functions
function showModal(modalId) {
    console.log('showModal called with:', modalId);
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        console.log('Modal shown:', modalId);
    } else {
        console.error('Modal not found:', modalId);
    }
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
