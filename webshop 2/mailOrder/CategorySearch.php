<?php
require '../dbconf.inc';

$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}

$mysqli->select_db(DB_NAME);

$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
$category_name = '';

$category_sql = "SELECT CategoryName FROM GoodsCategory WHERE CategoryID = {$cid}";
$category_result = $mysqli->query($category_sql);
if ($category_result && $category_result->num_rows > 0) {
    $category_row = $category_result->fetch_assoc();
    $category_name = $category_row['CategoryName'];
}

$sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.Stock, g.ImageName, m.MakerName, m.MakerURL
        FROM Goods g
        LEFT JOIN Maker m ON g.MakerID = m.MakerID
        WHERE g.CategoryID = {$cid} AND g.Stock > 0
        ORDER BY g.GoodsID";
$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カテゴリ検索結果</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <header class="floating">
            <a href="index.php" class="site-logo">
                <img src="../logo.png" alt="日電通販サイト">
                <span>日電通販サイト</span>
            </a>
            <div class="subtitle">Category Search</div>
        </header>

        <div class="content-wrapper">
            <div class="card">
                <div class="nav">
                    <a class="btn" href="index.php">トップへ戻る</a>
                    <a class="btn" href="DisplayCart.php">カートを見る</a>
                </div>
                <h2>
                    カテゴリ: <?php echo htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8'); ?>
                    <?php if ($result): ?>
                        <span style="font-size: 0.8em; color: var(--text-muted); margin-left: 10px;">
                            (<?php echo $result->num_rows; ?>件)
                        </span>
                    <?php endif; ?>
                </h2>

                <?php if ($category_name === ''): ?>
                    <p class="empty">カテゴリが見つかりません。</p>
                <?php elseif (!$result || $result->num_rows === 0): ?>
                    <p class="empty">検索結果が0件です。</p>
                <?php else: ?>
                    <table class="list">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>商品名</th>
                                <th>単価</th>
                                <th>在庫</th>
                                <th>メーカー</th>
                                <th>詳細</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo (int)$row['GoodsID']; ?></td>
                                <td><?php echo htmlspecialchars($row['GoodsName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>&yen;<?php echo number_format($row['Price']); ?></td>
                                <td><?php echo (int)$row['Stock']; ?></td>
                                <td>
                                    <?php if (!empty($row['MakerURL'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['MakerURL'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                            <?php echo htmlspecialchars($row['MakerName'], ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($row['MakerName'], ENT_QUOTES, 'UTF-8'); ?>
                                    <?php endif; ?>
                                </td>
                                <td><a class="btn" href="GoodsDisplay.php?gid=<?php echo (int)$row['GoodsID']; ?>">詳細</a></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
if ($category_result) {
    $category_result->free();
}
if ($result) {
    $result->free();
}
$mysqli->close();
?>
