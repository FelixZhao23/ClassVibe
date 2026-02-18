<?php
session_start();
require '../dbconf.inc';

$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}
$mysqli->select_db(DB_NAME);

$action = isset($_POST['action']) ? $_POST['action'] : 'add';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$errors = [];

if ($action === 'update' && isset($_POST['qty']) && is_array($_POST['qty'])) {
    foreach ($_POST['qty'] as $gid => $qty) {
        $gid = (int)$gid;
        $qty = trim($qty);
        if (!is_numeric($qty)) {
            $errors[] = "商品ID {$gid}: 数量は数字で入力してください。";
            continue;
        }
        $qty = (int)$qty;
        if ($qty <= 0) {
            $errors[] = "商品ID {$gid}: 数量は1以上で入力してください。";
            continue;
        }
        $result = $mysqli->query("SELECT Stock FROM Goods WHERE GoodsID = {$gid}");
        $row = $result ? $result->fetch_assoc() : null;
        $stock = $row ? (int)$row['Stock'] : 0;
        if ($qty > $stock) {
            $errors[] = "商品ID {$gid}: 在庫数を超えています。";
            continue;
        }
        $_SESSION['cart'][$gid] = $qty;
    }

    $_SESSION['cart_errors'] = $errors;
    $mysqli->close();
    header('Location: DisplayCart.php');
    exit;
}

$gid = isset($_POST['gid']) ? (int)$_POST['gid'] : 0;
$num = isset($_POST['num']) ? trim($_POST['num']) : '';

if ($gid <= 0) {
    $_SESSION['goods_error'] = '商品が正しく指定されていません。';
    header('Location: GoodsDisplay.php?gid=' . $gid);
    exit;
}

if (!is_numeric($num)) {
    $_SESSION['goods_error'] = '数量は数字で入力してください。';
    $_SESSION['goods_num'] = $num;
    header('Location: GoodsDisplay.php?gid=' . $gid);
    exit;
}

$num = (int)$num;
if ($num <= 0) {
    $_SESSION['goods_error'] = '数量は1以上で入力してください。';
    $_SESSION['goods_num'] = $num;
    header('Location: GoodsDisplay.php?gid=' . $gid);
    exit;
}

$result = $mysqli->query("SELECT Stock FROM Goods WHERE GoodsID = {$gid}");
$row = $result ? $result->fetch_assoc() : null;
$stock = $row ? (int)$row['Stock'] : 0;

if ($num > $stock) {
    $_SESSION['goods_error'] = '在庫数を超えています。';
    $_SESSION['goods_num'] = $num;
    header('Location: GoodsDisplay.php?gid=' . $gid);
    exit;
}

$current = isset($_SESSION['cart'][$gid]) ? (int)$_SESSION['cart'][$gid] : 0;
$_SESSION['cart'][$gid] = $current + $num;

$mysqli->close();
header('Location: DisplayCart.php');
?>
