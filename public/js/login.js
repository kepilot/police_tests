// Login page functionality
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName).classList.add('active');
    
    // Add active class to clicked tab
    event.target.classList.add('active');
}

function showResult(message, isSuccess) {
    const resultDiv = document.getElementById('result');
    resultDiv.textContent = message;
    resultDiv.className = 'result ' + (isSuccess ? 'success' : 'error');
    resultDiv.style.display = 'block';
}

// Cache busting utility function
function addCacheBuster(url) {
    const separator = url.includes('?') ? '&' : '?';
    return `${url}${separator}v=${Date.now()}`;
}

// Clear browser cache and reload with fresh files
function clearCacheAndRedirect(url) {
    // Clear localStorage cache if needed
    const cacheKeys = Object.keys(localStorage).filter(key => key.startsWith('cache_'));
    cacheKeys.forEach(key => localStorage.removeItem(key));
    
    // Add cache buster to the URL
    const urlWithCacheBuster = addCacheBuster(url);
    
    // Use window.location.replace to avoid adding to browser history
    window.location.replace(urlWithCacheBuster);
}

// Initialize event listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Login form submission
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;
        
        try {
            const response = await fetch('/auth/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showResult('Login successful! Redirecting to dashboard...', true);
                localStorage.setItem('token', data.data.token);
                // Store login timestamp for cache busting
                localStorage.setItem('lastLoginTime', Date.now().toString());
                
                // Redirect to dashboard after 1 second
                setTimeout(() => {
                    window.location.href = '/dashboard.html';
                }, 1000);
            } else {
                showResult('Login failed: ' + data.message, false);
            }
        } catch (error) {
            showResult('Error: ' + error.message, false);
        }
    });

    // Registration form submission
    document.getElementById('registerForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        
        try {
            const response = await fetch('/auth/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ name, email, password })
            });
            
            const data = await response.json();
            
            if (data.success) {
                showResult('Registration successful! User ID: ' + data.data.id, true);
            } else {
                showResult('Registration failed: ' + data.message, false);
            }
        } catch (error) {
            showResult('Error: ' + error.message, false);
        }
    });
}); 