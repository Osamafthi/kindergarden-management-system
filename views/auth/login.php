<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kindergarten Management System</title>
    <link rel="stylesheet" href="../../assets/css/auth.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>

        <div id="alert-container"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                Sign In
            </button>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            Signing in...
        </div>

        <div class="footer">
            <p>&copy; 2024 Kindergarten Management System</p>
        </div>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const remember = document.getElementById('remember').checked;
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            const alertContainer = document.getElementById('alert-container');

            // Show loading state
            loginBtn.disabled = true;
            loading.style.display = 'block';
            alertContainer.innerHTML = '';

            try {
                const response = await fetch('/kindergarden/api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        email: email,
                        password: password,
                        remember: remember
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Show success message
                    alertContainer.innerHTML = '<div class="alert alert-success">Login successful! Redirecting...</div>';
                    
                    // Redirect based on user role
                    setTimeout(() => {
                        if (data.user_role === 'admin') {
                            window.location.href = '/kindergarden/views/admin/index.php';
                        } else if (data.user_role === 'teacher') {
                            window.location.href = '/kindergarden/views/teacher/index.php';
                        } else {
                            window.location.href = '/kindergarden/views/admin/index.php';
                        }
                    }, 1000);
                } else {
                    // Show error message
                    alertContainer.innerHTML = `<div class="alert alert-error">${data.message}</div>`;
                }
            } catch (error) {
                console.error('Login error:', error);
                alertContainer.innerHTML = '<div class="alert alert-error">An error occurred. Please try again.</div>';
            } finally {
                // Hide loading state
                loginBtn.disabled = false;
                loading.style.display = 'none';
            }
        });

        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
    <script src="../../assets/js/arabic-converter.js"></script>
</body>
</html>
