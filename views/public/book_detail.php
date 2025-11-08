<?php
require_once '../../includes/init.php';

$user = new User();
$book = new Book();
$request = new Request();

$bookId = $_GET['id'] ?? 0;
$bookData = $book->getBookById($bookId);

if (!$bookData || $bookData['status'] !== 'approved') {
    redirect('/index.php');
}

$errors = [];
$success = '';

// Handle request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user->isLoggedIn()) {
    $message = sanitize($_POST['message']);
    $result = $request->createRequest($user->getCurrentUserId(), $bookId, $message);
    
    if ($result['success']) {
        $success = $result['message'];
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
    <title><?= htmlspecialchars($bookData['title']) ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="container my-5">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($bookData['title']) ?></li>
            </ol>
        </nav>
        
        <div class="row">
            <div class="col-md-4">
                <?php if ($bookData['image_path']): ?>
                    <img src="/<?= htmlspecialchars($bookData['image_path']) ?>" class="img-fluid rounded shadow" alt="Book Image">
                <?php else: ?>
                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center rounded" style="height: 400px;">
                        <i class="bi bi-book" style="font-size: 6rem;"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-8">
                <h2 class="fw-bold"><?= htmlspecialchars($bookData['title']) ?></h2>
                <p class="text-muted lead">by <?= htmlspecialchars($bookData['author']) ?></p>
                
                <div class="mb-3">
                    <span class="badge bg-info me-2"><?= ucfirst($bookData['exchange_type']) ?></span>
                    <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $bookData['condition_type'])) ?></span>
                    <span class="badge bg-success"><?= ucfirst($bookData['availability']) ?></span>
                </div>
                
                <?php if ($bookData['exchange_type'] == 'buy' && $bookData['price']): ?>
                    <h3 class="text-success mb-3"><?= number_format($bookData['price'], 2) ?> ETB</h3>
                <?php endif; ?>
                
                <div class="card mb-3">
                    <div class="card-body">
                        <h5>Book Details</h5>
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td><strong>Department:</strong></td>
                                <td><?= htmlspecialchars($bookData['department']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Course:</strong></td>
                                <td><?= htmlspecialchars($bookData['course']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Condition:</strong></td>
                                <td><?= ucfirst(str_replace('_', ' ', $bookData['condition_type'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Posted:</strong></td>
                                <td><?= timeAgo($bookData['created_at']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($bookData['description']): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Description</h5>
                            <p><?= nl2br(htmlspecialchars($bookData['description'])) ?></p>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-body">
                        <h5>Contact Information</h5>
                        <p class="mb-1"><i class="bi bi-person-circle"></i> <strong><?= htmlspecialchars($bookData['owner_name']) ?></strong></p>
                        <?php if ($user->isLoggedIn() && $user->getCurrentUserId() != $bookData['user_id']): ?>
                            <p class="mb-1"><i class="bi bi-envelope"></i> <?= htmlspecialchars($bookData['owner_email']) ?></p>
                            <?php if ($bookData['owner_phone']): ?>
                                <p class="mb-0"><i class="bi bi-phone"></i> <?= htmlspecialchars($bookData['owner_phone']) ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted small">Login to see contact details</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!$user->isLoggedIn()): ?>
                    <div class="alert alert-info mt-3">
                        Please <a href="/views/auth/login.php">login</a> to send a request for this book.
                    </div>
                <?php elseif ($user->getCurrentUserId() == $bookData['user_id']): ?>
                    <div class="alert alert-warning mt-3">
                        This is your own listing.
                    </div>
                <?php else: ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success mt-3"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger mt-3">
                            <?php foreach ($errors as $error): ?>
                                <?= htmlspecialchars($error) ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="mt-3">
                        <div class="mb-3">
                            <label class="form-label">Send a request message:</label>
                            <textarea name="message" class="form-control" rows="3" placeholder="Introduce yourself and explain why you need this book" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-send"></i> Send Request
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
