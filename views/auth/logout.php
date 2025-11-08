<?php
require_once '../../includes/init.php';

$user = new User();
$user->logout();

redirect('/index.php');
?>
