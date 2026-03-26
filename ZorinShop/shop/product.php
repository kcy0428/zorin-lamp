<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: /shop/"); exit; }

$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) { header("Location: /shop/"); exit; }

// 장바구니 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cart'])) {
    requireLogin();
    $qty = max(1, (int)$_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    $chk = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id=? AND product_id=?");
    $chk->bind_param("ii", $user_id, $id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();

    if ($existing) {
        $new_qty = $existing['quantity'] + $qty;
        $upd = $conn->prepare("UPDATE cart SET quantity=? WHERE id=?");
        $upd->bind_param("ii", $new_qty, $existing['id']);
        $upd->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?,?,?)");
        $ins->bind_param("iii", $user_id, $id, $qty);
        $ins->execute();
    }
    alert("장바구니에 추가되었습니다!", "/shop/cart.php");
    exit;
}

$cart_count = isLoggedIn() ? getCartCount($conn, $_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/shop/"><i class="bi bi-shop"></i> ZorinShop</a>
        <ul class="navbar-nav ms-auto align-items-center">
            <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/shop/cart.php">
                        <i class="bi bi-cart3"></i>
                        <?php if ($cart_count > 0): ?><span class="badge bg-danger rounded-pill"><?= $cart_count ?></span><?php endif; ?>
                    </a>
                </li>
                <li class="nav-item"><a class="nav-link" href="/shop/logout.php">로그아웃</a></li>
            <?php else: ?>
                <li class="nav-item"><a class="nav-link" href="/shop/login.php">로그인</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/shop/">홈</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <div class="col-md-5">
            <?php if ($product['image'] && file_exists(__DIR__ . '/uploads/products/' . $product['image'])): ?>
                <img src="/shop/uploads/products/<?= htmlspecialchars($product['image']) ?>" class="img-fluid rounded shadow" alt="<?= htmlspecialchars($product['name']) ?>">
            <?php else: ?>
                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height:350px">
                    <i class="bi bi-image text-muted display-1"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-md-7">
            <span class="badge bg-secondary mb-2"><?= htmlspecialchars($product['category_name'] ?? '') ?></span>
            <h2 class="fw-bold"><?= htmlspecialchars($product['name']) ?></h2>
            <h3 class="price-tag my-3"><?= formatPrice($product['price']) ?></h3>
            <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
            <p class="text-muted">재고: <strong><?= $product['stock'] ?></strong>개</p>

            <?php if ($product['stock'] > 0): ?>
                <form method="POST">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <label class="fw-bold">수량:</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" class="form-control" style="width:80px">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="add_cart" class="btn btn-primary btn-lg">
                            <i class="bi bi-cart-plus"></i> 장바구니 추가
                        </button>
                        <a href="/shop/cart.php" class="btn btn-outline-secondary btn-lg">
                            <i class="bi bi-cart3"></i> 장바구니 보기
                        </a>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">품절된 상품입니다.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<footer class="text-center mt-5">
    <div class="container">
        <p class="mb-0">&copy; 2025 ZorinShop. Built with LAMP Stack on Zorin OS.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
