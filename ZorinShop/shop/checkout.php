<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];

// 장바구니 조회
$stmt = $conn->prepare("
    SELECT c.quantity, p.id as product_id, p.name, p.price, p.stock
    FROM cart c JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($items)) {
    header("Location: /shop/cart.php");
    exit;
}

$total = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $items));

// 주문 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    if (!$name || !$phone || !$address) {
        $error = "모든 배송 정보를 입력해주세요.";
    } else {
        $conn->begin_transaction();
        try {
            // 주문 헤더 생성
            $stmt = $conn->prepare("INSERT INTO orders (user_id, name, phone, address, total_price, status) VALUES (?,?,?,?,'pending')");
            $stmt->bind_param("isssd", $user_id, $name, $phone, $address, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;

            // 주문 상세 및 재고 차감
            foreach ($items as $item) {
                $ins = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
                $ins->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                $ins->execute();

                $upd = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                $upd->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                $upd->execute();
            }

            // 장바구니 비우기
            $del = $conn->prepare("DELETE FROM cart WHERE user_id=?");
            $del->bind_param("i", $user_id);
            $del->execute();

            $conn->commit();
            alert("주문이 완료되었습니다! 주문번호: #" . $order_id, "/shop/orders.php");
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "주문 처리 중 오류가 발생했습니다.";
        }
    }
}

$user_stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>주문서 - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="/shop/"><i class="bi bi-shop"></i> ZorinShop</a>
    </div>
</nav>

<div class="container my-5">
    <h3 class="mb-4"><i class="bi bi-credit-card"></i> 주문서 작성</h3>
    <?php if (isset($error)): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

    <div class="row g-4">
        <div class="col-md-7">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-truck"></i> 배송 정보</h5>
                    <form method="POST" id="orderForm">
                        <div class="mb-3">
                            <label class="form-label">받는 분</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">연락처</label>
                            <input type="text" name="phone" class="form-control" required placeholder="010-0000-0000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">배송 주소</label>
                            <input type="text" name="address" class="form-control" required placeholder="주소를 입력하세요">
                        </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list-check"></i> 주문 상품</h5>
                    <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?></span>
                            <strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between pt-3">
                        <strong>합계</strong>
                        <strong class="price-tag fs-5"><?= formatPrice($total) ?></strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-wallet2"></i> 결제 정보</h5>
                    <p class="text-muted">결제 방법: 가상 결제 (테스트용)</p>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>최종 결제금액</strong>
                        <strong class="price-tag fs-4"><?= formatPrice($total) ?></strong>
                    </div>
                    <button type="submit" form="orderForm" class="btn btn-danger w-100 btn-lg">
                        <i class="bi bi-check-circle"></i> <?= formatPrice($total) ?> 결제하기
                    </button>
                    </form>
                    <a href="/shop/cart.php" class="btn btn-outline-secondary w-100 mt-2">뒤로 가기</a>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="text-center">
    <div class="container">
        <p class="mb-0">&copy; 2025 ZorinShop. Built with LAMP Stack on Zorin OS.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
