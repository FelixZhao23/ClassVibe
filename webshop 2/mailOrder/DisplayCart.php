<?php
session_start();
require '../dbconf.inc';

$mysqli = mysqli_connect(DB_URL, DB_USER, DB_PASS);
if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}
$mysqli->select_db(DB_NAME);

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$errors = isset($_SESSION['cart_errors']) ? $_SESSION['cart_errors'] : [];
unset($_SESSION['cart_errors']);

$items = [];
$total = 0;

if (!empty($cart)) {
    $ids = implode(',', array_map('intval', array_keys($cart)));
    $sql = "SELECT g.GoodsID, g.GoodsName, g.Price, g.Stock
            FROM Goods g
            WHERE g.GoodsID IN ({$ids})";
    $result = $mysqli->query($sql);
    while ($row = $result->fetch_assoc()) {
        $gid = (int)$row['GoodsID'];
        $qty = isset($cart[$gid]) ? (int)$cart[$gid] : 0;
        $subtotal = $row['Price'] * $qty;
        $total += $subtotal;
        $items[] = [
            'GoodsID' => $gid,
            'GoodsName' => $row['GoodsName'],
            'Price' => $row['Price'],
            'Stock' => $row['Stock'],
            'Qty' => $qty,
            'Subtotal' => $subtotal,
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>カート</title>
    <link rel="stylesheet" href="./style.css">
</head>
<body>
    <div class="container">
        <header class="floating">
            <a href="index.php" class="site-logo">
                <img src="../logo.png" alt="日電通販サイト">
                <span>日電通販サイト</span>
            </a>
            <div class="subtitle">Cart</div>
        </header>

        <div class="content-wrapper">
            <div class="card">
                <div class="nav">
                    <a class="btn" href="index.php">トップへ戻る</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <?php foreach ($errors as $e): ?>
                            <div class="error"><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (empty($items)): ?>
                    <p class="empty">カートに商品がありません。</p>
                <?php else: ?>
                    <form method="post" action="CartAdd.php">
                        <input type="hidden" name="action" value="update">
                        <table class="list">
                            <thead>
                                <tr>
                                    <th>商品名</th>
                                    <th>単価</th>
                                    <th>在庫</th>
                                    <th>数量</th>
                                    <th>小計</th>
                                    <th>削除</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['GoodsName'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>&yen;<?php echo number_format($item['Price']); ?></td>
                                        <td><?php echo (int)$item['Stock']; ?></td>
                                        <td>
                                            <div class="qty-group">
                                                <button type="button" class="qty-btn" onclick="updateQty(this, -1)">-</button>
                                                <input type="text" name="qty[<?php echo (int)$item['GoodsID']; ?>]" value="<?php echo (int)$item['Qty']; ?>" pattern="\d*">
                                                <button type="button" class="qty-btn" onclick="updateQty(this, 1)">+</button>
                                            </div>
                                        </td>
                                        <td>&yen;<?php echo number_format($item['Subtotal']); ?></td>
                                        <td><a class="btn danger sm" href="CartDel.php?gid=<?php echo (int)$item['GoodsID']; ?>" data-confirm="この商品を削除します。よろしいですか？">削除</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="cart-footer">
                            <div>
                                <a class="btn danger" href="CartClear.php" data-confirm="カート内の商品をすべて削除します。よろしいですか？" style="font-size: 0.9rem; padding: 8px 16px;">全て削除</a>
                            </div>
                            <div style="display: flex; gap: 20px; align-items: center;">
                                <div class="total">合計: &yen;<?php echo number_format($total); ?></div>
                                <button type="submit">数量を更新</button>
                            </div>
                        </div>
                    </form>
                    <script>
                        document.querySelectorAll('[data-confirm]').forEach(function (el) {
                            el.addEventListener('click', function (e) {
                                if (!confirm(el.getAttribute('data-confirm'))) {
                                    e.preventDefault();
                                }
                            });
                        });
                        function updateQty(btn, change) {
                            const input = btn.parentElement.querySelector('input');
                            let val = parseInt(input.value) || 0;
                            val += change;
                            if (val < 1) val = 1;
                            input.value = val;
                        }
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$mysqli->close();
?>
