<?php
require '../dbconf.inc';

$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}

$mysqli->select_db(DB_NAME);

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$escaped = $mysqli->real_escape_string($keyword);

if ($keyword === '') {
    $sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.Stock, g.ImageName,
                   m.MakerName, m.MakerURL, c.CategoryName
            FROM Goods g
            LEFT JOIN Maker m ON g.MakerID = m.MakerID
            LEFT JOIN GoodsCategory c ON g.CategoryID = c.CategoryID
            WHERE g.Stock > 0
            ORDER BY g.GoodsID";
} else {
    $sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.Stock, g.ImageName,
                   m.MakerName, m.MakerURL, c.CategoryName
            FROM Goods g
            LEFT JOIN Maker m ON g.MakerID = m.MakerID
            LEFT JOIN GoodsCategory c ON g.CategoryID = c.CategoryID
            WHERE g.Stock > 0 AND g.GoodsName LIKE '%{$escaped}%'
            ORDER BY g.GoodsID";
}

$result = $mysqli->query($sql);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品名検索結果</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <header class="floating">
            <a href="index.php" class="site-logo">
                <img src="../logo.png" alt="日電通販サイト">
                <span>日電通販サイト</span>
            </a>
            <div class="subtitle">Search Results</div>
        </header>

        <div class="content-wrapper">
            <div class="card">
                <div class="nav">
                    <a class="btn" href="index.php">トップへ戻る</a>
                    <a class="btn" href="DisplayCart.php">カートを見る</a>
                </div>

                <?php if ($keyword === ''): ?>
                    <p class="hint">キーワード未入力のため、全件検索結果を表示しています。</p>
                <?php else: ?>
                    <p class="hint">
                        検索キーワード: <?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>
                        <?php if ($result): ?>
                            <span style="margin-left: 10px; font-weight: bold; color: var(--accent);">
                                (<?php echo $result->num_rows; ?>件ヒット)
                            </span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!$result || $result->num_rows === 0): ?>
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
                                <th>カテゴリ</th>
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
                                <td><?php echo htmlspecialchars($row['CategoryName'], ENT_QUOTES, 'UTF-8'); ?></td>
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
if ($result) {
    $result->free();
}
$mysqli->close();
?>
