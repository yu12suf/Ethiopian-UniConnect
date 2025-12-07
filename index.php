<?php
require_once 'includes/init.php';

$user = new User();
$book = new Book();
$recommendedBooks = new RecommendedBooks();

// Get search filters from query parameters
$filters = [
    'search' => $_GET['search'] ?? '',
    'department' => $_GET['department'] ?? '',
    'exchange_type' => $_GET['exchange_type'] ?? '',
    'condition' => $_GET['condition'] ?? ''
];

// Get all approved books
$books = $book->getBooks($filters);

// Get recommended books with pagination support
$recommendedCategory = $_GET['rec_category'] ?? 'bestsellers';
$recommendedPage = intval($_GET['rec_page'] ?? 0);
$recommended = $recommendedBooks->getRecommendedBooks($recommendedCategory, 8, $recommendedPage * 8);
$categories = $recommendedBooks->getCategories();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniConnect - University Book Exchange Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
</head>

<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="hero-section text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold">UniConnect</h1>
                    <p class="lead">Connect with students across Ethiopian universities to exchange, borrow, and share academic books and materials.</p>
                    <?php if (!$user->isLoggedIn()): ?>
                        <div class="mt-4">
                            <a href="views/auth/register.php" class="btn btn-light btn-lg me-2">Get Started</a>
                            <a href="views/auth/login.php" class="btn btn-outline-light btn-lg">Login</a>
                        </div>
                    <?php else: ?>
                        <div class="mt-4">
                            <a href="views/dashboard/upload.php" class="btn btn-light btn-lg">Upload a Book</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="bi bi-book display-1"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <!-- Search and Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="index.php" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="search" class="form-control" placeholder="Search books..." value="<?= htmlspecialchars($filters['search']) ?>">
                    </div>
                    <div class="col-md-2">
                        <select name="department" class="form-select">
                            <option value="">All Categories</option>
                            <optgroup label="Academic Subjects">
                                <option value="Computer Science" <?= $filters['department'] == 'Computer Science' ? 'selected' : '' ?>>Computer Science</option>
                                <option value="Engineering" <?= $filters['department'] == 'Engineering' ? 'selected' : '' ?>>Engineering</option>
                                <option value="Medicine" <?= $filters['department'] == 'Medicine' ? 'selected' : '' ?>>Medicine</option>
                                <option value="Business" <?= $filters['department'] == 'Business' ? 'selected' : '' ?>>Business</option>
                                <option value="Law" <?= $filters['department'] == 'Law' ? 'selected' : '' ?>>Law</option>
                                <option value="Social Sciences" <?= $filters['department'] == 'Social Sciences' ? 'selected' : '' ?>>Social Sciences</option>
                                <option value="Natural Sciences" <?= $filters['department'] == 'Natural Sciences' ? 'selected' : '' ?>>Natural Sciences</option>
                                <option value="Mathematics" <?= $filters['department'] == 'Mathematics' ? 'selected' : '' ?>>Mathematics</option>
                                <option value="Languages" <?= $filters['department'] == 'Languages' ? 'selected' : '' ?>>Languages</option>
                                <option value="Education" <?= $filters['department'] == 'Education' ? 'selected' : '' ?>>Education</option>
                                <option value="Arts & Humanities" <?= $filters['department'] == 'Arts & Humanities' ? 'selected' : '' ?>>Arts & Humanities</option>
                            </optgroup>
                            <optgroup label="General Interest">
                                <option value="Fiction" <?= $filters['department'] == 'Fiction' ? 'selected' : '' ?>>Fiction</option>
                                <option value="Non-Fiction" <?= $filters['department'] == 'Non-Fiction' ? 'selected' : '' ?>>Non-Fiction</option>
                                <option value="Biography" <?= $filters['department'] == 'Biography' ? 'selected' : '' ?>>Biography</option>
                                <option value="Self-Help" <?= $filters['department'] == 'Self-Help' ? 'selected' : '' ?>>Self-Help</option>
                                <option value="History" <?= $filters['department'] == 'History' ? 'selected' : '' ?>>History</option>
                                <option value="Science" <?= $filters['department'] == 'Science' ? 'selected' : '' ?>>Science</option>
                                <option value="Technology" <?= $filters['department'] == 'Technology' ? 'selected' : '' ?>>Technology</option>
                                <option value="Health & Wellness" <?= $filters['department'] == 'Health & Wellness' ? 'selected' : '' ?>>Health & Wellness</option>
                                <option value="Cooking" <?= $filters['department'] == 'Cooking' ? 'selected' : '' ?>>Cooking</option>
                                <option value="Travel" <?= $filters['department'] == 'Travel' ? 'selected' : '' ?>>Travel</option>
                                <option value="Sports" <?= $filters['department'] == 'Sports' ? 'selected' : '' ?>>Sports</option>
                                <option value="Hobbies" <?= $filters['department'] == 'Hobbies' ? 'selected' : '' ?>>Hobbies</option>
                                <option value="Other" <?= $filters['department'] == 'Other' ? 'selected' : '' ?>>Other</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="exchange_type" class="form-select">
                            <option value="">All Types</option>
                            <option value="borrow" <?= $filters['exchange_type'] == 'borrow' ? 'selected' : '' ?>>Borrow</option>
                            <option value="buy" <?= $filters['exchange_type'] == 'buy' ? 'selected' : '' ?>>Buy</option>
                            <option value="donate" <?= $filters['exchange_type'] == 'donate' ? 'selected' : '' ?>>Donate</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="condition" class="form-select">
                            <option value="">All Conditions</option>
                            <option value="new" <?= $filters['condition'] == 'new' ? 'selected' : '' ?>>New</option>
                            <option value="like_new" <?= $filters['condition'] == 'like_new' ? 'selected' : '' ?>>Like New</option>
                            <option value="good" <?= $filters['condition'] == 'good' ? 'selected' : '' ?>>Good</option>
                            <option value="fair" <?= $filters['condition'] == 'fair' ? 'selected' : '' ?>>Fair</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Books Grid -->
        <div class="available-books-section">
            <h2 class="mb-4">Available Books</h2>

            <?php if (empty($books)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No books found. Try adjusting your search filters.
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($books as $bookItem): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm book-card">
                                <?php if ($bookItem['image_path']): ?>
                                    <img src="<?= htmlspecialchars($bookItem['image_path']) ?>" class="card-img-top" alt="Book Image" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-book text-white" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($bookItem['title']) ?></h5>
                                    <p class="card-text text-muted small">by <?= htmlspecialchars($bookItem['author']) ?></p>

                                    <div class="mb-2">
                                        <span class="badge bg-info"><?= ucfirst($bookItem['exchange_type']) ?></span>
                                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $bookItem['condition_type'])) ?></span>
                                    </div>

                                    <?php if ($bookItem['exchange_type'] == 'buy' && $bookItem['price']): ?>
                                        <p class="text-success fw-bold mb-2"><?= number_format($bookItem['price'], 2) ?> ETB</p>
                                    <?php endif; ?>

                                    <p class="small mb-1"><i class="bi bi-tag"></i> <?= htmlspecialchars($bookItem['department']) ?></p>
                                    <?php if (!empty($bookItem['course'])): ?>
                                        <p class="small mb-3"><i class="bi bi-journal-text"></i> <?= htmlspecialchars($bookItem['course']) ?></p>
                                    <?php endif; ?>

                                    <a href="views/public/book_detail.php?id=<?= $bookItem['id'] ?>" class="btn btn-primary btn-sm w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recommended Books Section -->
        <div class="d-flex justify-content-between align-items-center mb-4 mt-5">
            <h2>Recommended Books</h2>
            <div class="d-flex gap-2">
                <select id="categorySelect" class="form-select form-select-sm" style="width: auto;">
                    <?php foreach ($categories as $key => $name): ?>
                        <option value="<?= $key ?>" <?= $recommendedCategory === $key ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button id="loadMoreBtn" class="btn btn-outline-primary btn-sm">Load More</button>
            </div>
        </div>

        <div id="recommendedBooksContainer">
            <?php if (empty($recommended)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No recommended books available at the moment.
                </div>
            <?php else: ?>
                <div class="row g-4" id="recommendedBooksGrid">
                    <?php foreach ($recommended as $recBook): ?>
                        <div class="col-md-6 col-lg-4 col-xl-3">
                            <div class="card h-100 shadow-sm recommended-book-card">
                                <?php if (isset($recBook['imageLinks']['thumbnail'])): ?>
                                    <img src="<?= htmlspecialchars($recBook['imageLinks']['thumbnail']) ?>" class="card-img-top" alt="Book Image" style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <i class="bi bi-book text-white" style="font-size: 4rem;"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($recBook['title']) ?></h5>
                                    <p class="card-text text-muted small">by <?= htmlspecialchars(implode(', ', $recBook['authors'])) ?></p>

                                    <div class="mb-2">
                                        <span class="badge bg-success">Free Access</span>
                                    </div>

                                    <p class="small mb-3">
                                        <?= htmlspecialchars(substr($recBook['description'], 0, 100)) ?>...
                                    </p>

                                    <div class="d-flex gap-2">
                                        <?php if (!empty($recBook['previewLink'])): ?>
                                            <a href="<?= htmlspecialchars($recBook['previewLink']) ?>" class="btn btn-outline-primary btn-sm" target="_blank">Preview</a>
                                        <?php endif; ?>

                                        <?php if ($recBook['isFree'] && !empty($recBook['downloadLink'])): ?>
                                            <a href="<?= htmlspecialchars($recBook['downloadLink']) ?>" class="btn btn-success btn-sm" target="_blank">Download</a>
                                        <?php endif; ?>

                                        <?php if (!empty($recBook['infoLink'])): ?>
                                            <a href="<?= htmlspecialchars($recBook['infoLink']) ?>" class="btn btn-outline-secondary btn-sm" target="_blank">More Info</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const categorySelect = document.getElementById('categorySelect');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const recommendedBooksContainer = document.getElementById('recommendedBooksContainer');
            let currentPage = <?= $recommendedPage ?>;
            let currentCategory = '<?= $recommendedCategory ?>';

            // Category change handler
            categorySelect.addEventListener('change', function() {
                currentCategory = this.value;
                currentPage = 0;
                loadRecommendedBooks(true);
            });

            // Load more handler
            loadMoreBtn.addEventListener('click', function() {
                currentPage++;
                loadRecommendedBooks(false);
            });

            function loadRecommendedBooks(replace = false) {
                const url = `?rec_category=${currentCategory}&rec_page=${currentPage}`;
                fetch(url)
                    .then(response => response.text())
                    .then(html => {
                        // Extract the recommended books section from the HTML
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const newBooksGrid = doc.getElementById('recommendedBooksGrid');

                        if (newBooksGrid) {
                            const existingGrid = document.getElementById('recommendedBooksGrid');
                            if (replace) {
                                existingGrid.innerHTML = newBooksGrid.innerHTML;
                            } else {
                                // Append new books
                                const newCards = newBooksGrid.querySelectorAll('.col-md-6');
                                newCards.forEach(card => {
                                    existingGrid.appendChild(card.cloneNode(true));
                                });
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error loading recommended books:', error);
                    });
            }
        });
    </script>
</body>

</html>
