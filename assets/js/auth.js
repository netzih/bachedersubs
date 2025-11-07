// Authentication JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    const showRegisterLink = document.getElementById('showRegister');
    const showLoginLink = document.getElementById('showLogin');
    const loginFormElement = document.getElementById('loginFormElement');
    const registerFormElement = document.getElementById('registerFormElement');

    // Toggle between login and register forms
    showRegisterLink.addEventListener('click', function(e) {
        e.preventDefault();
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        clearErrors();
    });

    showLoginLink.addEventListener('click', function(e) {
        e.preventDefault();
        registerForm.style.display = 'none';
        loginForm.style.display = 'block';
        clearErrors();
    });

    // Handle login
    loginFormElement.addEventListener('submit', async function(e) {
        e.preventDefault();
        clearErrors();

        const email = document.getElementById('loginEmail').value;
        const password = document.getElementById('loginPassword').value;

        try {
            const response = await fetch('/api/auth.php?action=login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ email, password })
            });

            const data = await response.json();

            if (data.success) {
                // Redirect based on role
                if (data.user.role === 'admin') {
                    window.location.href = '/admin/dashboard.php';
                } else {
                    window.location.href = '/substitute/dashboard.php';
                }
            } else {
                showError('loginError', data.message || 'Login failed');
            }
        } catch (error) {
            showError('loginError', 'An error occurred. Please try again.');
        }
    });

    // Handle registration
    registerFormElement.addEventListener('submit', async function(e) {
        e.preventDefault();
        clearErrors();

        const name = document.getElementById('registerName').value;
        const email = document.getElementById('registerEmail').value;
        const password = document.getElementById('registerPassword').value;
        const zelle_info = document.getElementById('registerZelle').value;

        try {
            const response = await fetch('/api/auth.php?action=register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ name, email, password, zelle_info })
            });

            const data = await response.json();

            if (data.success) {
                alert('Registration successful! Please login.');
                registerForm.style.display = 'none';
                loginForm.style.display = 'block';
                registerFormElement.reset();
            } else {
                showError('registerError', data.message || 'Registration failed');
            }
        } catch (error) {
            showError('registerError', 'An error occurred. Please try again.');
        }
    });

    function showError(elementId, message) {
        const errorEl = document.getElementById(elementId);
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }

    function clearErrors() {
        document.querySelectorAll('.form-error').forEach(el => {
            el.textContent = '';
            el.classList.remove('show');
        });
    }
});
