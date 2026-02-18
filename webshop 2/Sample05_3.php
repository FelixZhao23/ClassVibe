<?php
session_start();

require './dbconf.inc';
$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
$mysqli->select_db(DB_NAME);

$gid = $_POST["gid"];
$num = $_POST["num"];

// ------------------------
// 入力チェック
// ------------------------
$errMsg = "";

if (!is_numeric($num)) {
    $errMsg = "個数は整数で入力してください";
}

if ($num <= 0) {
    $errMsg = "個数は1以上を入力してください";
}

// 商品番号チェック
$result = $mysqli->query("select Price from Goods where GoodsID = " . $gid);
$count = $result->num_rows;

if ($count == 0) {
    $errMsg = "その商品番号は存在しません";
}

// エラーがあった場合 → 戻る
if (!empty($errMsg)) {
    $_SESSION["errMsg"] = $errMsg;
    header("Location: ./Sample05_1.php");
    exit();
}

// 商品がある場合だけ実行
$row = $result->fetch_assoc();
$price = $row["Price"];
$result->free();
$mysqli->close();

// 保存して次へ
$_SESSION["gid"] = $gid;
$_SESSION["num"] = $num;
$_SESSION["price"] = $price;  // ★ 単価を保存
?>
<html>
<head>
    <title>Sample05_2.php</title>
</head>

<body>
    <h2>商品情報確認</h2>
    商品コード：<?php echo $gid; ?><br>
    個数：<?php echo $num; ?><br>
    単価：<?php echo $price; ?> 円<br><br>

    <form action="./Sample05_3.php" method="POST">
        <input type="submit" value="次へ（計算）">
    </form>
</body>
</html>
