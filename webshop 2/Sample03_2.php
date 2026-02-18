<?php
$searchName = $_POST["searchName"];

$mysqli = mysqli_connect('mysql327.phy.lolipop.lan', 'LAA1666871', password: 'wyq123');
//$mysqli = mysqli_connect('mysql', 'user1', 'user1password');
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}


// $mysqli->select_db('webshop');
$mysqli->select_db('LAA1666871-webshop');
if($searchName == ""){
    $result = $mysqli ->query("SELECT * FROM Maker");
}
$result = $mysqli->query("SELECT * FROM Maker where MakerName LIKE '%{$searchName}%'");

echo "<h4>検索結果は" . $result->num_rows . "件です</h4>";

echo '<table border= "1">';
echo '<tr><th>ID</th><th>メーカー名</th><th>メーカーURL</th></tr>';


while ($row = $result->fetch_assoc()) {
   echo '<tr>';
   echo "<td>{$row['MakerID']}</td><td>{$row['MakerName']}</td><td>{$row['MakerURL']}</td>";
   echo '</tr>';
}




$result->free();
$mysqli->close();
?>
