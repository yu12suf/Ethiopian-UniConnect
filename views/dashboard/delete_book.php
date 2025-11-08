<?php
require_once '../../includes/init.php';
requireLogin();

$user = new User();
$book = new Book();

$bookId = $_GET['id'] ?? 0;

if ($bookId) {
    $book->deleteListing($bookId, $user->getCurrentUserId());
}

redirect('/views/dashboard/index.php');
?>
