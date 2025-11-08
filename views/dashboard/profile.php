<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$userId = $user->getCurrentUserId();
$userData = $user->getUserById($userId);

$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitize($_POST['full_name']),
        'department' => sanitize($_POST['department']),
        'phone' => sanitize($_POST['phone'])
    ];
    
    if ($user->updateProfile($userId, $data)) {
        $success = 'Profile updated successfully';
        $userData = $user->getUserById($userId);
    } else {
        $errors[] = 'Failed to update profile';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-person-circle"></i> My Profile</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($userData['full_name']) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= htmlspecialchars($userData['email']) ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select" required>
                                    <option value="Computer Science" <?= $userData['department'] == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                    <option value="Engineering" <?= $userData['department'] == 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                    <option value="Medicine" <?= $userData['department'] == 'Medicine' ? 'selected' : '' ?>>Medicine</option>
                                    <option value="Business" <?= $userData['department'] == 'Business' ? 'selected' : '' ?>>Business</option>
                                    <option value="Law" <?= $userData['department'] == 'Law' ? 'selected' : '' ?>>Law</option>
                                    <option value="Social Sciences" <?= $userData['department'] == 'Social Sciences' ? 'selected' : '' ?>>Social Sciences</option>
                                    <option value="Natural Sciences" <?= $userData['department'] == 'Natural Sciences' ? 'selected' : '' ?>>Natural Sciences</option>
                                </select>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($userData['phone']) ?>">
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
