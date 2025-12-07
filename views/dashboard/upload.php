<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$book = new Book();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'user_id' => $user->getCurrentUserId(),
        'title' => sanitize($_POST['title']),
        'author' => sanitize($_POST['author']),
        'department' => sanitize($_POST['department']),
        'course' => sanitize($_POST['course']),
        'description' => sanitize($_POST['description']),
        'condition' => $_POST['condition'],
        'exchange_type' => $_POST['exchange_type'],
        'price' => $_POST['price'] ?? null,
        'payment_account_1_type' => $_POST['payment_account_1_type'] ?? null,
        'payment_account_1_number' => $_POST['payment_account_1_number'] ?? null,
        'payment_account_1_holder' => $_POST['payment_account_1_holder'] ?? null,
        'payment_account_2_type' => $_POST['payment_account_2_type'] ?? null,
        'payment_account_2_number' => $_POST['payment_account_2_number'] ?? null,
        'payment_account_2_holder' => $_POST['payment_account_2_holder'] ?? null,
        'payment_account_3_type' => $_POST['payment_account_3_type'] ?? null,
        'payment_account_3_number' => $_POST['payment_account_3_number'] ?? null,
        'payment_account_3_holder' => $_POST['payment_account_3_holder'] ?? null
    ];

    if (empty($data['title']) || empty($data['author'])) {
        $errors[] = 'Title and author are required';
    }

    // Validate payment fields for 'buy' exchange type
    if ($data['exchange_type'] === 'buy') {
        $hasAtLeastOneAccount = false;
        for ($i = 1; $i <= 3; $i++) {
            if (!empty($data["payment_account_{$i}_type"]) && !empty($data["payment_account_{$i}_number"])) {
                $hasAtLeastOneAccount = true;
                break;
            }
        }
        if (!$hasAtLeastOneAccount) {
            $errors[] = 'At least one payment account is required for books for sale';
        }
    }

    if (empty($errors)) {
        $image = $_FILES['image'] ?? null;
        $bookFile = $_FILES['book_file'] ?? null;
        $result = $book->createListing($data, $image);
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
    <title>Upload Book - UniConnect</title>
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
                        <h4 class="mb-0"><i class="bi bi-upload"></i> Upload Book</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <?= htmlspecialchars($error) ?><br>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <?= htmlspecialchars($success) ?>
                                <a href="index.php" class="alert-link">Go to dashboard</a>
                            </div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Book Title *</label>
                                    <input type="text" name="title" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Author *</label>
                                    <input type="text" name="author" class="form-control" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="department" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <optgroup label="Academic Subjects">
                                            <option value="Computer Science">Computer Science</option>
                                            <option value="Engineering">Engineering</option>
                                            <option value="Medicine">Medicine</option>
                                            <option value="Business">Business</option>
                                            <option value="Law">Law</option>
                                            <option value="Social Sciences">Social Sciences</option>
                                            <option value="Natural Sciences">Natural Sciences</option>
                                            <option value="Mathematics">Mathematics</option>
                                            <option value="Languages">Languages</option>
                                            <option value="Education">Education</option>
                                            <option value="Arts & Humanities">Arts & Humanities</option>
                                        </optgroup>
                                        <optgroup label="General Interest">
                                            <option value="Fiction">Fiction</option>
                                            <option value="Non-Fiction">Non-Fiction</option>
                                            <option value="Biography">Biography</option>
                                            <option value="Self-Help">Self-Help</option>
                                            <option value="History">History</option>
                                            <option value="Science">Science</option>
                                            <option value="Technology">Technology</option>
                                            <option value="Health & Wellness">Health & Wellness</option>
                                            <option value="Cooking">Cooking</option>
                                            <option value="Travel">Travel</option>
                                            <option value="Sports">Sports</option>
                                            <option value="Hobbies">Hobbies</option>
                                            <option value="Other">Other</option>
                                        </optgroup>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Course/Topic (Optional)</label>
                                    <input type="text" name="course" class="form-control" placeholder="e.g., Data Structures, Novel, Cooking Techniques">
                                    <small class="text-muted">For academic books, specify the course. For general books, you can leave this blank or specify a topic.</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Additional information about the book"></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Condition *</label>
                                    <select name="condition" class="form-select" required>
                                        <option value="new">New</option>
                                        <option value="like_new">Like New</option>
                                        <option value="good" selected>Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Exchange Type *</label>
                                    <select name="exchange_type" class="form-select" id="exchangeType" required>
                                        <option value="borrow">Borrow</option>
                                        <option value="buy">Buy</option>
                                        <option value="donate">Donate</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3" id="priceField" style="display: none;">
                                    <label class="form-label">Price (ETB)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" min="0">
                                </div>
                            </div>

                            <!-- Payment Account Fields (shown only for 'buy' exchange type) -->
                            <div id="paymentFields" style="display: none;">
                                <p class="text-muted small">Provide at least one payment account. Buyers can choose which account to use for payment.</p>

                                <!-- Account 1 -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Account 1</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank *</label>
                                                <select name="payment_account_1_type" class="form-select">
                                                    <option value="">Select Bank</option>
                                                    <option value="CBE">Commercial Bank of Ethiopia (CBE)</option>
                                                    <option value="Dashen">Dashen Bank</option>
                                                    <option value="Awash">Awash International Bank</option>
                                                    <option value="Wegagen">Wegagen Bank</option>
                                                    <option value="United">United Bank</option>
                                                    <option value="Nib">Nib International Bank</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Number *</label>
                                                <input type="text" name="payment_account_1_number" class="form-control" placeholder="Enter account number">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="payment_account_1_holder" class="form-control" placeholder="Account holder name">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account 2 -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Account 2 (Optional)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank</label>
                                                <select name="payment_account_2_type" class="form-select">
                                                    <option value="">Select Bank</option>
                                                    <option value="CBE">Commercial Bank of Ethiopia (CBE)</option>
                                                    <option value="Dashen">Dashen Bank</option>
                                                    <option value="Awash">Awash International Bank</option>
                                                    <option value="Wegagen">Wegagen Bank</option>
                                                    <option value="United">United Bank</option>
                                                    <option value="Nib">Nib International Bank</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="payment_account_2_number" class="form-control" placeholder="Enter account number">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="payment_account_2_holder" class="form-control" placeholder="Account holder name">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Account 3 -->
                                <div class="card mb-3">
                                    <div class="card-header">
                                        <h6 class="mb-0">Payment Account 3 (Optional)</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Bank</label>
                                                <select name="payment_account_3_type" class="form-select">
                                                    <option value="">Select Bank</option>
                                                    <option value="CBE">Commercial Bank of Ethiopia (CBE)</option>
                                                    <option value="Dashen">Dashen Bank</option>
                                                    <option value="Awash">Awash International Bank</option>
                                                    <option value="Wegagen">Wegagen Bank</option>
                                                    <option value="United">United Bank</option>
                                                    <option value="Nib">Nib International Bank</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Number</label>
                                                <input type="text" name="payment_account_3_number" class="form-control" placeholder="Enter account number">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Account Holder Name</label>
                                                <input type="text" name="payment_account_3_holder" class="form-control" placeholder="Account holder name">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <small class="text-muted">Leave account holder name blank if same as your profile name</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Book Image (optional)</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <small class="text-muted">Max size: 5MB. Supported formats: JPG, PNG, GIF</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Book File (PDF/DOC/DOCX) (optional)</label>
                                <input type="file" name="book_file" class="form-control" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                                <small class="text-muted">Max size: 10MB. Supported formats: PDF, DOC, DOCX</small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Upload Book</button>
                                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('exchangeType').addEventListener('change', function() {
            const priceField = document.getElementById('priceField');
            const paymentFields = document.getElementById('paymentFields');
            if (this.value === 'buy') {
                priceField.style.display = 'block';
                paymentFields.style.display = 'block';
            } else {
                priceField.style.display = 'none';
                paymentFields.style.display = 'none';
            }
        });
    </script>
</body>

</html>