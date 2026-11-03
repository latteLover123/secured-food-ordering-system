<?php
// menu_search.php
// Tugas: FR2 (Search) dan SR3 (SQL Injection Prevention)

declare(strict_types=1);
require_once 'session.php';
require_once 'db.php';

// Wajib login untuk cari makanan
requireAuthentication();

$search = trim($_GET['q'] ?? '');
$menus = [];

if ($search !== '') {
    // GUNA PREPARED STATEMENTS (Jangan concatenate string!)
    $stmt = $pdo->prepare("SELECT id, restaurant_name, item_name, price FROM menus WHERE item_name LIKE ? AND is_available = 1");
    $stmt->execute(['%' . $search . '%']);
    $menus = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head><title>Search Menu</title></head>
<body>
    <h2>Cari Makanan</h2>
    <a href="main_module.php">Kembali ke Utama</a> | 
    <a href="logout.php">Logout</a>
    <br><br>

    <form method="GET">
        <input type="text" name="q" placeholder="Cari (cth: Nasi)" value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Cari</button>
    </form>

    <ul>
        <?php foreach ($menus as $m): ?>
            <li>
                <strong><?= htmlspecialchars($m['item_name']) ?></strong> 
                (<?= htmlspecialchars($m['restaurant_name']) ?>) - 
                RM <?= number_format($m['price'], 2) ?>
            </li>
        <?php endforeach; ?>
    </ul>
</body>
</html>