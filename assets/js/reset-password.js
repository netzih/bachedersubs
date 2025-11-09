// Reset Password Page JavaScript

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const token = urlParams.get('token');

    const loadingState = document.getElementById('loadingState');
    const invalidTokenState = document.getElementById('invalidTokenState');
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    const successState = document.getElementById('successState');
    const resetPasswordFormElement = document.getElementById('resetPasswordFormElement');

    // Validate token on page load
    if (!token) {
        showInvalidToken('No reset token provided');
        return;
    }

    try {
        const response = await fetch(`api/reset-password.php?token=${encodeURIComponent(token)}`);
        const data = await response.json();

        if (data.success) {
            // Token is valid, show reset form
            loadingState.style.display = 'none';
            resetPasswordForm.style.display = 'block';
            document.getElementById('userEmail').textContent = data.email;
        } else {
            showInvalidToken(data.message);
        }
    } catch (error) {
        showInvalidToken('Failed to validate reset link');
    }

    // Handle password reset form submission
    resetPasswordFormElement.addEventListener('submit', async (e) => {
        e.preventDefault();
        clearErrors();

        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        // Client-side validation
        if (newPassword !== confirmPassword) {
            showError('Passwords do not match');
            return;
        }

        if (newPassword.length < 6) {
            showError('Password must be at least 6 characters long');
            return;
        }

        // Disable submit button
        const submitBtn = document.getElementById('resetPasswordBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Resetting...';

        try {
            const response = await fetch('api/reset-password.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    token: token,
                    password: newPassword
                })
            });

            const data = await response.json();

            if (data.success) {
                // Show success state
                resetPasswordForm.style.display = 'none';
                successState.style.display = 'block';
            } else {
                showError(data.message || 'Failed to reset password');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Reset Password';
            }
        } catch (error) {
            showError('An error occurred. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Reset Password';
        }
    });

    function showInvalidToken(message) {
        loadingState.style.display = 'none';
        invalidTokenState.style.display = 'block';
        document.getElementById('invalidTokenMessage').textContent = message;
    }

    function showError(message) {
        const errorEl = document.getElementById('resetPasswordError');
        errorEl.textContent = message;
        errorEl.classList.add('show');
    }

    function clearErrors() {
        const errorEl = document.getElementById('resetPasswordError');
        errorEl.textContent = '';
        errorEl.classList.remove('show');
    }
});
