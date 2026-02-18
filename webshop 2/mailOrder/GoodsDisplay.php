<?php
session_start();
require '../dbconf.inc';

$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}

$mysqli->select_db(DB_NAME);

$gid = isset($_GET['gid']) ? (int)$_GET['gid'] : 0;

$sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.Stock, g.ImageName,
               m.MakerName, m.MakerURL, c.CategoryName
        FROM Goods g
        LEFT JOIN Maker m ON g.MakerID = m.MakerID
        LEFT JOIN GoodsCategory c ON g.CategoryID = c.CategoryID
        WHERE g.GoodsID = {$gid}";
$result = $mysqli->query($sql);
$item = $result ? $result->fetch_assoc() : null;
$query_error = $result ? '' : $mysqli->error;

$err = isset($_SESSION['goods_error']) ? $_SESSION['goods_error'] : '';
$old_num = isset($_SESSION['goods_num']) ? $_SESSION['goods_num'] : '1';
unset($_SESSION['goods_error'], $_SESSION['goods_num']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>商品詳細</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <header class="floating">
            <a href="index.php" class="site-logo">
                <img src="../logo.png" alt="日電通販サイト">
                <span>日電通販サイト</span>
            </a>
            <div class="subtitle">商品詳細</div>
        </header>

        <div class="content-wrapper">
            <div class="card">
                <div class="nav">
                    <a class="btn" href="index.php">トップへ戻る</a>
                    <a class="btn" href="DisplayCart.php">カートを見る</a>
                </div>

                <?php if (!$item): ?>
                    <p class="empty">商品が見つかりません。（指定ID: <?php echo (int)$gid; ?>）</p>
                    <p class="hint">検索結果の「詳細」ボタンからアクセスしてください。</p>
                    <?php if (!empty($query_error)): ?>
                        <p class="error">DBエラー: <?php echo htmlspecialchars($query_error, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="detail">
                        <div class="detail-img">
                            <?php if (!empty($item['ImageName'])): ?>
                                <img src="../goodsImg/<?php echo htmlspecialchars($item['ImageName'], ENT_QUOTES, 'UTF-8'); ?>" alt="商品画像">
                            <?php else: ?>
                                <div class="no-img">NO IMAGE</div>
                            <?php endif; ?>
                        </div>
                        <div class="detail-body">
                            <h2><?php echo htmlspecialchars($item['GoodsName'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="price">&yen;<?php echo number_format($item['Price']); ?></p>
                            <p>在庫: <?php echo (int)$item['Stock']; ?></p>
                            <p>カテゴリ: <?php echo htmlspecialchars($item['CategoryName'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p>メーカー: 
                                <?php if (!empty($item['MakerURL'])): ?>
                                    <a href="<?php echo htmlspecialchars($item['MakerURL'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                                        <?php echo htmlspecialchars($item['MakerName'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($item['MakerName'], ENT_QUOTES, 'UTF-8'); ?>
                                <?php endif; ?>
                            </p>
                            <?php if (isset($item['GoodsDetail']) && !empty($item['GoodsDetail'])): ?>
                                <p class="desc"><?php echo htmlspecialchars($item['GoodsDetail'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>

                            <form method="post" action="CartAdd.php" class="cart-form">
                                <input type="hidden" name="gid" value="<?php echo (int)$item['GoodsID']; ?>">
                                <label style="font-weight:bold; margin-right: 8px;">数量</label>
                                <div class="qty-group">
                                    <button type="button" class="qty-btn" onclick="updateQty(this, -1)">-</button>
                                    <input type="text" name="num" value="<?php echo htmlspecialchars($old_num, ENT_QUOTES, 'UTF-8'); ?>" pattern="\d*">
                                    <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                                </div>
                                <button type="submit" style="margin-left: 16px;">カートに入れる</button>
                                <?php if (!empty($err)): ?>
                                    <div class="error" style="width: 100%;"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
    function updateQty(btn, change) {
        const input = btn.parentElement.querySelector('input');
        let val = parseInt(input.value) || 0;
        val += change;
        if (val < 1) val = 1;
        input.value = val;
    }
    </script>
</body>
</html>

<?php
if ($result) {
    $result->free();
}
$mysqli->close();
?>
