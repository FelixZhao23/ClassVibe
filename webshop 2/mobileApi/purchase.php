<?php
require '../dbconf.inc';
header('Content-Type: application/json; charset=utf-8');
$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo json_encode(['error' => $mysqli->connect_error]);
    exit;
}
$mysqli->select_db(DB_NAME);
?>
<?php
$gid = isset($_POST['gid']) ? (int)$_POST['gid'] : 0;
$num = isset($_POST['num']) ? (int)$_POST['num'] : 0;
if ($gid <= 0 || $num <= 0) {
    echo json_encode(['success' => false, 'message' => '入力が不正です']);
    exit;
}

$mysqli->begin_transaction();
$result = $mysqli->query("SELECT Stock FROM Goods WHERE GoodsID = {$gid} FOR UPDATE");
if (!$result || $result->num_rows === 0) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => '商品が見つかりません']);
    exit;
}
$row = $result->fetch_assoc();
$stock = (int)$row['Stock'];
if ($num > $stock) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => '在庫数を超えています', 'stock' => $stock]);
    exit;
}
$newStock = $stock - $num;
$mysqli->query("UPDATE Goods SET Stock = {$newStock} WHERE GoodsID = {$gid}");
$mysqli->commit();

$result->free();
$mysqli->close();

echo json_encode(['success' => true, 'message' => '購入しました', 'stock' => $newStock]);
?>
