<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'shop_user');
define('DB_PASS', 'Shop@Pass1');
define('DB_NAME', 'zorinshop');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("DB 연결 실패: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
