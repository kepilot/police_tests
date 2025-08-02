// Admin page functionality
// Global variables
let currentTab = 'dashboard';
let editingId = null;

// Initialize the admin dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is authenticated
    const token = localStorage.getItem('token');
    if (!token) {
        console.error('No authentication token found');
        return;
    }
    
    // Load data with authentication
    loadTopics();
    loadExams();
    loadQuestions();
    loadAssignments();
});

// Tab navigation
function showTab(tabName) {
    // Hide all content
    document.querySelectorAll('.content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected content and activate tab
    document.getElementById(tabName).classList.remove('hidden');
    event.target.classList.add('active');
    currentTab = tabName;
}

// Dashboard functions
async function loadDashboard() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/learning/stats', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            const topicsByLevel = stats.topics_by_level || {};
            const totalActiveTopics = (topicsByLevel.beginner || 0) + 
                                    (topicsByLevel.intermediate || 0) + 
                                    (topicsByLevel.advanced || 0) + 
                                    (topicsByLevel.expert || 0);
            
            document.getElementById('stats-grid').innerHTML = `
                <div class="stat-card">
                    <h3>${stats.total_topics || 0}</h3>
                    <p>Total Topics</p>
                </div>
                <div class="stat-card">
                    <h3>${stats.total_exams || 0}</h3>
                    <p>Total Exams</p>
                </div>
                <div class="stat-card">
                    <h3>${stats.total_users || 0}</h3>
                    <p>Total Users</p>
                </div>
                <div class="stat-card">
                    <h3>${totalActiveTopics}</h3>
                    <p>Active Topics</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
        document.getElementById('stats-grid').innerHTML = '<div class="alert alert-error">Error loading dashboard data</div>';
    }
}

// Topic functions
async function loadTopics() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/topics', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const topics = data.data;
            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Level</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            topics.forEach(topic => {
                html += `
                    <tr>
                        <td>${topic.title}</td>
                        <td><span class="badge badge-${topic.level}">${topic.level_display}</span></td>
                        <td>${topic.description.substring(0, 100)}${topic.description.length > 100 ? '...' : ''}</td>
                        <td>
                            <button class="btn btn-warning" onclick="editTopic('${topic.id}')">Edit</button>
                            <button class="btn btn-danger" onclick="deleteTopic('${topic.id}')">Delete</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            document.getElementById('topics-list').innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading topics:', error);
        document.getElementById('topics-list').innerHTML = '<div class="alert alert-error">Error loading topics</div>';
    }
}

function showCreateTopicForm() {
    document.getElementById('topic-form').classList.remove('hidden');
    document.getElementById('topic-form-title').textContent = 'Create New Topic';
    document.getElementById('topicForm').reset();
    editingId = null;
}

function cancelTopicForm() {
    document.getElementById('topic-form').classList.add('hidden');
    editingId = null;
}

async function saveTopic(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const topicData = {
        title: formData.get('title'),
        description: formData.get('description'),
        level: formData.get('level')
    };

    try {
        const url = editingId ? `/topics/${editingId}` : '/topics';
        const method = editingId ? 'PUT' : 'POST';
        
        const token = localStorage.getItem('token');
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(topicData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Topic saved successfully!', 'success');
            cancelTopicForm();
            loadTopics();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error saving topic:', error);
        showAlert('Error saving topic', 'error');
    }
}

async function editTopic(topicId) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/topics/${topicId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const topic = data.data;
            document.getElementById('topicId').value = topic.id;
            document.getElementById('topicTitle').value = topic.title;
            document.getElementById('topicDescription').value = topic.description;
            document.getElementById('topicLevel').value = topic.level;
            
            document.getElementById('topic-form').classList.remove('hidden');
            document.getElementById('topic-form-title').textContent = 'Edit Topic';
            editingId = topicId;
        } else {
            showAlert(data.message || 'Error loading topic', 'error');
        }
    } catch (error) {
        console.error('Error loading topic:', error);
        showAlert('Error loading topic', 'error');
    }
}

