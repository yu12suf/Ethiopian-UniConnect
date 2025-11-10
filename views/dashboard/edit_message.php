<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$messageClass = new Message();
$currentUserId = $user->getCurrentUserId();

$messageId = intval($_GET['message_id'] ?? ($_POST['message_id'] ?? 0));
if ($messageId <= 0) {
    redirect('/views/dashboard/messages.php');
}

// If POST, perform update
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newMessage = trim($_POST['message'] ?? '');
    if ($newMessage === '') {
        $errors[] = 'Message cannot be empty';
    } else {
        $res = $messageClass->editMessage($messageId, $currentUserId, $newMessage);
        if ($res['success']) {
            redirect('/views/dashboard/conversation.php?other_user_id=' . intval($_POST['other_user_id'] ?? 0));
        } else {
            $errors[] = $res['message'] ?? 'Failed to edit message';
        }
    }
}

// Load existing message to prefill
$db = Database::getInstance()->getConnection();
$st = $db->prepare('SELECT id, sender_id, receiver_id, message, created_at FROM messages WHERE id = ?');
$st->execute([$messageId]);
$msg = $st->fetch();
if (!$msg) {
    // message not found — redirect back
    redirect('/views/dashboard/messages.php');
}

// Determine ownership; instead of redirecting silently, show the form but disable editing if not owner
$isOwner = ($msg['sender_id'] == $currentUserId);
$otherUserId = ($msg['sender_id'] == $currentUserId) ? $msg['receiver_id'] : $msg['sender_id'];
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Edit Message</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <h3>Edit Message</h3>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
        <?php endif; ?>

        <?php if (!$isOwner): ?>
            <div class="alert alert-warning">You may only edit messages that you sent. This message is read-only.</div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="message_id" value="<?= $msg['id'] ?>">
            <input type="hidden" name="other_user_id" value="<?= intval($otherUserId) ?>">
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="6" <?= $isOwner ? '' : 'disabled' ?>><?= htmlspecialchars($msg['message']) ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <?php if ($isOwner): ?>
                    <button class="btn btn-primary" type="submit">Save changes</button>
                <?php else: ?>
                    <button class="btn btn-primary" type="button" disabled>Save changes</button>
                <?php endif; ?>
                <a class="btn btn-link" href="<?= site_url('views/dashboard/conversation.php?other_user_id=' . $otherUserId) ?>">Cancel</a>
            </div>
        </form>
    </div>

    <?php include '../../includes/footer.php'; ?>
</body>

</html>