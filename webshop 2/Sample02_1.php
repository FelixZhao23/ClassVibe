<?php

$mysqli = mysqli_connect('mysql','user1','user1password');
// $mysqli = mysqli_connect('mysql327.phy.lolipop.lan', 'LAA1666871', 'wyq123');


if ( $mysqli->connect_error ) 
    {
        echo $mysqli->connect_error;
        exit;
    }

    $mysqli->select_db( 'webshop');
$mysqli->select_db( 'LAA1666871-webshop');
$result = $mysqli->query('select * from Maker');
while($row = $result->fetch_assoc()) {

    // echo "<a href='Sample02_2.php?mid=5'>" . $row ["MakerName"] . "</a>";
    echo "<a href='Sample02_2.php?mid=" . $row ["MakerID"] ."'>" . $row["MakerName"] . "</a>";
    echo "<br>";
}

$result->free();
$mysqli->close();
?>

