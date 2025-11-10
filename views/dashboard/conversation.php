<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();

$currentUserId = $user->getCurrentUserId();
$otherUserId = intval($_GET['other_user_id'] ?? 0);
$bookId = isset($_GET['book_id']) ? intval($_GET['book_id']) : null;

if ($otherUserId <= 0) {
    redirect('/views/dashboard/messages.php');
}

// Ensure other user exists and is active
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT id, full_name, status FROM users WHERE id = ?');
$stmt->execute([$otherUserId]);
$other = $stmt->fetch();
if (!$other) {
    redirect('/views/dashboard/messages.php');
}

// Mark messages from other user as read
$message->markAsRead($otherUserId, $currentUserId);

// Handle posting a reply
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = sanitize($_POST['message'] ?? '');
    if (!empty($body)) {
        // replies don't usually have a subject in conversation; pass null
        $res = $message->sendMessage($currentUserId, $otherUserId, $body, $bookId, null);
        if ($res['success']) {
            redirect('/views/dashboard/conversation.php?other_user_id=' . $otherUserId . ($bookId ? '&book_id=' . $bookId : ''));
        } else {
            $error = $res['message'];
        }
    } else {
        $error = 'Message cannot be empty';
    }
}

$conversation = $message->getConversation($currentUserId, $otherUserId, $bookId);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Conversation with <?= htmlspecialchars($other['full_name']) ?> - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <a href="<?= site_url('views/dashboard/messages.php') ?>" class="btn btn-link mb-3">&larr; Back to Messages</a>
        <div class="d-flex align-items-center">
            <h4 class="mb-0">Conversation with <?= htmlspecialchars($other['full_name']) ?></h4>
            <form method="POST" action="<?= site_url('views/dashboard/delete_conversation.php') ?>" class="ms-3">
                <input type="hidden" name="other_user_id" value="<?= intval($otherUserId) ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this conversation for everyone? This is permanent.');">Delete Conversation</button>
            </form>
        </div>

        <div class="card my-3">
            <div class="card-body" style="max-height: 60vh; overflow-y: auto;">
                <?php if (empty($conversation)): ?>
                    <p class="text-muted">No messages yet. Start the conversation below.</p>
                <?php else: ?>
                    <?php foreach ($conversation as $msg): ?>
                        <div class="mb-3 <?= $msg['sender_id'] == $currentUserId ? 'text-end' : 'text-start' ?>">
                            <div class="d-inline-block p-2 rounded position-relative" style="max-width: 80%; background: <?= $msg['sender_id'] == $currentUserId ? '#0d6efd' : '#f1f1f1' ?>; color: <?= $msg['sender_id'] == $currentUserId ? '#fff' : '#000' ?>;">
                                <small class="d-block text-muted mb-1"><?= htmlspecialchars($msg['sender_id'] == $currentUserId ? 'You' : $msg['sender_name']) ?> • <?= timeAgo($msg['created_at']) ?></small>
                                <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                <?php
                                // Load attachments for this message if any. Guard against missing optional tables
                                $atts = [];
                                try {
                                    $tblCheck = $db->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'message_attachments' LIMIT 1");
                                    $tblCheck->execute();
                                    $tblExists = (bool)$tblCheck->fetch();
                                } catch (Exception $e) {
                                    $tblExists = false;
                                }

                                if ($tblExists) {
                                    try {
                                        $attStmt = $db->prepare('SELECT a.* FROM message_attachments ma JOIN attachments a ON ma.attachment_id = a.id WHERE ma.message_id = ?');
                                        $attStmt->execute([intval($msg['id'])]);
                                        $atts = $attStmt->fetchAll();
                                    } catch (Exception $e) {
                                        // If the attachments query fails, don't break the conversation view
                                        error_log('Attachment load failed: ' . $e->getMessage());
                                        $atts = [];
                                    }
                                }

                                if (!empty($atts)):
                                ?>
                                    <div class="mt-2">
                                        <?php foreach ($atts as $a): ?>
                                            <?php if (strpos($a['mime'], 'image/') === 0): ?>
                                                <div class="mb-2"><img src="<?= htmlspecialchars('../../' . $a['path']) ?>" alt="attachment" style="max-width:200px; height:auto;" /></div>
                                            <?php elseif (strpos($a['mime'], 'audio/') === 0): ?>
                                                <div class="mb-2"><audio controls src="<?= htmlspecialchars('../../' . $a['path']) ?>"></audio></div>
                                            <?php elseif (strpos($a['mime'], 'video/') === 0): ?>
                                                <div class="mb-2"><video controls style="max-width:300px;" src="<?= htmlspecialchars('../../' . $a['path']) ?>"></video></div>
                                            <?php else: ?>
                                                <div class="mb-2"><a href="<?= htmlspecialchars('../../' . $a['path']) ?>" target="_blank"><?= htmlspecialchars($a['filename']) ?></a></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($msg['sender_id'] == $currentUserId): ?>
                                    <div class="mt-2">
                                        <a href="<?= site_url('views/dashboard/edit_message.php?message_id=' . intval($msg['id'])) ?>" class="btn btn-sm btn-light" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="<?= site_url('views/dashboard/delete_message.php') ?>" style="display:inline-block;">
                                            <input type="hidden" name="message_id" value="<?= intval($msg['id']) ?>">
                                            <input type="hidden" name="other_user_id" value="<?= intval($otherUserId) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this message?');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" id="replyForm">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="4" placeholder="Write your reply..." required></textarea>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Send Reply</button>
                        <a href="<?= site_url('views/dashboard/messages.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable reply send button after submit to avoid double posts
        (function() {
            const f = document.getElementById('replyForm');
            if (!f) return;
            f.addEventListener('submit', function() {
                const b = f.querySelector('button[type="submit"]');
                if (b) {
                    b.disabled = true;
                    b.innerText = 'Sending...';
                }
            });
        })();
    </script>
</body>

</html>