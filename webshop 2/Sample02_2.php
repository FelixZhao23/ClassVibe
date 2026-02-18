<?php
$mid = intval($_GET["mid"]);


$mysqli = mysqli_connect('mysql','user1','user1password');
// $mysqli = mysqli_connect(hostname: 'mysql327.phy.lolipop.lan', 'LAA1666871', 'wyq123');

if ($mysqli->connect_error) {
    echo "Connect Error: " . $mysqli->connect_error;
    exit;
}

 $mysqli->select_db( 'webshop');
// $mysqli->select_db('LAA1666871-webshop');

$sql = "SELECT * FROM Goods WHERE MakerID = {$mid}";
$result = $mysqli->query($sql);

if (!$result) {
    echo "SQL Error: " . $mysqli->error;
    exit;
}

while ($row = $result->fetch_assoc()) {
    echo $row["GoodsID"] . "/";
    echo $row["CategoryID"] . "/";
    echo $row["GoodsName"] . "/";
    echo $row["Price"] . "/";
    echo $row["CostPrice"] . "/";
    echo $row["MakerID"] . "/";
    echo $row["Stock"] . "/";
    echo "<br>";
}

$result->free();
$mysqli->close();
?>
