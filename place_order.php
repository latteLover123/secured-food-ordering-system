<?php
// place_order.php
declare(strict_types=1);
require_once 'session.php';
require_once 'db.php';

enforce_authenticated_session();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else if (isset($_SESSION['username'])) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$_SESSION['username']]);
    $u = $stmt->fetch();
    $user_id = $u ? $u['id'] : null;
} else {
    $user_id = null;
}

if (!$user_id) {
    die("Ralat: Sesi tamat. Sila log keluar dan log masuk semula.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $delivery_address = trim($_POST['address'] ?? '');

    if (!$menu_id || !$quantity || $quantity <= 0 || empty($delivery_address)) {
        die("Ralat: Input tidak sah.");
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT price FROM menus WHERE id = ? AND is_available = 1");
        $stmt->execute([$menu_id]);
        $menu = $stmt->fetch();

        if (!$menu) throw new Exception("Menu tiada.");

        $unit_price = (float)$menu['price'];
        $total_amount = $unit_price * $quantity;

        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $total_amount, $delivery_address]);
        $order_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $menu_id, $quantity, $unit_price]);

        $pdo->commit();
        header("Location: order_success.php?total=" . $total_amount);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("RALAT: " . $e->getMessage());
    }
}
?>