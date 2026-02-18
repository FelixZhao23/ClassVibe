<!-- 
演習1：学生一覧をtableタグで整形して表示してください
演習2：表のタイトルをつけて表示してください（「学籍番号」「名前」）
演習3：名前の文字の色を赤にしてください
-->



<?php

// $mysqli = mysqli_connect('mysql326.phy.lolipop.lan','LAA1666871','W7agWK4CbSuiEqT');
$mysqli = mysqli_connect('mysql','user1','user1password');

if ($mysqli->connect_error) {
    echo $mysqli->connect_error;
    exit;
}


$mysqli->select_db('LAA1666871-testdb');
// $mysqli->select_db('testdb');
$result = $mysqli->query('select * from students');

echo '<table border="1">';//表格边框
echo '<tr><th>学籍番号</th><th>名前</th></tr>';//表列名

while($row = $result->fetch_assoc()){
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['no']) . '</td>';
    echo '<td style="color:red;">' . htmlspecialchars($row['name']) . '</td>';
    echo '</tr>';
}

$result->free();
$mysqli->close();

?>