// Exam page functionality
let examData = null;
let currentAttemptId = null;
let timeRemaining = 0;
let timer = null;
let answers = {};

// Get exam ID from URL parameters
const urlParams = new URLSearchParams(window.location.search);
const examId = urlParams.get('examId');
const userId = urlParams.get('userId');
const assignmentId = urlParams.get('assignmentId');

// Initialize exam when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Exam page loaded with params:', { examId, userId, assignmentId });
    
    if (!examId || !userId) {
        showError('Missing exam ID or user ID. Please access this page from your dashboard.');
    } else {
        console.log('Starting exam...');
        startExam();
    }
});

async function startExam() {
    try {
        const token = localStorage.getItem('token');
        if (!token) {
            showError('Authentication required. Please log in again.');
            return;
        }

        const response = await fetch('/api/learning/start-exam-attempt', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                userId: userId,
                examId: examId
            })
        });

        const result = await response.json();

        if (result.success) {
            examData = result.data;
            currentAttemptId = examData.attempt_id;
            timeRemaining = examData.time_limit; // Already in seconds
            
            displayExam();
            startTimer();
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to start exam: ' + error.message);
    }
}

function displayExam() {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('examInterface').classList.remove('hidden');

    // Set exam info
    document.getElementById('examTitle').textContent = examData.exam.title;
    document.getElementById('examTitleInfo').textContent = examData.exam.title;
    document.getElementById('examDescription').textContent = examData.exam.description;
    document.getElementById('examDuration').textContent = examData.exam.duration_minutes;
    document.getElementById('passingScore').textContent = examData.exam.passing_score_percentage;

    // Display questions
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';

    examData.questions.forEach((question, index) => {
        const questionDiv = document.createElement('div');
        questionDiv.className = 'question';
        questionDiv.innerHTML = `
            <h3>Question ${index + 1}</h3>
            <p>${question.text}</p>
            <div class="options">
                ${generateOptions(question, index)}
            </div>
        `;
        container.appendChild(questionDiv);
    });

    updateProgress();
}

function generateOptions(question, questionIndex) {
    if (question.type === 'true_false') {
        return question.options.map((option, optionIndex) => `
            <label class="option">
                <input type="radio" name="question_${question.id}" value="${optionIndex}" 
                       onchange="saveAnswer('${question.id}', ${optionIndex})">
                ${option}
            </label>
        `).join('');
    } else {
        return question.options.map((option, optionIndex) => `
            <label class="option">
                <input type="radio" name="question_${question.id}" value="${optionIndex}" 
                       onchange="saveAnswer('${question.id}', ${optionIndex})">
                ${option}
            </label>
        `).join('');
    }
}

function saveAnswer(questionId, answer) {
    answers[questionId] = answer;
    updateProgress();
}

function updateProgress() {
    const totalQuestions = examData.questions.length;
    const answeredQuestions = Object.keys(answers).length;
    const progress = (answeredQuestions / totalQuestions) * 100;
    
    document.getElementById('progressBar').style.width = progress + '%';
}

function startTimer() {
    updateTimerDisplay();
    timer = setInterval(() => {
        timeRemaining--;
        updateTimerDisplay();
        
        if (timeRemaining <= 0) {
            clearInterval(timer);
            alert('Time is up! Submitting your exam automatically.');
            submitExam();
        }
    }, 1000);
}

function updateTimerDisplay() {
    const minutes = Math.floor(timeRemaining / 60);
    const seconds = timeRemaining % 60;
    document.getElementById('timeRemaining').textContent = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
}

async function submitExam() {
    if (confirm('Are you sure you want to submit your exam? You cannot change your answers after submission.')) {
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                showError('Authentication required. Please log in again.');
                return;
            }

            const response = await fetch('/api/learning/submit-exam-attempt', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({
                    attemptId: currentAttemptId,
                    answers: answers
                })
            });

            const result = await response.json();

            if (result.success) {
                clearInterval(timer);
                
                // Mark assignment as completed if assignmentId is provided
                if (assignmentId) {
                    await markAssignmentAsCompleted(assignmentId);
                }
                
                showResults(result.data);
            } else {
                showError(result.message);
            }
        } catch (error) {
            showError('Failed to submit exam: ' + error.message);
        }
    }
}

async function markAssignmentAsCompleted(assignmentId) {
    try {
        const response = await fetch(`/exam-assignments/${assignmentId}/complete`, {
            method: 'PUT',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();
        if (!result.success) {
            console.warn('Failed to mark assignment as completed:', result.message);
        }
    } catch (error) {
        console.warn('Error marking assignment as completed:', error);
    }
}

function showResults(results) {
    document.getElementById('examInterface').classList.add('hidden');
    document.getElementById('resultsContainer').classList.remove('hidden');

    const resultsContent = document.getElementById('resultsContent');
    const score = results.score;
    
    resultsContent.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <h3 style="color: ${score.passed ? '#28a745' : '#dc3545'};">
                ${score.passed ? '🎉 Congratulations! You passed!' : '❌ Sorry, you did not pass.'}
            </h3>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4>Score</h4>
                <p style="font-size: 24px; font-weight: bold; color: #667eea;">
                    ${score.earned}/${score.total}
                </p>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4>Percentage</h4>
                <p style="font-size: 24px; font-weight: bold; color: #667eea;">
                    ${score.percentage}%
                </p>
            </div>
            <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                <h4>Passing Threshold</h4>
                <p style="font-size: 24px; font-weight: bold; color: #667eea;">
                    ${score.passing_threshold}%
                </p>
            </div>
        </div>
        <p><strong>Completed:</strong> ${results.completion_time}</p>
        <p><strong>Duration:</strong> ${results.duration_minutes} minutes</p>
    `;
}

function saveProgress() {
    // In a real application, you would save progress to the server
    alert('Progress saved! (This is a demo - in a real app, progress would be saved to the server)');
}

function confirmExit() {
    if (confirm('Are you sure you want to exit? Your progress will be lost.')) {
        window.location.href = 'dashboard.html';
    }
}

function showError(message) {
    document.getElementById('loadingState').classList.add('hidden');
    document.getElementById('examInterface').classList.add('hidden');
    document.getElementById('errorState').classList.remove('hidden');
    document.getElementById('errorMessage').textContent = message;
}

// Prevent accidental navigation
window.addEventListener('beforeunload', function(e) {
    if (examData && !document.getElementById('resultsContainer').classList.contains('hidden')) {
        return;
    }
    e.preventDefault();
    e.returnValue = 'Are you sure you want to leave? Your exam progress will be lost.';
}); 