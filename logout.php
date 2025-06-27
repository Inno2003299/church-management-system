<?php
session_start();
require_once 'config/db.php';

// Remove session token from database
if (isset($_SESSION['session_token'])) {
    try {
        $stmt = $conn->prepare("DELETE FROM admin_sessions WHERE session_token = ?");
        $stmt->bind_param('s', $_SESSION['session_token']);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        error_log('Error removing session token: ' . $e->getMessage());
    }
}

// Destroy session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Church Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .logout-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .logout-icon {
            color: #28a745;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
    <div class="logout-card">
        <i class="bi bi-check-circle logout-icon"></i>
        <h3 class="mb-3">Successfully Logged Out</h3>
        <p class="text-muted mb-4">You have been safely logged out of the Church Management System.</p>
        
        <div class="d-grid gap-2">
            <a href="login.php" class="btn btn-primary btn-login">
                <i class="bi bi-box-arrow-in-right me-2"></i>
                Login Again
            </a>
            <a href="index.html" class="btn btn-outline-secondary">
                <i class="bi bi-house me-2"></i>
                Go to Homepage
            </a>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                <i class="bi bi-shield-check me-1"></i>
                Your session has been securely terminated
            </small>
        </div>
    </div>

    <script>
        // Auto-redirect after 5 seconds
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 5000);
    </script>
</body>
</html>
