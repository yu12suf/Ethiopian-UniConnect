<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= site_url('index.php') ?>">
            <i class="bi bi-book-half"></i> UniConnect
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= site_url('index.php') ?>">Browse Books</a>
                </li>

                <?php if ($user->isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/index.php') ?>">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/upload.php') ?>">Upload Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/requests.php') ?>">Requests</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/dashboard/messages.php') ?>">
                            Messages
                            <?php
                            // Use a non-conflicting variable name to avoid overwriting $msg used by message views
                            $messageService = new Message();
                            $unreadCount = $messageService->getUnreadCount($user->getCurrentUserId());
                            if ($unreadCount > 0):
                            ?>
                                <span class="badge bg-danger"><?= $unreadCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php if ($user->isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= site_url('views/admin/index.php') ?>">Admin Panel</a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user_name']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= site_url('views/dashboard/profile.php') ?>">Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="<?= site_url('views/auth/logout.php') ?>">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= site_url('views/auth/login.php') ?>">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-primary text-white ms-2" href="<?= site_url('views/auth/register.php') ?>">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>