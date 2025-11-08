<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$book = new Book();

$bookId = $_GET['id'] ?? 0;
$bookData = $book->getBookById($bookId);

if (!$bookData || $bookData['user_id'] != $user->getCurrentUserId()) {
    redirect('/views/dashboard/index.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitize($_POST['title']),
        'author' => sanitize($_POST['author']),
        'department' => sanitize($_POST['department']),
        'course' => sanitize($_POST['course']),
        'description' => sanitize($_POST['description']),
        'condition' => $_POST['condition'],
        'exchange_type' => $_POST['exchange_type'],
        'price' => $_POST['price'] ?? null
    ];
    
    $result = $book->updateListing($bookId, $user->getCurrentUserId(), $data);
    if ($result['success']) {
        $success = $result['message'];
        $bookData = $book->getBookById($bookId);
    } else {
        $errors[] = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-pencil"></i> Edit Book</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Book Title *</label>
                                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($bookData['title']) ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Author *</label>
                                    <input type="text" name="author" class="form-control" value="<?= htmlspecialchars($bookData['author']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Department *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="Computer Science" <?= $bookData['department'] == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                        <option value="Engineering" <?= $bookData['department'] == 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                        <option value="Medicine" <?= $bookData['department'] == 'Medicine' ? 'selected' : '' ?>>Medicine</option>
                                        <option value="Business" <?= $bookData['department'] == 'Business' ? 'selected' : '' ?>>Business</option>
                                        <option value="Law" <?= $bookData['department'] == 'Law' ? 'selected' : '' ?>>Law</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Course *</label>
                                    <input type="text" name="course" class="form-control" value="<?= htmlspecialchars($bookData['course']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($bookData['description']) ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Condition *</label>
                                    <select name="condition" class="form-select" required>
                                        <option value="new" <?= $bookData['condition_type'] == 'new' ? 'selected' : '' ?>>New</option>
                                        <option value="like_new" <?= $bookData['condition_type'] == 'like_new' ? 'selected' : '' ?>>Like New</option>
                                        <option value="good" <?= $bookData['condition_type'] == 'good' ? 'selected' : '' ?>>Good</option>
                                        <option value="fair" <?= $bookData['condition_type'] == 'fair' ? 'selected' : '' ?>>Fair</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Exchange Type *</label>
                                    <select name="exchange_type" class="form-select" required>
                                        <option value="borrow" <?= $bookData['exchange_type'] == 'borrow' ? 'selected' : '' ?>>Borrow</option>
                                        <option value="buy" <?= $bookData['exchange_type'] == 'buy' ? 'selected' : '' ?>>Buy</option>
                                        <option value="donate" <?= $bookData['exchange_type'] == 'donate' ? 'selected' : '' ?>>Donate</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Price (ETB)</label>
                                    <input type="number" name="price" class="form-control" value="<?= $bookData['price'] ?>" step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Book</button>
                                <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
                            </div>
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
