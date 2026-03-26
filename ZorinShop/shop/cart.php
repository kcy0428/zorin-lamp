<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// 수량 변경
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update'])) {
        $cart_id = (int)$_POST['cart_id'];
        $qty = max(1, (int)$_POST['quantity']);
        $stmt = $conn->prepare("UPDATE cart SET quantity=? WHERE id=? AND user_id=?");
        $stmt->bind_param("iii", $qty, $cart_id, $user_id);
        $stmt->execute();
    } elseif (isset($_POST['delete'])) {
        $cart_id = (int)$_POST['cart_id'];
        $stmt = $conn->prepare("DELETE FROM cart WHERE id=? AND user_id=?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
    } elseif (isset($_POST['clear'])) {
        $stmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    header("Location: /shop/cart.php");
    exit;
}

// 장바구니 목록
$stmt = $conn->prepare("
    SELECT c.id, c.quantity, p.id as product_id, p.name, p.price, p.image, p.stock
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$total = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items));
$cart_count = getCartCount($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>장바구니 - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/shop/"><i class="bi bi-shop"></i> ZorinShop</a>
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item"><a class="nav-link" href="/shop/orders.php"><i class="bi bi-bag"></i> 주문내역</a></li>
            <li class="nav-item"><a class="nav-link" href="/shop/logout.php">로그아웃</a></li>
        </ul>
    </div>
</nav>

<div class="container my-5">
    <h3 class="mb-4"><i class="bi bi-cart3"></i> 장바구니</h3>

    <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x display-1 text-muted"></i>
            <p class="mt-3 text-muted">장바구니가 비어있습니다.</p>
            <a href="/shop/" class="btn btn-primary">쇼핑 계속하기</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <?php foreach ($items as $item): ?>
                            <div class="cart-item">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:70px;height:70px;overflow:hidden;border-radius:8px;background:#f0f0f0" class="d-flex align-items-center justify-content-center flex-shrink-0">
                                        <?php if ($item['image'] && file_exists(__DIR__ . '/uploads/products/' . $item['image'])): ?>
                                            <img src="/shop/uploads/products/<?= htmlspecialchars($item['image']) ?>" style="width:100%;height:100%;object-fit:cover">
                                        <?php else: ?>
                                            <i class="bi bi-image text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                        <span class="price-tag"><?= formatPrice($item['price']) ?></span>
                                    </div>
                                    <form method="POST" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                        <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $item['stock'] ?>" class="form-control form-control-sm" style="width:65px">
                                        <button type="submit" name="update" class="btn btn-sm btn-outline-secondary">수정</button>
                                        <button type="submit" name="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('삭제할까요?')"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <div class="text-end" style="min-width:90px">
                                        <strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <form method="POST" class="mt-3">
                            <button type="submit" name="clear" class="btn btn-sm btn-outline-danger" onclick="return confirm('전체 삭제할까요?')">
                                <i class="bi bi-trash"></i> 전체 삭제
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">주문 요약</h5>
                        <div class="d-flex justify-content-between mb-2">
                            <span>상품금액</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>배송비</span>
                            <span class="text-success">무료</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>총 합계</strong>
                            <strong class="price-tag fs-5"><?= formatPrice($total) ?></strong>
                        </div>
                        <a href="/shop/checkout.php" class="btn btn-primary w-100 btn-lg">
                            <i class="bi bi-credit-card"></i> 주문하기
                        </a>
                    </div>
                </div>
            </div>
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
