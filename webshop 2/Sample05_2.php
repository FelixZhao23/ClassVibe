<?php
session_start();

require './dbconf.inc';
$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
$mysqli->select_db(DB_NAME);

$gid = $_POST["gid"];//定义goodsID
$num = $_POST["num"];//定义个数


$errMsg = "";//定义error字段

// 整数チェック
//若输入非整数 → 给error字段赋值。
if (!is_numeric($num)) {
    $errMsg = "個数は整数で入力してください";
}
// >0 check（数字の場合のみ）若输入负数 → 给error赋值。
else if ($num <= 0) {
    $errMsg = "個数は1以上を入力してください";
}

// 商品番号チェック（存在確認） 若无error → 查找单价 + 库存数
if (empty($errMsg)) {
    $result = $mysqli->query("SELECT Price, Stock FROM Goods WHERE GoodsID = " . $gid);

    if ($result->num_rows == 0) {
        $errMsg = "その商品番号は存在しません";
    }
}

// エラーがある場合 → 戻る
if (!empty($errMsg)) {
    $_SESSION["errMsg"] = $errMsg;
    header("Location: ./Sample05_1.php");
    exit();
}

// 商品が存在するので取得
$row = $result->fetch_assoc();
$price = $row["Price"];
$goodStock = $row["Stock"]; // 在庫数

// 在庫数チェック
if ($num > $goodStock) {
    $_SESSION["errMsg"] = "over";
    header("Location: ./Sample05_1.php");
    exit();
}

$result->free();
$mysqli->close();

// 計算
$total = $price * $num;

// 保存
$_SESSION["gid"] = $gid;
$_SESSION["num"] = $num;
$_SESSION["price"] = $price;
$_SESSION["total"] = $total;
$_SESSION["stock"] = $goodStock;
?>
<html>
<head>
    <title>Sample05_2.php</title>
</head>

<body>
    <h2>商品情報確認</h2>
    商品コード：<?php echo $gid; ?><br>
    個数：<?php echo $num; ?><br>
    単価：<?php echo $price; ?> 円<br>
    合計金額：<?php echo $total; ?> 円<br>
    在庫数：<?php echo $goodStock; ?><br>
</body>
</html>