<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

// 주문 상태 변경
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = in_array($_POST['status'], ['pending','shipping','delivered','cancelled']) ? $_POST['status'] : 'pending';
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    header("Location: /shop/admin/orders.php?msg=상태가 변경되었습니다.");
    exit;
}

$message = isset($_GET['msg']) ? sanitize($_GET['msg']) : '';

$orders = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

$status_options = [
    'pending'   => '주문완료',
    'shipping'  => '배송중',
    'delivered' => '배송완료',
    'cancelled' => '취소됨',
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주문 관리 - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <div class="admin-sidebar" style="width:220px;min-width:220px">
        <div class="p-3 border-bottom border-secondary">
            <h5 class="text-white mb-0"><i class="bi bi-shop"></i> ZorinShop</h5>
            <small class="text-muted">관리자 패널</small>
        </div>
        <a href="/shop/admin/"><i class="bi bi-speedometer2 me-2"></i> 대시보드</a>
        <a href="/shop/admin/products.php"><i class="bi bi-box-seam me-2"></i> 상품 관리</a>
        <a href="/shop/admin/orders.php" class="active"><i class="bi bi-bag me-2"></i> 주문 관리</a>
        <hr class="border-secondary mx-3">
        <a href="/shop/"><i class="bi bi-house me-2"></i> 쇼핑몰로</a>
        <a href="/shop/logout.php"><i class="bi bi-box-arrow-right me-2"></i> 로그아웃</a>
    </div>

    <div class="flex-grow-1 p-4">
        <h4 class="mb-4">주문 관리</h4>
        <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>주문자</th><th>이메일</th><th>금액</th><th>배송지</th><th>상태</th><th>일시</th><th>상품</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($orders as $o):
                        $items_stmt = $conn->prepare("SELECT oi.quantity, p.name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?");
                        $items_stmt->bind_param("i", $o['id']);
                        $items_stmt->execute();
                        $o_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                    ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['user_name']) ?></td>
                            <td><?= htmlspecialchars($o['user_email']) ?></td>
                            <td><?= number_format($o['total_price']) ?>원</td>
                            <td><?= htmlspecialchars($o['address'] ?? '') ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-1">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <select name="status" class="form-select form-select-sm" style="width:100px">
                                        <?php foreach ($status_options as $val => $label): ?>
                                            <option value="<?= $val ?>" <?= $o['status'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">변경</button>
                                </form>
                            </td>
                            <td><?= $o['created_at'] ?></td>
                            <td>
                                <small><?php foreach ($o_items as $oi): echo htmlspecialchars($oi['name']) . ' x' . $oi['quantity'] . '<br>'; endforeach; ?></small>
                            </td>
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
