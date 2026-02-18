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
$sql = "SELECT CategoryID, CategoryName FROM GoodsCategory ORDER BY CategoryID";
$result = $mysqli->query($sql);
$list = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $list[] = [
            'category_id' => (int)$row['CategoryID'],
            'category_name' => $row['CategoryName']
        ];
    }
    $result->free();
}
$mysqli->close();
echo json_encode($list, JSON_UNESCAPED_UNICODE);
?>
