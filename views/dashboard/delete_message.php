<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$messageClass = new Message();
$currentUserId = $user->getCurrentUserId();

$messageId = intval($_POST['message_id'] ?? 0);
$redirect = '/views/dashboard/messages.php';
if ($messageId <= 0) redirect($redirect);

$res = $messageClass->deleteMessage($messageId, $currentUserId);
// ignore result and redirect back to conversation if provided
$other = intval($_POST['other_user_id'] ?? 0);
if ($other) redirect('/views/dashboard/conversation.php?other_user_id=' . $other);
redirect($redirect);
