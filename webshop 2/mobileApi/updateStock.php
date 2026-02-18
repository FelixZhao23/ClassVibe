<?php
require '../dbconf.inc';
header('Content-Type: application/json; charset=utf-8');
$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo json_encode(['error' => $mysqli->connect_error]);
    exit;
}
$mysqli->select_db(DB_NAME);

$gid = isset($_POST['gid']) ? (int)$_POST['gid'] : 0;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : -1;
if ($gid <= 0 || $stock < 0) {
    echo json_encode(['success' => false, 'message' => '入力が不正です']);
    exit;
}

$mysqli->query("UPDATE Goods SET Stock = {$stock} WHERE GoodsID = {$gid}");
$mysqli->close();

echo json_encode(['success' => true, 'stock' => $stock]);
?>
