<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$category_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// 카테고리 목록
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);

// 상품 목록 쿼리
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.stock > 0";
$params = [];
$types = "";

if ($category_id > 0) {
    $sql .= " AND p.category_id = ?";
    $types .= "i";
    $params[] = $category_id;
}
if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    $types .= "ss";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY p.created_at DESC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$cart_count = isLoggedIn() ? getCartCount($conn, $_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZorinShop - 온라인 쇼핑몰</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/shop/"><i class="bi bi-shop"></i> ZorinShop</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <form class="d-flex mx-auto" action="/shop/" method="GET" style="width:40%">
                <input class="form-control me-2" type="search" name="search" placeholder="상품 검색..." value="<?= htmlspecialchars($search) ?>">
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            </form>
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/shop/cart.php">
                            <i class="bi bi-cart3"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?= $cart_count ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="/shop/orders.php"><i class="bi bi-bag"></i> 주문내역</a></li>
                    <?php if (isAdmin()): ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="/shop/admin/"><i class="bi bi-gear"></i> 관리자</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="/shop/logout.php"><i class="bi bi-box-arrow-right"></i> 로그아웃</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/shop/login.php"><i class="bi bi-person"></i> 로그인</a></li>
                    <li class="nav-item"><a class="btn btn-primary btn-sm ms-2" href="/shop/register.php">회원가입</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero -->
<div class="hero-section text-center">
    <div class="container">
        <h1 class="display-5 fw-bold"><i class="bi bi-stars"></i> ZorinShop</h1>
        <p class="lead">LAMP 스택으로 만든 온라인 쇼핑몰</p>
    </div>
</div>

<!-- Main Content -->
<div class="container mb-5">
    <!-- 카테고리 필터 -->
    <div class="mb-4">
        <a href="/shop/" class="badge bg-secondary text-decoration-none category-badge me-2 p-2 <?= $category_id == 0 ? 'active' : '' ?>">전체</a>
        <?php foreach ($categories as $cat): ?>
            <a href="/shop/?cat=<?= $cat['id'] ?>" class="badge bg-light text-dark text-decoration-none category-badge me-2 p-2 <?= $category_id == $cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- 상품 수 표시 -->
    <p class="text-muted mb-3">
        <?= $search ? '"' . htmlspecialchars($search) . '" 검색결과: ' : '' ?>
        총 <strong><?= count($products) ?></strong>개 상품
    </p>

    <!-- 상품 목록 -->
    <?php if (empty($products)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <p class="mt-3 text-muted">상품이 없습니다.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
            <?php foreach ($products as $product): ?>
                <div class="col">
                    <div class="card product-card h-100">
                        <?php if ($product['image'] && file_exists(__DIR__ . '/uploads/products/' . $product['image'])): ?>
                            <img src="/shop/uploads/products/<?= htmlspecialchars($product['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:200px">
                                <i class="bi bi-image text-muted display-4"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <span class="badge bg-light text-secondary mb-1"><?= htmlspecialchars($product['category_name'] ?? '') ?></span>
                            <h6 class="card-title"><?= htmlspecialchars($product['name']) ?></h6>
                            <p class="card-text text-muted small flex-grow-1"><?= htmlspecialchars(mb_substr($product['description'], 0, 50)) ?>...</p>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="price-tag"><?= formatPrice($product['price']) ?></span>
                                <small class="text-muted">재고: <?= $product['stock'] ?></small>
                            </div>
                            <a href="/shop/product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary btn-sm mt-2">
                                <i class="bi bi-eye"></i> 상세보기
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<footer class="text-center">
    <div class="container">
        <p class="mb-0">&copy; 2025 ZorinShop. Built with LAMP Stack on Zorin OS.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
