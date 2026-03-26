<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) { header("Location: /shop/"); exit; }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (!$name || !$email || !$password) {
        $error = "모든 필드를 입력해주세요.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "올바른 이메일 형식이 아닙니다.";
    } elseif (strlen($password) < 6) {
        $error = "비밀번호는 6자 이상이어야 합니다.";
    } elseif ($password !== $password2) {
        $error = "비밀번호가 일치하지 않습니다.";
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email=?");
        $chk->bind_param("s", $email);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $error = "이미 사용 중인 이메일입니다.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,'user')");
            $ins->bind_param("sss", $name, $email, $hash);
            $ins->execute();
            $success = "회원가입이 완료되었습니다! 로그인해주세요.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회원가입 - ZorinShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/shop/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="text-center mb-4">
                <a href="/shop/" class="text-decoration-none">
                    <h2 class="navbar-brand"><i class="bi bi-shop"></i> ZorinShop</h2>
                </a>
            </div>
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h4 class="card-title mb-4">회원가입</h4>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?> <a href="/shop/login.php">로그인하기</a></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">이름</label>
                            <input type="text" name="name" class="form-control" required placeholder="홍길동">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">이메일</label>
                            <input type="email" name="email" class="form-control" required placeholder="example@email.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">비밀번호 (6자 이상)</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">비밀번호 확인</label>
                            <input type="password" name="password2" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">회원가입</button>
                    </form>
                    <hr>
                    <p class="text-center mb-0">이미 계정이 있으신가요? <a href="/shop/login.php">로그인</a></p>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
