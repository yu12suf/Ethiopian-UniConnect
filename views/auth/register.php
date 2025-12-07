<?php
require_once '../../includes/init.php';

$user = new User();

// Redirect if already logged in
if ($user->isLoggedIn()) {
    redirect('/views/dashboard/index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitize($_POST['full_name']),
        'email' => sanitize($_POST['email']),
        'password' => $_POST['password'],
        'confirm_password' => $_POST['confirm_password'],
        'department' => sanitize($_POST['department']),
        'phone' => sanitize($_POST['phone'])
    ];

    // Validation
    if (empty($data['full_name'])) {
        $errors[] = 'Full name is required';
    }
    if (empty($data['email'])) {
        $errors[] = 'Email is required';
    }
    if (empty($data['password'])) {
        $errors[] = 'Password is required';
    } elseif (strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters';
    }
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match';
    }
    if (empty($data['department'])) {
        $errors[] = 'Department is required';
    }

    if (empty($errors)) {
        $result = $user->register($data);
        if ($result['success']) {
            $success = $result['message'];
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
    <title>Register - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h2 class="fw-bold"><i class="bi bi-book-half"></i> UniConnect</h2>
                            <p class="text-muted">Create your account</p>
                        </div>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= htmlspecialchars($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                                <a href="login.php" class="alert-link">Login now</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">University Email (.edu.et)</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                <small class="form-text text-muted">Must be a valid university email ending with .edu.et</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select" required>
                                    <option value="">Select Department</option>
                                    <option value="Computer Science">Computer Science</option>
                                    <option value="Engineering">Engineering</option>
                                    <option value="Medicine">Medicine</option>
                                    <option value="Business">Business</option>
                                    <option value="Law">Law</option>
                                    <option value="Social Sciences">Social Sciences</option>
                                    <option value="Natural Sciences">Natural Sciences</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="0911234567">
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100">Register</button>
                        </form>

                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>