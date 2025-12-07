<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();
$book = new Book();

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
    $attachBookId = !empty($_POST['book_id']) ? intval($_POST['book_id']) : null;

    // Handle file attachments
    $attachments = [];
    if (!empty($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $attachment = new Attachment();
        $attId = $attachment->storeUpload($_FILES['attachment'], $currentUserId);
        if ($attId) {
            $attachments[] = $attId;
        }
    }

    if (!empty($body) || $attachBookId || !empty($attachments)) {
        // replies don't usually have a subject in conversation; pass null
        $res = $message->sendMessage($currentUserId, $otherUserId, $body ?: ' ', $attachBookId, null, $attachments);
        if ($res['success']) {
            redirect('/views/dashboard/conversation.php?other_user_id=' . $otherUserId);
        } else {
            $error = $res['message'];
        }
    } else {
        $error = 'Message cannot be empty';
    }
}

$conversation = $message->getConversation($currentUserId, $otherUserId);

// Get user's available books for attachment
$userBooks = array_filter($book->getUserListings($currentUserId), function($b) {
    return $b['status'] === 'approved' && $b['availability'] === 'available';
});
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
            <form method="POST" action="<?= site_url('views/dashboard/delete_conversation.php') ?>" class="ms-3" id="deleteConversationForm">
                <input type="hidden" name="other_user_id" value="<?= intval($otherUserId) ?>">
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteConversationModal">Delete Conversation</button>
            </form>
        </div>

        <div class="card my-3">
            <div class="card-body" style="max-height: 60vh; overflow-y: auto;">
                <?php if (empty($conversation)): ?>
                    <p class="text-muted">No messages yet. Start the conversation below.</p>
                <?php else: ?>
                    <?php foreach ($conversation as $msg): ?>
                        <div class="mb-3 <?= $msg['sender_id'] == $currentUserId ? 'text-end' : 'text-start' ?>">
                            <div class="message-bubble <?= $msg['sender_id'] == $currentUserId ? 'message-sent' : 'message-received' ?> d-inline-block p-2 rounded position-relative">
                                <small class="d-block text-muted mb-1"><?= htmlspecialchars($msg['sender_id'] == $currentUserId ? 'You' : $msg['sender_name']) ?> • <?= timeAgo($msg['created_at']) ?></small>
                                <?php if (trim($msg['message'])): ?>
                                    <div><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                <?php endif; ?>
                                <?php
                                // Load attached book if any
                                if (!empty($msg['book_id'])) {
                                    $attachedBook = $book->getBookById($msg['book_id']);
                                    if ($attachedBook && $attachedBook['status'] === 'approved'):
                                ?>
                                        <div class="mt-2 p-2 border rounded bg-light">
                                            <div class="d-flex">
                                                <?php if ($attachedBook['image_path']): ?>
                                                    <img src="<?= site_url($attachedBook['image_path']) ?>" alt="Book Image" class="me-3" style="width: 60px; height: auto;">
                                                <?php else: ?>
                                                    <div class="me-3 bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                        <i class="bi bi-book"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><a href="<?= site_url('views/public/book_detail.php?id=' . $attachedBook['id']) ?>" target="_blank" class="text-decoration-none"><?= htmlspecialchars($attachedBook['title']) ?></a></h6>
                                                    <p class="mb-1 small text-muted">by <?= htmlspecialchars($attachedBook['author']) ?></p>
                                                    <div class="mb-1">
                                                        <span class="badge bg-info me-1"><?= ucfirst($attachedBook['exchange_type']) ?></span>
                                                        <span class="badge bg-secondary"><?= ucfirst(str_replace('_', ' ', $attachedBook['condition_type'])) ?></span>
                                                    </div>
                                                    <?php if ($attachedBook['exchange_type'] == 'buy' && $attachedBook['price']): ?>
                                                        <p class="mb-1 text-success fw-bold"><?= number_format($attachedBook['price'], 2) ?> ETB</p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($attachedBook['file_path'])): ?>
                                                        <?php
                                                        // For books attached in messages, allow recipient to access (direct sharing)
                                                        $fileAvailable = ($currentUserId == $attachedBook['user_id'] || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') || $msg['receiver_id'] == $currentUserId);
                                                        ?>
                                                        <?php if ($fileAvailable): ?>
                                                            <?php $btnOutline = $msg['sender_id'] == $currentUserId ? 'btn-outline-warning' : 'btn-outline-primary'; ?>
                                                            <?php $btnPrimary = $msg['sender_id'] == $currentUserId ? 'btn-warning' : 'btn-primary'; ?>
                                                            <?php $textClass = $msg['sender_id'] == $currentUserId ? 'text-dark' : ''; ?>
                                                            <a href="<?= site_url('views/public/download.php?book_id=' . $attachedBook['id'] . '&action=view') ?>" class="btn btn-sm <?= $btnOutline ?> me-1 <?= $textClass ?>" target="_blank">View</a>
                                                            <a href="<?= site_url('views/public/download.php?book_id=' . $attachedBook['id'] . '&action=download') ?>" class="btn btn-sm <?= $btnPrimary ?>">Download</a>
                                                        <?php else: ?>
                                                            <span class="text-muted small">File access restricted</span>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php } ?>

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
                                            <?php
                                            $fileExt = strtolower(pathinfo($a['filename'], PATHINFO_EXTENSION));
                                            $iconClass = 'file-icon-default';
                                            if (in_array($fileExt, ['pdf'])) $iconClass = 'file-icon-pdf';
                                            elseif (in_array($fileExt, ['doc', 'docx'])) $iconClass = 'file-icon-doc';
                                            elseif (in_array($fileExt, ['xls', 'xlsx'])) $iconClass = 'file-icon-xls';
                                            elseif (in_array($fileExt, ['zip', 'rar'])) $iconClass = 'file-icon-zip';
                                            elseif (in_array($fileExt, ['txt'])) $iconClass = 'file-icon-txt';
                                            $fileSize = round($a['size'] / 1024, 1) . ' KB'; // Convert to KB
                                            ?>
                                            <?php if (strpos($a['mime'], 'image/') === 0): ?>
                                                <div class="attachment-card">
                                                    <img src="<?= htmlspecialchars('../../' . $a['path']) ?>" alt="attachment" class="attachment-image" onclick="window.open(this.src, '_blank')" />
                                                </div>
                                            <?php elseif (strpos($a['mime'], 'audio/') === 0): ?>
                                                <div class="attachment-card">
                                                    <div class="attachment-preview">
                                                        <div class="attachment-icon">
                                                            <i class="bi bi-music-note"></i>
                                                        </div>
                                                        <div class="attachment-details">
                                                            <div class="attachment-name"><?= htmlspecialchars($a['filename']) ?></div>
                                                            <div class="attachment-size"><?= $fileSize ?></div>
                                                        </div>
                                                    </div>
                                                    <audio controls class="attachment-audio mt-2">
                                                        <source src="<?= htmlspecialchars('../../' . $a['path']) ?>" type="<?= htmlspecialchars($a['mime']) ?>">
                                                        Your browser does not support the audio element.
                                                    </audio>
                                                </div>
                                            <?php elseif (strpos($a['mime'], 'video/') === 0): ?>
                                                <div class="attachment-card">
                                                    <div class="attachment-preview">
                                                        <div class="attachment-icon">
                                                            <i class="bi bi-play-circle"></i>
                                                        </div>
                                                        <div class="attachment-details">
                                                            <div class="attachment-name"><?= htmlspecialchars($a['filename']) ?></div>
                                                            <div class="attachment-size"><?= $fileSize ?></div>
                                                        </div>
                                                    </div>
                                                    <video controls class="attachment-video mt-2">
                                                        <source src="<?= htmlspecialchars('../../' . $a['path']) ?>" type="<?= htmlspecialchars($a['mime']) ?>">
                                                        Your browser does not support the video element.
                                                    </video>
                                                </div>
                                            <?php else: ?>
                                                <div class="attachment-card">
                                                    <div class="attachment-preview">
                                                        <div class="attachment-icon <?= $iconClass ?>">
                                                            <i class="bi bi-file-earmark"></i>
                                                        </div>
                                                        <div class="attachment-details">
                                                            <div class="attachment-name"><?= htmlspecialchars($a['filename']) ?></div>
                                                            <div class="attachment-size"><?= $fileSize ?></div>
                                                        </div>
                                                    </div>
                                                    <?php $btnClass = $msg['sender_id'] == $currentUserId ? 'btn-warning' : 'btn-primary'; ?>
                                                    <a href="<?= htmlspecialchars('../../' . $a['path']) ?>" target="_blank" class="btn btn-sm <?= $btnClass ?> mt-2">Download</a>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($msg['sender_id'] == $currentUserId): ?>
                                    <div class="mt-2">
                                        <a href="<?= site_url('views/dashboard/edit_message.php?message_id=' . intval($msg['id'])) ?>" class="btn btn-sm btn-info" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="<?= site_url('views/dashboard/delete_message.php') ?>" style="display:inline-block;" id="deleteForm_<?= $msg['id'] ?>">
                                            <input type="hidden" name="message_id" value="<?= intval($msg['id']) ?>">
                                            <input type="hidden" name="other_user_id" value="<?= intval($otherUserId) ?>">
                                            <button type="button" class="btn btn-sm btn-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteMessageModal" data-form-id="deleteForm_<?= $msg['id'] ?>">
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
                <form method="POST" enctype="multipart/form-data" id="replyForm">
                    <div class="mb-3">
                        <label class="form-label">Attach Book (optional)</label>
                        <select name="book_id" class="form-select">
                            <option value="">No book attachment</option>
                            <?php foreach($userBooks as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= isset($_GET['book_id']) && $_GET['book_id'] == $b['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['title']) ?> by <?= htmlspecialchars($b['author']) ?> (<?= ucfirst($b['exchange_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Attach File (optional)</label>
                        <input type="file" name="attachment" class="form-control" accept="image/*,audio/*,video/*,.pdf,.doc,.docx,.zip,.rar,.txt">
                        <small class="text-muted">Supported: Images, Audio, Video, PDF, DOC/DOCX, ZIP, TXT (max 10MB)</small>
                    </div>
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="4" placeholder="Write your reply..." <?= empty($userBooks) ? 'required' : '' ?>></textarea>
                        <?php if (!empty($userBooks)): ?>
                            <small class="text-muted">Message is optional if attaching a book or file</small>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Send Reply</button>
                        <a href="<?= site_url('views/dashboard/messages.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- Delete Message Modal -->
    <div class="modal fade" id="deleteMessageModal" tabindex="-1" aria-labelledby="deleteMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteMessageModalLabel">Delete Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this message? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Message</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Conversation Modal -->
    <div class="modal fade" id="deleteConversationModal" tabindex="-1" aria-labelledby="deleteConversationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConversationModalLabel">Delete Conversation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this entire conversation? This will delete all messages between you and this user for everyone involved. This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteConversationBtn">Delete Conversation</button>
                </div>
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

        // Handle delete message modal
        (function() {
            const modal = document.getElementById('deleteMessageModal');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            let currentFormId = null;

            modal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                currentFormId = button.getAttribute('data-form-id');
            });

            confirmBtn.addEventListener('click', function() {
                if (currentFormId) {
                    const form = document.getElementById(currentFormId);
                    if (form) {
                        form.submit();
                    }
                }
            });
        })();

        // Handle delete conversation modal
        (function() {
            const confirmBtn = document.getElementById('confirmDeleteConversationBtn');
            const form = document.getElementById('deleteConversationForm');

            confirmBtn.addEventListener('click', function() {
                if (form) {
                    form.submit();
                }
            });
        })();
    </script>
</body>

</html>