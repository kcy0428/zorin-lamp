<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

function statusLabel($status) {
    $map = [
        'pending'   => ['label' => '주문완료', 'class' => 'bg-warning text-dark'],
        'shipping'  => ['label' => '배송중',   'class' => 'bg-info text-dark'],
        'delivered' => ['label' => '배송완료', 'class' => 'bg-success'],
        'cancelled' => ['label' => '취소됨',   'class' => 'bg-secondary'],
    ];
    return $map[$status] ?? ['label' => $status, 'class' => 'bg-secondary'];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주문내역 - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand" href="/shop/"><i class="bi bi-shop"></i> ZorinShop</a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item"><a class="nav-link" href="/shop/cart.php"><i class="bi bi-cart3"></i></a></li>
            <li class="nav-item"><a class="nav-link" href="/shop/logout.php">로그아웃</a></li>
        </ul>
    </div>
</nav>

<div class="container my-5">
    <h3 class="mb-4"><i class="bi bi-bag-check"></i> 주문내역</h3>

    <?php if (empty($orders)): ?>
        <div class="text-center py-5">
            <i class="bi bi-bag-x display-1 text-muted"></i>
            <p class="mt-3 text-muted">주문 내역이 없습니다.</p>
            <a href="/shop/" class="btn btn-primary">쇼핑하러 가기</a>
        </div>
    <?php else: ?>
        <?php foreach ($orders as $order):
            $stmt2 = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmt2->bind_param("i", $order['id']);
            $stmt2->execute();
            $order_items = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $s = statusLabel($order['status']);
        ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center bg-white">
                <div>
                    <strong>주문번호 #<?= $order['id'] ?></strong>
                    <small class="text-muted ms-2"><?= $order['created_at'] ?></small>
                </div>
                <span class="badge <?= $s['class'] ?> order-status-badge"><?= $s['label'] ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <?php foreach ($order_items as $oi): ?>
                            <div class="d-flex justify-content-between py-1">
                                <span><?= htmlspecialchars($oi['name']) ?> x <?= $oi['quantity'] ?></span>
                                <span><?= formatPrice($oi['price'] * $oi['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-4 text-end">
                        <p class="mb-1"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($order['address'] ?? '') ?></p>
                        <p class="mb-1"><i class="bi bi-telephone"></i> <?= htmlspecialchars($order['phone'] ?? '') ?></p>
                        <strong class="price-tag fs-5"><?= formatPrice($order['total_price']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
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