async function deleteTopic(topicId) {
    if (!confirm('Are you sure you want to delete this topic?')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/topics/${topicId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            showAlert('Topic deleted successfully!', 'success');
            loadTopics();
        } else {
            const data = await response.json();
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error deleting topic:', error);
        showAlert('Error deleting topic', 'error');
    }
}

// Question functions
async function loadQuestions() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/questions', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const questions = data.data;
            
            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Type</th>
                            <th>Points</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            questions.forEach(question => {
                html += `
                    <tr>
                        <td>${question.text.substring(0, 100)}${question.text.length > 100 ? '...' : ''}</td>
                        <td>${question.type_display}</td>
                        <td>${question.points}</td>
                        <td>
                            <button class="btn btn-warning" onclick="editQuestion('${question.id}')">Edit</button>
                            <button class="btn btn-danger" onclick="deleteQuestion('${question.id}')">Delete</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            document.getElementById('questions-list').innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading questions:', error);
        document.getElementById('questions-list').innerHTML = '<div class="alert alert-error">Error loading questions</div>';
    }
}

function showCreateQuestionForm() {
    document.getElementById('question-form').classList.remove('hidden');
    document.getElementById('question-form-title').textContent = 'Create New Question';
    document.getElementById('questionForm').reset();
    editingId = null;
    loadExamsForSelect();
    loadTopicsForSelect();
}

function cancelQuestionForm() {
    document.getElementById('question-form').classList.add('hidden');
    editingId = null;
}

function toggleOptions() {
    const type = document.getElementById('questionType').value;
    const optionsContainer = document.getElementById('options-container');
    
    if (type === 'true_false') {
        optionsContainer.classList.add('hidden');
    } else {
        optionsContainer.classList.remove('hidden');
    }
}

function addOption() {
    const optionsList = document.getElementById('options-list');
    const optionCount = optionsList.children.length + 1;
    
    const optionDiv = document.createElement('div');
    optionDiv.className = 'form-group';
    optionDiv.innerHTML = `
        <label>Option ${optionCount}</label>
        <input type="text" name="options[]" required>
    `;
    
    optionsList.appendChild(optionDiv);
}

async function saveQuestion(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const questionData = {
        text: formData.get('text'),
        type: formData.get('type'),
        exam_id: formData.get('exam_id'),
        points: parseInt(formData.get('points')),
        correct_option: parseInt(formData.get('correct_option')),
        options: formData.getAll('options'),
        topic_ids: formData.getAll('topic_ids[]')
    };

    try {
        const url = editingId ? `/questions/${editingId}` : '/questions';
        const method = editingId ? 'PUT' : 'POST';
        
        const token = localStorage.getItem('token');
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(questionData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Question saved successfully!', 'success');
            cancelQuestionForm();
            loadQuestions();
        } else {
            showAlert(data.message, 'error');
        }
    } catch (error) {
        console.error('Error saving question:', error);
        showAlert('Error saving question', 'error');
    }
}

async function loadExamsForSelect() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/exams', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('questionExam');
            select.innerHTML = '<option value="">Select Exam</option>';
            
            data.data.forEach(exam => {
                const option = document.createElement('option');
                option.value = exam.id;
                option.textContent = exam.title;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading exams:', error);
    }
}

async function editQuestion(questionId) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/questions/${questionId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const question = data.data;
            document.getElementById('questionId').value = question.id;
            document.getElementById('questionText').value = question.text;
            document.getElementById('questionType').value = question.type;
            document.getElementById('questionExam').value = question.exam_id;
            document.getElementById('questionPoints').value = question.points;
            document.getElementById('questionCorrectOption').value = question.correct_option;
            
            // Load options
            const optionsList = document.getElementById('options-list');
            optionsList.innerHTML = '';
            question.options.forEach((option, index) => {
                const optionDiv = document.createElement('div');
                optionDiv.className = 'form-group';
                optionDiv.innerHTML = `
                    <label>Option ${index + 1}</label>
                    <input type="text" name="options[]" value="${option}" required>
                `;
                optionsList.appendChild(optionDiv);
            });
            
            // Load topics
            await loadTopicsForSelect();
            // Set checkboxes for associated topics
            question.topic_ids.forEach(topicId => {
                const checkbox = document.getElementById(`form_topic_${topicId}`);
                if (checkbox) checkbox.checked = true;
            });
            
            document.getElementById('question-form').classList.remove('hidden');
            document.getElementById('question-form-title').textContent = 'Edit Question';
            editingId = questionId;
            toggleOptions();
        } else {
            showAlert(data.message || 'Error loading question', 'error');
        }
    } catch (error) {
        console.error('Error loading question:', error);
        showAlert('Error loading question', 'error');
    }
}

async function deleteQuestion(questionId) {
    if (!confirm('Are you sure you want to delete this question?')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/questions/${questionId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Question deleted successfully!', 'success');
            loadQuestions();
        } else {
            showAlert(data.message || 'Error deleting question', 'error');
        }
    } catch (error) {
        console.error('Error deleting question:', error);
        showAlert('Error deleting question', 'error');
    }
}

// Exam functions
async function loadExams() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/exams', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const exams = data.data;
            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Duration</th>
                            <th>Passing Score</th>
                            <th>Topic</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            exams.forEach(exam => {
                html += `
                    <tr>
                        <td>${exam.title}</td>
                        <td>${exam.duration_display}</td>
                        <td>${exam.passing_score_display}</td>
                        <td>${exam.topic_id}</td>
                        <td>
                            <button class="btn btn-warning" onclick="editExam('${exam.id}')">Edit</button>
                            <button class="btn btn-danger" onclick="deleteExam('${exam.id}')">Delete</button>
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            document.getElementById('exams-list').innerHTML = html;
        }
    } catch (error) {
        console.error('Error loading exams:', error);
        document.getElementById('exams-list').innerHTML = '<div class="alert alert-error">Error loading exams</div>';
    }
}

