<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$message = new Message();
$admin = new Admin(); // reuse admin helper to fetch users list
$bookClass = new Book();

$currentUserId = $user->getCurrentUserId();

// Fetch users and filter out the current user and only include active users
$users = $admin->getAllUsers();
$recipients = array_filter($users, function ($u) use ($currentUserId) {
    return $u['id'] != $currentUserId && isset($u['status']) && $u['status'] === 'active';
});

// Get approved books to optionally attach to message (limit recent 50)
$books = $bookClass->getBooks();

$errors = [];
$success = '';

// Pre-fill when opened via query (reply)
$prefillReceiver = intval($_GET['to'] ?? 0);
$prefillBook = intval($_GET['book_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // If using the autocomplete field, the selected id is in receiver_id hidden field
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $recipient_label = trim($_POST['recipient_label'] ?? '');
    $book_id = !empty($_POST['book_id']) ? intval($_POST['book_id']) : null;
    $subject = sanitize($_POST['subject'] ?? '');
    $body = sanitize($_POST['message'] ?? '');

    // If receiver_id not provided, try to resolve from the visible label (e.g. "Full Name <email>")
    if ($receiver_id <= 0 && $recipient_label !== '') {
        $db = Database::getInstance()->getConnection();
        $resolved = false;

        // try extracting an email inside <...>
        $resolutionMethod = null;
        if (preg_match('/<([^>]+)>/', $recipient_label, $m)) {
            $email = trim($m[1]);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $st = $db->prepare('SELECT id, status FROM users WHERE email = ? LIMIT 1');
                $st->execute([$email]);
                $u = $st->fetch();
                if ($u && $u['status'] === 'active') {
                    $receiver_id = intval($u['id']);
                    $resolved = true;
                    $resolutionMethod = 'email_in_brackets';
                }
            }
        }

        // try if the entire input is an email
        if (!$resolved && filter_var($recipient_label, FILTER_VALIDATE_EMAIL)) {
            $st = $db->prepare('SELECT id, status FROM users WHERE email = ? LIMIT 1');
            $st->execute([$recipient_label]);
            $u = $st->fetch();
            if ($u && $u['status'] === 'active') {
                $receiver_id = intval($u['id']);
                $resolved = true;
                $resolutionMethod = 'email_exact';
            }
        }

        // try exact full_name match
        if (!$resolved) {
            $st = $db->prepare('SELECT id, status FROM users WHERE full_name = ? LIMIT 1');
            $st->execute([$recipient_label]);
            $u = $st->fetch();
            if ($u && $u['status'] === 'active') {
                $receiver_id = intval($u['id']);
                $resolved = true;
                $resolutionMethod = 'name_exact';
            }
        }

        // fallback to a LIKE search (first match)
        if (!$resolved) {
            $pattern = '%' . str_replace('%', '\\%', $recipient_label) . '%';
            $st = $db->prepare('SELECT id, status FROM users WHERE status = ? AND (full_name LIKE ? OR email LIKE ?) LIMIT 1');
            $st->execute(['active', $pattern, $pattern]);
            $u = $st->fetch();
            if ($u) {
                $receiver_id = intval($u['id']);
                $resolved = true;
                $resolutionMethod = 'like_fallback';
            }
        }
        // If we resolved via server-side logic, set a session flag so messages.php can show a notice
        if ($resolved) {
            $_SESSION['recipient_resolved_server'] = [
                'label' => $recipient_label,
                'resolved_id' => $receiver_id,
                'method' => $resolutionMethod
            ];
        }
    }

    if (empty($body)) {
        $errors[] = 'Message cannot be empty';
    }

    // Check recipient exists and not current user
    $validRecipient = false;
    foreach ($recipients as $r) {
        if ($r['id'] == $receiver_id) {
            $validRecipient = true;
            break;
        }
    }
    if (!$validRecipient) {
        $errors[] = 'Please select a recipient from the suggestions';
    }

    if (empty($errors)) {
        // Handle file uploads (if any)
        $attachmentIds = [];
        if (!empty($_FILES['attachments'])) {
            $att = new Attachment();
            // Normalize multiple files
            $files = $_FILES['attachments'];
            if (is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $fileArray = [
                            'name' => $files['name'][$i],
                            'type' => $files['type'][$i],
                            'tmp_name' => $files['tmp_name'][$i],
                            'error' => $files['error'][$i],
                            'size' => $files['size'][$i]
                        ];
                        $aid = $att->storeUpload($fileArray, $currentUserId);
                        if ($aid) $attachmentIds[] = $aid;
                    }
                }
            } else {
                // single upload
                if ($files['error'] === UPLOAD_ERR_OK) {
                    $aid = $att->storeUpload($files, $currentUserId);
                    if ($aid) $attachmentIds[] = $aid;
                }
            }
        }

        $res = $message->sendMessage($currentUserId, $receiver_id, $body, $book_id, $subject, $attachmentIds);
        if ($res['success']) {
            // Redirect to messages list
            redirect('/views/dashboard/messages.php');
        } else {
            $errors[] = $res['message'];
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Compose Message - UniConnect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>

<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container my-5">
        <h2 class="mb-4"><i class="bi bi-pencil-square"></i> Compose New Message</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $e): ?>
                    <?= htmlspecialchars($e) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" id="composeForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">To</label>
                        <!-- Autocomplete recipient: visible input backed by hidden receiver_id -->
                        <input name="recipient_label" list="recipient_list" id="recipient_search" class="form-control" placeholder="Type name or email" autocomplete="off">
                        <datalist id="recipient_list">
                            <?php foreach ($recipients as $r): ?>
                                <option data-id="<?= $r['id'] ?>" value="<?= htmlspecialchars($r['full_name'] . ' <' . $r['email'] . '>') ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <input type="hidden" name="receiver_id" id="receiver_id" value="<?= $prefillReceiver ?? '' ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Subject (optional)</label>
                        <input type="text" name="subject" id="subject_input" class="form-control" value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" placeholder="Short subject or summary">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Regarding (optional)</label>
                        <select name="book_id" class="form-select">
                            <option value="">-- None --</option>
                            <?php foreach ($books as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= ($prefillBook && $prefillBook == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['title']) ?> by <?= htmlspecialchars($b['author']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="6" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Attachments (images, audio, video, pdf) — optional</label>
                        <input type="file" name="attachments[]" multiple class="form-control">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">Send Message</button>
                        <a href="<?= site_url('views/dashboard/messages.php') ?>" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../../includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Map datalist selections to hidden receiver_id
        (function() {
            const datalist = document.getElementById('recipient_list');
            const input = document.getElementById('recipient_search');
            const hidden = document.getElementById('receiver_id');

            // Build map from value -> id
            const map = {};
            Array.from(datalist.options).forEach(opt => {
                map[opt.value] = opt.getAttribute('data-id');
            });

            // If prefilled receiver exists, show its label
            const prefilledId = hidden.value;
            if (prefilledId) {
                for (const opt of datalist.options) {
                    if (opt.getAttribute('data-id') == prefilledId) {
                        input.value = opt.value;
                        break;
                    }
                }
            }

            input.addEventListener('input', function() {
                const val = input.value;
                if (map[val]) {
                    hidden.value = map[val];
                } else {
                    // clear hidden id if no exact match
                    hidden.value = '';
                }
            });
        })();

        // Suggest a subject when a book is selected (non-intrusive)
        (function() {
            const bookSelect = document.querySelector('select[name="book_id"]');
            const subjectInput = document.getElementById('subject_input');
            let userEdited = false;

            // if user modifies subject, don't overwrite
            subjectInput.addEventListener('input', function() {
                userEdited = true;
            });

            bookSelect.addEventListener('change', function() {
                if (userEdited) return; // don't overwrite if user already typed
                const selected = bookSelect.options[bookSelect.selectedIndex];
                if (selected && selected.value) {
                    const title = selected.text.split(' by ')[0];
                    subjectInput.value = 'Inquiry about: ' + title;
                } else {
                    // clear suggestion if no book selected and user hasn't edited
                    subjectInput.value = '';
                }
            });
        })();
    </script>
    <script>
        // Prevent accidental double-submit by disabling the send button after first submit
        (function() {
            const form = document.getElementById('composeForm');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                const btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerText = 'Sending...';
                }
            });
        })();
    </script>
</body>

</html>