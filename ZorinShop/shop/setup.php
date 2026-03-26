<?php
/**
 * ZorinShop - 초기 설정 스크립트
 * 브라우저에서 http://localhost/shop/setup.php 실행
 * 설치 완료 후 이 파일을 삭제하세요!
 */

define('DB_HOST', 'localhost');
define('DB_ROOT', 'root');

// root 비밀번호 없이 시도, 실패 시 소켓 시도
$conn = @new mysqli(DB_HOST, DB_ROOT, '');
if ($conn->connect_error) {
    $conn = @new mysqli(DB_HOST, DB_ROOT, '', '', 0, '/var/run/mysqld/mysqld.sock');
}
if ($conn->connect_error) {
    die("<div style='color:red;font-family:sans-serif;padding:20px'>
        <h3>DB 연결 실패</h3>
        <p>MySQL root 비밀번호가 필요합니다. 터미널에서 직접 실행하세요:</p>
        <code>mysql -u root -p &lt; /home/chan/Desktop/zorin-lamp/shop/setup_db.sql</code>
        <br><br>그 후 비밀번호를 직접 설정하고 재시도하거나, setup_db.sql을 직접 실행하세요.
    </div>");
}

$conn->set_charset("utf8mb4");

$errors = [];
$steps = [];

// 1. DB 생성
if ($conn->query("CREATE DATABASE IF NOT EXISTS zorinshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    $steps[] = "✅ 데이터베이스 'zorinshop' 생성 완료";
} else {
    $errors[] = "❌ DB 생성 실패: " . $conn->error;
}

$conn->select_db("zorinshop");

// 2. 사용자 생성
$conn->query("CREATE USER IF NOT EXISTS 'shop_user'@'localhost' IDENTIFIED BY 'shop_pass123'");
$conn->query("GRANT ALL PRIVILEGES ON zorinshop.* TO 'shop_user'@'localhost'");
$conn->query("FLUSH PRIVILEGES");
$steps[] = "✅ DB 사용자 'shop_user' 생성 완료";

// 3. 테이블 생성
$tables = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user','admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "categories" => "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "products" => "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        name VARCHAR(200) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        stock INT NOT NULL DEFAULT 0,
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "cart" => "CREATE TABLE IF NOT EXISTS cart (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "orders" => "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100),
        phone VARCHAR(20),
        address VARCHAR(300),
        total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
        status ENUM('pending','shipping','delivered','cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "order_items" => "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        $steps[] = "✅ 테이블 '$name' 생성 완료";
    } else {
        $errors[] = "❌ '$name' 테이블 생성 실패: " . $conn->error;
    }
}

// 4. 샘플 데이터 (중복 방지)
$user_count = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
if ($user_count == 0) {
    // 관리자 (비밀번호: admin123)
    $admin_pw = password_hash('admin123', PASSWORD_DEFAULT);
    $user_pw  = password_hash('user123', PASSWORD_DEFAULT);

    $conn->query("INSERT INTO users (name,email,password,role) VALUES ('관리자','admin@shop.com','$admin_pw','admin')");
    $conn->query("INSERT INTO users (name,email,password,role) VALUES ('홍길동','user@shop.com','$user_pw','user')");
    $steps[] = "✅ 기본 계정 생성 (admin@shop.com / admin123)";

    // 카테고리
    $conn->query("INSERT INTO categories (name,description) VALUES
        ('전자제품','스마트폰, 노트북, 태블릿 등'),
        ('의류','남성/여성 의류 및 액세서리'),
        ('식품','신선식품, 가공식품, 건강식품'),
        ('도서','국내외 도서, 전자책'),
        ('스포츠','운동기구, 스포츠 용품')");
    $steps[] = "✅ 카테고리 5개 삽입";

    // 상품 샘플
    $conn->query("INSERT INTO products (category_id,name,description,price,stock) VALUES
        (1,'스마트폰 Pro X','최신형 스마트폰. 6.7인치 AMOLED 디스플레이, 5000mAh 배터리',999000,50),
        (1,'노트북 UltraBook 15','인텔 i7 프로세서, 16GB RAM, 512GB SSD',1250000,30),
        (1,'무선 이어폰 AirBuds','노이즈 캔슬링, 30시간 배터리, 프리미엄 음질',189000,100),
        (1,'스마트워치 FitPro','심박수, 혈압 측정, GPS, 방수 기능',299000,75),
        (2,'캐주얼 티셔츠','고급 면 100%, 다양한 색상, 남녀공용',29000,200),
        (2,'청바지 슬림핏','스트레치 데님, 편안한 착용감',59000,150),
        (3,'유기농 그린티 세트','국내산 유기농 녹차, 50티백 세트',18000,300),
        (3,'프리미엄 견과류 믹스','아몬드, 호두, 캐슈넛 혼합, 500g',15000,250),
        (4,'Python 완벽 가이드','파이썬 입문부터 심화까지, 최신 개정판',32000,80),
        (4,'SQL 마스터','MySQL, PostgreSQL 실전 예제 포함',28000,60),
        (5,'요가 매트 프로','6mm 두께, 논슬립 표면, 환경 친화적 소재',35000,120),
        (5,'덤벨 세트 10kg','크롬 도금 덤벨, 조립식, 다용도 운동',45000,80)");
    $steps[] = "✅ 샘플 상품 12개 삽입";
} else {
    $steps[] = "ℹ️ 데이터가 이미 존재합니다. 샘플 데이터 삽입 건너뜀";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZorinShop 설치</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-7">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">🛒 ZorinShop 설치 마법사</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5>오류 발생</h5>
                            <?php foreach ($errors as $e): echo "<p class='mb-1'>$e</p>"; endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($steps as $step): ?>
                        <p class="mb-1"><?= $step ?></p>
                    <?php endforeach; ?>

                    <?php if (empty($errors)): ?>
                        <hr>
                        <div class="alert alert-success">
                            <h5>설치 완료!</h5>
                            <p class="mb-1"><strong>관리자 계정:</strong> admin@shop.com / admin123</p>
                            <p class="mb-0"><strong>일반 계정:</strong> user@shop.com / user123</p>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <a href="/shop/" class="btn btn-primary">🛍️ 쇼핑몰 열기</a>
                            <a href="/shop/admin/" class="btn btn-warning">⚙️ 관리자 패널</a>
                        </div>
                        <p class="text-danger small mt-3">⚠️ 보안을 위해 이 파일(setup.php)을 삭제하세요!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
