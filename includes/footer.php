<footer class="bg-dark text-white mt-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5><i class="bi bi-book-half"></i> UniConnect</h5>
                <p class="small">Connecting Ethiopian university students for book and material exchange.</p>
            </div>
            <div class="col-md-4">
                <h6>Quick Links</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= site_url('index.php') ?>" class="text-white-50 text-decoration-none">Browse Books</a></li>
                    <?php if (!empty($user) && $user->isLoggedIn()): ?>
                        <li><a href="<?= site_url('views/dashboard/index.php') ?>" class="text-white-50 text-decoration-none">Dashboard</a></li>
                        <li><a href="<?= site_url('views/auth/logout.php') ?>" class="text-white-50 text-decoration-none">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?= site_url('views/auth/register.php') ?>" class="text-white-50 text-decoration-none">Register</a></li>
                        <li><a href="<?= site_url('views/auth/login.php') ?>" class="text-white-50 text-decoration-none">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h6>Contact</h6>
                <p class="small text-white-50">
                    <i class="bi bi-envelope"></i> admin@uniconnect.edu.et<br>
                    <i class="bi bi-phone"></i> +251 911 234 567
                </p>
            </div>
        </div>
        <hr class="bg-white">
        <div class="text-center">
            <p class="small mb-0">&copy; <?= date('Y') ?> UniConnect. All rights reserved.</p>
        </div>
    </div>
</footer>