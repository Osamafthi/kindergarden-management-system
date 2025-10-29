<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام إدارة الروضة</title>
    <link rel="stylesheet" href="../../assets/css/auth.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>مرحباً بعودتك</h1>
            <p>قم بتسجيل الدخول إلى حسابك</p>
        </div>

        <div id="alert-container"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">تذكرني</label>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                تسجيل الدخول
            </button>
        </form>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            جاري تسجيل الدخول...
        </div>

        <div class="footer">
            <p>&copy; 2024 نظام إدارة الروضة</p>
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
                    alertContainer.innerHTML = '<div class="alert alert-success">تم تسجيل الدخول بنجاح! جاري التوجيه...</div>';
                    
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
                console.error('خطأ في تسجيل الدخول:', error);
                alertContainer.innerHTML = '<div class="alert alert-error">حدث خطأ. يرجى المحاولة مرة أخرى.</div>';
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
