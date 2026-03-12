<?php
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function formatPrice($price) {
    return number_format($price) . '원';
}

function getCartCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['total'] ?? 0;
}

function alert($msg, $redirect = '') {
    echo "<script>alert('" . addslashes($msg) . "');";
    if ($redirect) echo "location.href='" . $redirect . "';";
    echo "</script>";
}
