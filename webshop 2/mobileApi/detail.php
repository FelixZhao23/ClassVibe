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
$gid = isset($_GET['gid']) ? (int)$_GET['gid'] : 0;
$sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.CostPrice, g.Stock, g.ImageName,
               c.CategoryName, m.MakerName, m.MakerURL
        FROM Goods g
        LEFT JOIN GoodsCategory c ON g.CategoryID = c.CategoryID
        LEFT JOIN Maker m ON g.MakerID = m.MakerID
        WHERE g.GoodsID = {$gid}";
$result = $mysqli->query($sql);
$item = [];
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $item = [
        'goods_id' => (int)$row['GoodsID'],
        'goods_name' => $row['GoodsName'],
        'price' => (int)$row['Price'],
        'cost_price' => (int)$row['CostPrice'],
        'stock' => (int)$row['Stock'],
        'image' => $row['ImageName'],
        'category' => $row['CategoryName'],
        'maker' => $row['MakerName'],
        'maker_url' => $row['MakerURL'],
        'detail' => isset($row['GoodsDetail']) ? $row['GoodsDetail'] : ''
    ];
}
if ($result) {
    $result->free();
}
$mysqli->close();
echo json_encode($item, JSON_UNESCAPED_UNICODE);
?>
