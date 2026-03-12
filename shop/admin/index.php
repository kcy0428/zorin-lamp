<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

// 통계
$total_users    = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0];
$total_products = $conn->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
$total_orders   = $conn->query("SELECT COUNT(*) FROM orders")->fetch_row()[0];
$total_revenue  = $conn->query("SELECT COALESCE(SUM(total_price),0) FROM orders WHERE status != 'cancelled'")->fetch_row()[0];

// 최근 주문 5건
$recent_orders = $conn->query("SELECT o.*, u.name as user_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <!-- Sidebar -->
    <div class="admin-sidebar" style="width:220px;min-width:220px">
        <div class="p-3 border-bottom border-secondary">
            <h5 class="text-white mb-0"><i class="bi bi-shop"></i> ZorinShop</h5>
            <small class="text-muted">관리자 패널</small>
        </div>
        <a href="/shop/admin/" class="active"><i class="bi bi-speedometer2 me-2"></i> 대시보드</a>
        <a href="/shop/admin/products.php"><i class="bi bi-box-seam me-2"></i> 상품 관리</a>
        <a href="/shop/admin/orders.php"><i class="bi bi-bag me-2"></i> 주문 관리</a>
        <hr class="border-secondary mx-3">
        <a href="/shop/"><i class="bi bi-house me-2"></i> 쇼핑몰로</a>
        <a href="/shop/logout.php"><i class="bi bi-box-arrow-right me-2"></i> 로그아웃</a>
    </div>

    <!-- Main -->
    <div class="flex-grow-1 p-4">
        <h4 class="mb-4">대시보드</h4>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <i class="bi bi-people display-5 text-primary mb-2"></i>
                    <h3 class="fw-bold"><?= $total_users ?></h3>
                    <p class="text-muted mb-0">총 회원</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <i class="bi bi-box-seam display-5 text-success mb-2"></i>
                    <h3 class="fw-bold"><?= $total_products ?></h3>
                    <p class="text-muted mb-0">등록 상품</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <i class="bi bi-bag display-5 text-warning mb-2"></i>
                    <h3 class="fw-bold"><?= $total_orders ?></h3>
                    <p class="text-muted mb-0">총 주문</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card text-center p-3">
                    <i class="bi bi-currency-dollar display-5 text-danger mb-2"></i>
                    <h3 class="fw-bold"><?= number_format($total_revenue) ?>원</h3>
                    <p class="text-muted mb-0">총 매출</p>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between">
                <h5 class="mb-0">최근 주문</h5>
                <a href="/shop/admin/orders.php" class="btn btn-sm btn-outline-primary">전체보기</a>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>주문자</th><th>금액</th><th>상태</th><th>일시</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent_orders as $o): ?>
                        <tr>
                            <td><?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['user_name']) ?></td>
                            <td><?= number_format($o['total_price']) ?>원</td>
                            <td><span class="badge bg-warning text-dark"><?= $o['status'] ?></span></td>
                            <td><?= $o['created_at'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
