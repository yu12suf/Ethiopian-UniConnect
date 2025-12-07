<?php
require_once '../../includes/init.php';
require_once '../../classes/PasswordReset.php';

$errors = [];
$success_message = '';

$token = $_GET['token'] ?? '';
$passwordReset = new PasswordReset();
$is_valid_token = $passwordReset->validateToken($token);

if (!$is_valid_token) {
    $errors[] = 'Invalid or expired password reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_valid_token) {
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];

    if (empty($password) || empty($password_confirm)) {
        $errors[] = 'Please enter and confirm your new password.';
    } else if ($password !== $password_confirm) {
        $errors[] = 'Passwords do not match.';
    } else if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } else {
        $result = $passwordReset->resetPassword($token, $password);
        if ($result['success']) {
            $success_message = $result['message'];
            // Optionally redirect to login page after successful reset
            // redirect('login.php');
        } else {
            $errors[] = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold"><i class="bi bi-book-half"></i> UniConnect</h2>
                            <p class="text-muted">Set Your New Password</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success_message) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($is_valid_token && empty($success_message)): ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="password" class="form-control" required autofocus>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="password_confirm" class="form-control" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            </form>
                        <?php elseif (empty($errors)): // Only show login link if no errors and token is invalid ?>
                            <div class="text-center mt-3">
                                <p>Return to <a href="login.php">Login</a></p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>