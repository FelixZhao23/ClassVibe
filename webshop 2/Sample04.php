<?php
require './dbconf.inc';//外部documentを導入

// define('DB_URL','mysql');
// define('DB_USER','user1');
// define('DB_PASS','user1password');
// define('DB_NAME','webshop');

//http://localhost:8080/Sample04.php


//--- 1：データベース接続---//ロリポップ
// $mysqli = mysqli_connect( 'mysql327.phy lolipop.lan', 'LAA1683640', 'hogehoge');
// ローカル接続用
// $mysqli = mysqli_connect ( 'mysql','user1', 'user1password');
$mysqli = mysqli_connect ( DB_URL,DB_USER, DB_PASS);//定量をuploadしてサーバーを繋がって、return値がmysql型

//
if ( $mysqli->connect_error)
    {
        echo $mysqli->connect_error; 
        exit;
    }

//-- 2：データベースを選択-// ロリポップ
// $mysqli->select_db ( 'LAA1683640-webshop' );
// ローカル接続用
// $mysqli->select_db('webshop');
$mysqli->select_db(DB_NAME);//lolipopのDBを設定する。

$result = $mysqli->query("select * from Goods where GoodsID = 201");
$row = $result->fetch_assoc();

echo $row ["GoodsName"];
echo "<hr>";
echo $row["ImageName"];

echo "<img src='./img/celeron.jpg'>";

echo "<img src='./img/".$row['ImageName'] . "'>";

$result->free();
$mysqli->close();
?>