<?php
session_start();
?>
<html>

<head>
    <title>Sample05_1.php</title>
</head>

<body>
    <h2>簡易計算機</h2>
    <form action="./Sample05_2.php" method="POST">
        商品コード:
        <input type="text" name="gid">
        <br>
        個数:
        <input type="text" name="num">
        <span style="color:red;">
            <?php echo $_SESSION["errMsg"];
            unset($_SESSION["errMsg"]); ?>
        </span>
        <br>
        <input type="submit" value="計算">
    </form>
</body>

</html>

<!-- http://localhost: 8080/Sample05_1.php -->