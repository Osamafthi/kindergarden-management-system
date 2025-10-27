<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout - Kindergarten Management System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logout-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .logout-header h1 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .logout-message {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .logout-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-right: 1rem;
        }

        .logout-btn:hover {
            transform: translateY(-2px);
        }

        .cancel-btn {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .cancel-btn:hover {
            transform: translateY(-2px);
        }

        .loading {
            display: none;
            margin-top: 1rem;
        }

        .spinner {
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 0.5rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-container">
        <div class="logout-header">
            <h1>Logout</h1>
        </div>
        
        <div class="logout-message">
            Are you sure you want to logout?
        </div>
        
        <button class="logout-btn" id="logoutBtn">Yes, Logout</button>
        <button class="cancel-btn" id="cancelBtn">Cancel</button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            Logging out...
        </div>
    </div>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', async function() {
            const logoutBtn = document.getElementById('logoutBtn');
            const cancelBtn = document.getElementById('cancelBtn');
            const loading = document.getElementById('loading');

            // Show loading state
            logoutBtn.disabled = true;
            cancelBtn.disabled = true;
            loading.style.display = 'block';

            try {
                const response = await fetch('/kindergarden/api/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to login page
                    setTimeout(() => {
                        window.location.href = '/kindergarden/views/auth/login.php';
                    }, 1000);
                } else {
                    alert('Logout failed: ' + data.message);
                    // Reset button states
                    logoutBtn.disabled = false;
                    cancelBtn.disabled = false;
                    loading.style.display = 'none';
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('An error occurred during logout');
                // Reset button states
                logoutBtn.disabled = false;
                cancelBtn.disabled = false;
                loading.style.display = 'none';
            }
        });

        document.getElementById('cancelBtn').addEventListener('click', function() {
            // Go back to previous page or admin dashboard
            if (document.referrer) {
                window.history.back();
            } else {
                window.location.href = '/kindergarden/views/admin/index.php';
            }
        });
    </script>
</body>
</html>
