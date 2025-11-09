<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Bay Area Cheder</title>
    <link rel="icon" type="image/jpeg" href="assets/images/favicon.jpg">
    <link rel="apple-touch-icon" href="assets/images/favicon.jpg">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="assets/images/logo.jpeg" alt="Bay Area Cheder Logo" class="logo">
                <h2>Reset Your Password</h2>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="auth-form">
                <p style="text-align: center; color: var(--text-secondary);">
                    Validating reset link...
                </p>
            </div>

            <!-- Invalid Token State -->
            <div id="invalidTokenState" class="auth-form" style="display: none;">
                <div class="form-error show" style="margin-bottom: 20px;">
                    <strong>Invalid or Expired Link</strong><br>
                    <span id="invalidTokenMessage">This password reset link is invalid or has expired.</span>
                </div>
                <p style="text-align: center; color: var(--text-secondary);">
                    You can request a new password reset link from the login page.
                </p>
                <a href="index.html" class="btn btn-primary btn-block">Back to Login</a>
            </div>

            <!-- Reset Password Form -->
            <div id="resetPasswordForm" class="auth-form" style="display: none;">
                <p style="color: var(--text-secondary); font-size: 14px; margin-bottom: 20px;">
                    Enter your new password for <strong id="userEmail"></strong>
                </p>
                <form id="resetPasswordFormElement">
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <input type="password" id="newPassword" name="password" required minlength="6" autofocus>
                        <small>Minimum 6 characters</small>
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirm_password" required minlength="6">
                    </div>
                    <div class="form-error" id="resetPasswordError"></div>
                    <button type="submit" class="btn btn-primary btn-block" id="resetPasswordBtn">Reset Password</button>
                </form>
            </div>

            <!-- Success State -->
            <div id="successState" class="auth-form" style="display: none;">
                <div class="form-success show" style="margin-bottom: 20px;">
                    <strong>✓ Password Reset Successful!</strong><br>
                    Your password has been updated successfully.
                </div>
                <p style="text-align: center; color: var(--text-secondary); margin-bottom: 20px;">
                    You can now log in with your new password.
                </p>
                <a href="index.html" class="btn btn-primary btn-block">Go to Login</a>
            </div>
        </div>
    </div>

    <script src="assets/js/reset-password.js"></script>
</body>
</html>
