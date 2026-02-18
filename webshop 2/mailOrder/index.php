<?php

require '../dbconf.inc';

// MySQL 接続
$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}

$mysqli->select_db(DB_NAME);


$sql = "SELECT CategoryID, CategoryName FROM GoodsCategory ORDER BY CategoryID";
$result = $mysqli->query($sql);

?>


<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>⚡日電通販サイト - お得なショッピング！</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <header class="floating">
            <a href="index.php" class="site-logo">
                <img src="../logo.png" alt="日電通販サイト">
                <span>日電通販サイト</span>
            </a>
            <div class="subtitle">Shopping</div>
        </header>

        <div class="content-wrapper">
            <div class="card search-box">
                <h2>商品検索</h2>
                <form method="get" action="GoodsSearch.php">
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="keyword" placeholder="何をお探しですか？">
                        <button type="submit">検索</button>
                    </div>
                </form>
            </div>

            <div class="card category-list">
                <h2>カテゴリから探す</h2>
                <p style="margin-bottom: 20px; color: var(--text-muted);">気になるカテゴリを選択してください。</p>

                <ul>
                    <?php
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<li>";
                            echo "<a href='CategorySearch.php?cid=" 
                                    . $row["CategoryID"] . "'>";
                            echo htmlspecialchars($row["CategoryName"], ENT_QUOTES, 'UTF-8');
                            echo "</a>";
                            echo "</li>";
                        }
                    } else {
                        echo "<li>😅 カテゴリがありません</li>";
                    }
                    ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$mysqli->close();
?>
