<?php
if(in_array($_SERVER['SERVER_NAME'], ['localhost','127.0.0.1','10.0.2.2'])){
    define('DB_URL','mysql');
    define('DB_USER','user1');
    define('DB_PASS','user1password');
    define('DB_NAME','webshop');
}else{
        define('DB_URL','mysql327.phy.lolipop.lan');//データベースホスト
        define('DB_USER','LAA1666871'); //アカウントID
        define('DB_PASS','wyq123');//hogehoge
        define('DB_NAME','LAA1666871-webshop');//データベース名
}

?>