function showCreateExamForm() {
    document.getElementById('exam-form').classList.remove('hidden');
    document.getElementById('exam-form-title').textContent = 'Create New Exam';
    document.getElementById('examForm').reset();
    editingId = null;
    loadTopicsForExamSelect();
}

function cancelExamForm() {
    document.getElementById('exam-form').classList.add('hidden');
    editingId = null;
}

async function saveExam(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const examData = {
        title: formData.get('title'),
        description: formData.get('description'),
        duration_minutes: parseInt(formData.get('duration_minutes')),
        passing_score_percentage: parseInt(formData.get('passing_score_percentage')),
        topic_id: formData.get('topic_id')
    };

    try {
        const url = editingId ? `/exams/${editingId}` : '/exams';
        const method = editingId ? 'PUT' : 'POST';
        
        const token = localStorage.getItem('token');
        const response = await fetch(url, {
            method: method,
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(examData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Exam saved successfully!', 'success');
            cancelExamForm();
            loadExams();
        } else {
            showAlert(data.message || 'Error saving exam', 'error');
        }
    } catch (error) {
        console.error('Error saving exam:', error);
        showAlert('Error saving exam', 'error');
    }
}

async function loadTopicsForExamSelect() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/topics', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('examTopic');
            select.innerHTML = '<option value="">Select Topic</option>';
            
            data.data.forEach(topic => {
                const option = document.createElement('option');
                option.value = topic.id;
                option.textContent = `${topic.title} (${topic.level_display})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading topics:', error);
    }
}

async function editExam(examId) {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/exams/${examId}`, {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const exam = data.data;
            document.getElementById('examId').value = exam.id;
            document.getElementById('examTitle').value = exam.title;
            document.getElementById('examDescription').value = exam.description;
            document.getElementById('examDuration').value = exam.duration_minutes;
            document.getElementById('examPassingScore').value = exam.passing_score_percentage;
            document.getElementById('examTopic').value = exam.topic_id;
            
            document.getElementById('exam-form').classList.remove('hidden');
            document.getElementById('exam-form-title').textContent = 'Edit Exam';
            editingId = examId;
        } else {
            showAlert(data.message || 'Error loading exam', 'error');
        }
    } catch (error) {
        console.error('Error loading exam:', error);
        showAlert('Error loading exam', 'error');
    }
}

async function deleteExam(examId) {
    if (!confirm('Are you sure you want to delete this exam?')) {
        return;
    }
    
    try {
        const token = localStorage.getItem('token');
        const response = await fetch(`/exams/${examId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Exam deleted successfully!', 'success');
            loadExams();
        } else {
            showAlert(data.message || 'Error deleting exam', 'error');
        }
    } catch (error) {
        console.error('Error deleting exam:', error);
        showAlert('Error deleting exam', 'error');
    }
}

async function loadTopicsForSelect() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/topics', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('questionTopicsCheckboxes');
            container.innerHTML = '';
            
            data.data.forEach(topic => {
                const checkboxDiv = document.createElement('div');
                checkboxDiv.className = 'topic-checkbox';
                checkboxDiv.innerHTML = `
                    <input type="checkbox" 
                           id="form_topic_${topic.id}" 
                           name="topic_ids[]" 
                           value="${topic.id}">
                    <label for="form_topic_${topic.id}">${topic.title} (${topic.level_display})</label>
                `;
                container.appendChild(checkboxDiv);
            });
        }
    } catch (error) {
        console.error('Error loading topics:', error);
    }
}

// Assignment functions
async function loadAssignments() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/exam-assignments', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        console.log(data);
        console.log("rada");
        
        if (data.success) {
            let html = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Exam</th>
                            <th>Assigned By</th>
                            <th>Assigned Date</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.data.forEach(assignment => {
                const status = assignment.is_completed ? 'Completed' : 
                              assignment.is_overdue ? 'Overdue' : 'Pending';
                const statusClass = assignment.is_completed ? 'completed' : 
                                   assignment.is_overdue ? 'overdue' : 'pending';
                
                html += `
                    <tr>
                        <td>${assignment.user_name || assignment.user_id}</td>
                        <td>${assignment.exam_title || assignment.exam_id}</td>
                        <td>${assignment.assigned_by_name || assignment.assigned_by}</td>
                        <td>${new Date(assignment.assigned_at).toLocaleDateString()}</td>
                        <td>${assignment.due_date ? new Date(assignment.due_date).toLocaleDateString() : 'No due date'}</td>
                        <td><span class="status-${statusClass}">${status}</span></td>
                        <td>
                            ${!assignment.is_completed ? 
                                `<button class="btn btn-warning" onclick="markAssignmentComplete('${assignment.id}')">Mark Complete</button>` : 
                                '<span class="completed">✓ Completed</span>'
                            }
                        </td>
                    </tr>
                `;
            });
            
            html += '</tbody></table>';
            document.getElementById('assignments-list').innerHTML = html;
        } else {
            document.getElementById('assignments-list').innerHTML = '<div class="alert alert-error">Error loading assignments: ' + (data.message || 'Unknown error') + '</div>';
        }
    } catch (error) {
        console.error('Error loading assignments:', error);
        document.getElementById('assignments-list').innerHTML = '<div class="alert alert-error">Error loading assignments: ' + error.message + '</div>';
    }
}

function showAssignExamForm() {
    document.getElementById('assignment-form').classList.remove('hidden');
    document.getElementById('assignment-form-title').textContent = 'Assign Exam to User';
    document.getElementById('assignmentForm').reset();
    loadUsersForAssignment();
    loadExamsForAssignment();
    loadAdminsForAssignment();
}

function cancelAssignmentForm() {
    document.getElementById('assignment-form').classList.add('hidden');
}

async function saveAssignment(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const assignmentData = {
        userId: formData.get('userId'),
        examId: formData.get('examId'),
        assignedBy: formData.get('assignedBy'),
        dueDate: formData.get('dueDate') || null
    };

    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/exam-assignments', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(assignmentData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert('Exam assigned successfully!', 'success');
            cancelAssignmentForm();
            loadAssignments();
        } else {
            showAlert(data.message || 'Error assigning exam', 'error');
        }
    } catch (error) {
        console.error('Error assigning exam:', error);
        showAlert('Error assigning exam', 'error');
    }
}

async function loadUsersForAssignment() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/users', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('assignmentUserId');
            select.innerHTML = '<option value="">Select Student</option>';
            
            // Filter for students (users with role 'user')
            data.data.forEach(user => {
                if (user.role === 'user' && user.is_active) {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.email})`;
                    select.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

async function loadExamsForAssignment() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/exams', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('assignmentExamId');
            select.innerHTML = '<option value="">Select Exam</option>';
            
            data.data.forEach(exam => {
                const option = document.createElement('option');
                option.value = exam.id;
                option.textContent = exam.title;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading exams:', error);
    }
}

async function loadAdminsForAssignment() {
    try {
        const token = localStorage.getItem('token');
        const response = await fetch('/users', {
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('assignmentAssignedBy');
            select.innerHTML = '<option value="">Select Admin User</option>';
            
            // Filter for admin users (users with role 'admin' or 'superadmin')
            data.data.forEach(user => {
                if ((user.role === 'admin' || user.role === 'superadmin') && user.is_active) {
                    const option = document.createElement('option');
                    option.value = user.id;
                    option.textContent = `${user.name} (${user.email})`;
                    select.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error('Error loading admin users:', error);
    }
}

async function markAssignmentComplete(assignmentId) {
    if (confirm('Are you sure you want to mark this assignment as complete?')) {
        try {
            const token = localStorage.getItem('token');
            const response = await fetch(`/exam-assignments/${assignmentId}/complete`, {
                method: 'PUT',
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Content-Type': 'application/json'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                showAlert('Assignment marked as complete!', 'success');
                loadAssignments();
            } else {
                showAlert(data.message || 'Error marking assignment complete', 'error');
            }
        } catch (error) {
            console.error('Error marking assignment complete:', error);
            showAlert('Error marking assignment complete', 'error');
        }
    }
}

// Utility functions
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
} 