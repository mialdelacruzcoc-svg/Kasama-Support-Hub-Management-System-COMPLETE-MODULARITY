<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Kasama Support Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forgot-password-styles.css">
    <link rel="stylesheet" href="css/dark-mode.css">
    <script src="js/dark-mode.js"></script>
</head>
<body class="forgot-body">
    <div class="forgot-card">
        <h2>Forgot Password?</h2>
        <p>I-enter ang imong email aron makadawat og reset link.</p>
        
        <form action="api/process-forgot-password.php" method="POST">
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" name="email" required placeholder="name.coc@phinmaed.com">
            </div>
            <button type="submit" class="btn-reset">Send Reset Link 📧</button>
        </form>
        
        <a href="index.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>