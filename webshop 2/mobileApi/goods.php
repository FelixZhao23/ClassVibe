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
$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$where = "WHERE 1=1";
if ($cid > 0) {
    $where .= " AND g.CategoryID = {$cid}";
}
$sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.Stock, g.ImageName
        FROM Goods g
        {$where}
        ORDER BY g.GoodsID";
$result = $mysqli->query($sql);
$list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $list[] = [
            'goods_id' => (int)$row['GoodsID'],
            'goods_name' => $row['GoodsName'],
            'price' => (int)$row['Price'],
            'stock' => (int)$row['Stock'],
            'image' => $row['ImageName']
        ];
    }
    $result->free();
}
$mysqli->close();
echo json_encode($list, JSON_UNESCAPED_UNICODE);
?>
