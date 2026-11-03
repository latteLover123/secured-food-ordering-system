<?php
// place_order.php
// Tugas: FR3 (Place Order), SR6 (Server-side Integrity), SR3 (SQLi Prevention)

declare(strict_types=1);
require_once 'session.php';
require_once 'db.php';

requireAuthentication();
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Dapatkan input
    $menu_id = filter_input(INPUT_POST, 'menu_id', FILTER_VALIDATE_INT);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
    $address = trim($_POST['address'] ?? '');

    // 2. Semak validation (Asas - Person 3 akan upgrade)
    if (!$menu_id || !$quantity || $quantity <= 0 || empty($address)) {
        die("Input tidak sah. Sila lengkapkan maklumat.");
    }

    try {
        $pdo->beginTransaction();

        // 3. SERVER-SIDE PRICE CHECK (Mitigates Parameter Tampering)
        // JANGAN percaya harga dari client. Ambil dari DB.
        $stmt = $pdo->prepare("SELECT price FROM menus WHERE id = ? AND is_available = 1");
        $stmt->execute([$menu_id]);
        $menu = $stmt->fetch();

        if (!$menu) {
            throw new Exception("Menu tiada atau tidak tersedia.");
        }

        $unit_price = $menu['price'];
        $total_amount = $unit_price * $quantity;

        // 4. Masukkan data ke table `orders`
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, delivery_address, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $total_amount, $address]);
        $order_id = $pdo->lastInsertId();

        // 5. Masukkan data ke table `order_items`
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $menu_id, $quantity, $unit_price]);

        $pdo->commit();
        
        // Redirect ke page berjaya
        header("Location: main_module.php?msg=order_success");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        // Log error (Jangan tunjuk pada user)
        error_log("Order Error: " . $e->getMessage());
        die("Maaf, ralat berlaku semasa memproses pesanan anda.");
    }
}
?>