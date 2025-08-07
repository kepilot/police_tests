// Dashboard page functionality
let currentUserId = null;

// Cache busting utility function
function addCacheBuster(url) {
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}v=${Date.now()}`;
}

// Force refresh with cache busting
function forceRefresh() {
    const currentUrl = window.location.href.split('?')[0]; // Remove existing query params
    window.location.href = addCacheBuster(currentUrl);
}

// Check if user is authenticated
function checkAuth() {
    const token = localStorage.getItem('token');
    if (!token) {
        window.location.href = '/login.html';
        return;
    }
    loadUserInfo();
    loadAssignments();
}

function loadUserInfo() {
    const token = localStorage.getItem('token');
    if (!token) return;

    try {
        const payload = JSON.parse(atob(token.split('.')[1]));
        console.log('Token payload:', payload);
        document.getElementById('welcomeMessage').textContent = `Welcome, ${payload.name}!`;
        document.getElementById('lastLogin').textContent = `Last login: ${new Date().toLocaleString()}`;
        currentUserId = payload.sub;
        
        // Check if user is admin and show/hide admin button accordingly
        const adminButton = document.querySelector('.admin-btn');
        if (adminButton) {
            console.log('Admin button found, checking role:', payload.role);
            if (payload.role === 'admin' || payload.role === 'superadmin') {
                adminButton.style.display = 'inline-block';
                console.log('Admin button shown');
            } else {
                adminButton.style.display = 'none';
                console.log('Admin button hidden');
            }
        } else {
            console.log('Admin button not found in DOM');
        }
    } catch (e) {
        console.error('Error parsing token:', e);
    }
}

async function loadAssignments() {
    if (!currentUserId) {
        document.getElementById('assignmentsContainer').innerHTML = 
            '<div class="no-assignments">Unable to load assignments - user ID not found</div>';
        return;
    }

    try {
        const response = await fetch(`/exam-assignments/user/${currentUserId}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            displayAssignments(data.data);
        } else {
            document.getElementById('assignmentsContainer').innerHTML = 
                `<div class="no-assignments">Error loading assignments: ${data.message}</div>`;
        }
    } catch (error) {
        console.error('Error loading assignments:', error);
        document.getElementById('assignmentsContainer').innerHTML = 
            '<div class="no-assignments">Error loading assignments. Please try again.</div>';
    }
}

function displayAssignments(assignments) {
    const container = document.getElementById('assignmentsContainer');
    
    if (!assignments || assignments.length === 0) {
        container.innerHTML = '<div class="no-assignments">No exam assignments found. Check back later!</div>';
        return;
    }

    container.innerHTML = '';
    
    assignments.forEach(assignment => {
        const card = document.createElement('div');
        card.className = 'exam-card';
        
        const status = getAssignmentStatus(assignment);
        const dueDateText = assignment.due_date ? 
            `Due: ${new Date(assignment.due_date).toLocaleDateString()}` : 
            'No due date';
        
        card.innerHTML = `
            <div class="exam-info">
                <div class="exam-title">Exam ID: ${assignment.exam_id}</div>
                <div class="exam-details">
                    Assigned: ${new Date(assignment.assigned_at).toLocaleDateString()} | 
                    ${dueDateText}
                </div>
            </div>
            <div class="exam-status status-${status.toLowerCase()}">${status}</div>
            <div>
                ${assignment.is_completed ? 
                    '<span style="color: #28a745;">✓ Completed</span>' :
                    `<button class="btn btn-primary" onclick="takeExam('${assignment.exam_id}', '${assignment.id}')">Take Exam</button>`
                }
            </div>
        `;
        
        container.appendChild(card);
    });
}

function getAssignmentStatus(assignment) {
    if (assignment.is_completed) {
        return 'COMPLETED';
    } else if (assignment.is_overdue) {
        return 'OVERDUE';
    } else {
        return 'PENDING';
    }
}

function takeExam(examId, assignmentId) {
    if (!currentUserId) {
        alert('User ID not found. Please log in again.');
        return;
    }
    
    console.log('Taking exam:', { examId, userId: currentUserId, assignmentId });
    
    // Redirect to exam page with parameters
    const examUrl = `/exam.html?examId=${examId}&userId=${currentUserId}&assignmentId=${assignmentId}`;
    console.log('Redirecting to:', examUrl);
    window.location.href = examUrl;
}

function refreshAssignments() {
    loadAssignments();
}

// Enhanced refresh function with cache busting option
function refreshWithCacheBusting() {
    if (confirm('This will refresh the page and clear any cached data. Continue?')) {
        forceRefresh();
    }
}

function showTokenInfo() {
    const token = localStorage.getItem('token');
    if (token) {
        document.getElementById('tokenText').textContent = token;
        document.getElementById('tokenInfo').style.display = 'block';
    }
}

function logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('lastLoginTime');
    window.location.href = '/login.html';
}

function goToAdmin() {
    window.location.href = '/admin.html';
}

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
    
    // Add keyboard shortcut for force refresh (Ctrl+Shift+R)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.shiftKey && e.key === 'R') {
            e.preventDefault();
            forceRefresh();
        }
    });
}); 