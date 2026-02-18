<?php
session_start();

$gid = isset($_GET['gid']) ? (int)$_GET['gid'] : 0;
if ($gid > 0 && isset($_SESSION['cart'][$gid])) {
    unset($_SESSION['cart'][$gid]);
}

header('Location: DisplayCart.php');
?>
