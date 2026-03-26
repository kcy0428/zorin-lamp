<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireAdmin();

$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$message = '';
$error = '';

// 상품 등록/수정
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $name = sanitize($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    // 이미지 업로드
    $image = $_POST['existing_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed)) {
            $image = uniqid() . '.' . $ext;
            $dest = __DIR__ . '/../uploads/products/' . $image;
            move_uploaded_file($_FILES['image']['tmp_name'], $dest);
        }
    }

    if ($action === 'add') {
        $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("issdis", $category_id, $name, $description, $price, $stock, $image);
        // i=int, s=string, d=double
        $stmt->execute();
        $message = "상품이 등록되었습니다.";
    } elseif ($action === 'edit') {
        $id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, description=?, price=?, stock=?, image=? WHERE id=?");
        $stmt->bind_param("issdisi", $category_id, $name, $description, $price, $stock, $image, $id);
        $stmt->execute();
        $message = "상품이 수정되었습니다.";
    } elseif ($action === 'delete') {
        $id = (int)$_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "상품이 삭제되었습니다.";
    }
    header("Location: /shop/admin/products.php?msg=" . urlencode($message));
    exit;
}

if (isset($_GET['msg'])) $message = sanitize($_GET['msg']);

$products = $conn->query("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC")->fetch_all(MYSQLI_ASSOC);
$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", (int)$_GET['edit']);
    $stmt->execute();
    $edit_product = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>상품 관리 - ZorinShop</title>
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
        <a href="/shop/admin/products.php" class="active"><i class="bi bi-box-seam me-2"></i> 상품 관리</a>
        <a href="/shop/admin/orders.php"><i class="bi bi-bag me-2"></i> 주문 관리</a>
        <hr class="border-secondary mx-3">
        <a href="/shop/"><i class="bi bi-house me-2"></i> 쇼핑몰로</a>
        <a href="/shop/logout.php"><i class="bi bi-box-arrow-right me-2"></i> 로그아웃</a>
    </div>

    <div class="flex-grow-1 p-4">
        <h4 class="mb-4">상품 관리</h4>
        <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

        <!-- 상품 등록/수정 폼 -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $edit_product ? '상품 수정' : '상품 등록' ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?= $edit_product ? 'edit' : 'add' ?>">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
                        <input type="hidden" name="existing_image" value="<?= htmlspecialchars($edit_product['image'] ?? '') ?>">
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">상품명</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">카테고리</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">선택</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($edit_product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">이미지</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">설명</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">가격 (원)</label>
                            <input type="number" name="price" class="form-control" required min="0" value="<?= $edit_product['price'] ?? '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">재고</label>
                            <input type="number" name="stock" class="form-control" required min="0" value="<?= $edit_product['stock'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary"><?= $edit_product ? '수정 완료' : '등록' ?></button>
                        <?php if ($edit_product): ?>
                            <a href="/shop/admin/products.php" class="btn btn-secondary ms-2">취소</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- 상품 목록 -->
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5 class="mb-0">상품 목록 (<?= count($products) ?>개)</h5></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead class="table-light">
                        <tr><th>#</th><th>이미지</th><th>상품명</th><th>카테고리</th><th>가격</th><th>재고</th><th>관리</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= $p['id'] ?></td>
                            <td>
                                <?php if ($p['image'] && file_exists(__DIR__ . '/../uploads/products/' . $p['image'])): ?>
                                    <img src="/shop/uploads/products/<?= htmlspecialchars($p['image']) ?>" width="50" height="50" style="object-fit:cover;border-radius:4px">
                                <?php else: ?>
                                    <i class="bi bi-image text-muted fs-4"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($p['name']) ?></td>
                            <td><?= htmlspecialchars($p['cat_name'] ?? '') ?></td>
                            <td><?= number_format($p['price']) ?>원</td>
                            <td><?= $p['stock'] ?></td>
                            <td>
                                <a href="?edit=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-pencil"></i></a>
                                <form method="POST" class="d-inline" onsubmit="return confirm('삭제할까요?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
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
