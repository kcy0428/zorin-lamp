-- ZorinShop Database Setup Script
-- Run: mysql -u root -p < setup_db.sql

CREATE DATABASE IF NOT EXISTS zorinshop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE zorinshop;

-- DB 사용자 생성
CREATE USER IF NOT EXISTS 'shop_user'@'localhost' IDENTIFIED BY 'Shop@Pass1';
GRANT ALL PRIVILEGES ON zorinshop.* TO 'shop_user'@'localhost';
FLUSH PRIVILEGES;

-- 회원 테이블
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 카테고리 테이블
CREATE TABLE IF NOT EXISTS categories (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 상품 테이블
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock       INT NOT NULL DEFAULT 0,
    image       VARCHAR(255),
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 장바구니 테이블
CREATE TABLE IF NOT EXISTS cart (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 주문 헤더 테이블
CREATE TABLE IF NOT EXISTS orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    name        VARCHAR(100),
    phone       VARCHAR(20),
    address     VARCHAR(300),
    total_price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status      ENUM('pending','shipping','delivered','cancelled') DEFAULT 'pending',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 주문 상세 테이블
CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT NOT NULL,
    quantity   INT NOT NULL DEFAULT 1,
    price      DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- 샘플 데이터 삽입
-- =============================================

-- 관리자 계정 (비밀번호: admin123)
INSERT INTO users (name, email, password, role) VALUES
('관리자', 'admin@shop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- 일반 회원 (비밀번호: user123)
INSERT INTO users (name, email, password, role) VALUES
('홍길동', 'user@shop.com', '$2y$10$TKh8H1.PfbuNkLSAR', 'user');

-- 카테고리
INSERT INTO categories (name, description) VALUES
('전자제품', '스마트폰, 노트북, 태블릿 등'),
('의류', '남성/여성 의류 및 액세서리'),
('식품', '신선식품, 가공식품, 건강식품'),
('도서', '국내외 도서, 전자책'),
('스포츠', '운동기구, 스포츠 용품');

-- 샘플 상품
INSERT INTO products (category_id, name, description, price, stock) VALUES
(1, '스마트폰 Pro X', '최신형 스마트폰. 6.7인치 AMOLED 디스플레이, 5000mAh 배터리', 999000, 50),
(1, '노트북 UltraBook 15', '인텔 i7 프로세서, 16GB RAM, 512GB SSD, 가볍고 강력한 성능', 1250000, 30),
(1, '무선 이어폰 AirBuds', '노이즈 캔슬링, 30시간 배터리, 프리미엄 음질', 189000, 100),
(1, '스마트워치 FitPro', '심박수, 혈압 측정, GPS, 방수 기능', 299000, 75),
(2, '캐주얼 티셔츠', '고급 면 100%, 다양한 색상 보유, 남녀공용', 29000, 200),
(2, '청바지 슬림핏', '스트레치 데님, 편안한 착용감, 모던한 디자인', 59000, 150),
(3, '유기농 그린티 세트', '국내산 유기농 녹차, 50티백 세트', 18000, 300),
(3, '프리미엄 견과류 믹스', '아몬드, 호두, 캐슈넛 혼합, 500g', 15000, 250),
(4, 'Python 완벽 가이드', '파이썬 입문부터 심화까지, 최신 개정판', 32000, 80),
(4, 'SQL 마스터', 'MySQL, PostgreSQL 실전 예제 포함', 28000, 60),
(5, '요가 매트 프로', '6mm 두께, 논슬립 표면, 환경 친화적 소재', 35000, 120),
(5, '덤벨 세트 10kg', '크롬 도금 덤벨, 조립식, 다용도 운동', 45000, 80);
