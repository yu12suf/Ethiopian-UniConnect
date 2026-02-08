<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();

$userId = $user->getCurrentUserId();
$conversations = $message->getUserConversations($userId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0"><i class="bi bi-chat-dots"></i> Messages</h2>
            <div class="ms-auto">
                <a href="<?= site_url('views/dashboard/compose_message.php') ?>" class="btn btn-primary">New Message</a>
            </div>
        </div>

        <!-- New Message button only (inline compose removed to avoid duplicate sends) -->

        <?php if (empty($conversations)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No messages yet. Start by sending a request for a book!
            </div>
        <?php else: ?>
            <div class="card shadow">
                <div class="list-group list-group-flush">
                    <?php foreach ($conversations as $conv): ?>
                        <?php
                        $otherId = $conv['other_user_id'];
                        $convUrl = site_url("views/dashboard/conversation.php?other_user_id={$otherId}");
                        $replyUrl = site_url("views/dashboard/conversation.php?other_user_id={$otherId}");
                        ?>
                        <a href="<?= $convUrl ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1"><i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($conv['other_user_name']) ?></h6>
                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?= timeAgo($conv['last_message_time']) ?></small>
                            </div>
                            <?php if ($conv['book_title']): ?>
                                <p class="mb-1 small text-muted"><i class="bi bi-book me-1"></i>Re: <?= htmlspecialchars($conv['book_title']) ?></p>
                            <?php endif; ?>
                            <p class="mb-0 text-truncate"><i class="bi bi-chat-text me-1"></i><?= htmlspecialchars($conv['last_message']) ?></p>
                        </a>
                        <div class="px-3 py-2 border-bottom">
                            <a href="<?= $replyUrl ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-reply me-1"></i>Reply</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>