<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$messageClass = new Message();
$currentUserId = $user->getCurrentUserId();

$other = intval($_POST['other_user_id'] ?? 0);
if ($other <= 0) redirect('/views/dashboard/messages.php');

// Note: this removes the conversation for both users (permanent delete)
$res = $messageClass->deleteConversation($currentUserId, $other);
redirect('/views/dashboard/messages.php');
