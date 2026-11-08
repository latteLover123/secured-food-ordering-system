<?php
// main_module.php
declare(strict_types=1);
require_once 'session.php';
require_once 'db.php';

enforce_authenticated_session();

$username = $_SESSION['username'] ?? 'Pengguna';
$role = $_SESSION['role'] ?? 'user';

$stmt = $pdo->query("SELECT * FROM menus WHERE is_available = 1");
$menus = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Utama - Sistem Pesanan Makanan</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; margin: 0; padding: 20px; color: #334155; }
        .container { max-width: 800px; margin: auto; background: #ffffff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; margin-bottom: 25px; }
        .header h2 { margin: 0; color: #1e293b; font-weight: 600; }
        .logout-btn { background: #fee2e2; color: #ef4444; text-decoration: none; font-weight: 600; padding: 8px 16px; border-radius: 10px; transition: 0.3s; }
        .logout-btn:hover { background: #fca5a5; color: #fff; }
        
        .search-box { display: flex; gap: 10px; margin-bottom: 30px; }
        .search-box input { flex: 1; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: 'Poppins', sans-serif; outline: none; transition: 0.3s; }
        .search-box input:focus { border-color: #818cf8; box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.1); }
        .btn-search { background: #818cf8; color: white; border: none; padding: 0 25px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-search:hover { background: #6366f1; transform: translateY(-2px); }

        h3 { color: #1e293b; border-left: 5px solid #818cf8; padding-left: 10px; }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        .menu-card { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 16px; padding: 20px; transition: 0.3s; display: flex; flex-direction: column; justify-content: space-between; }
        .menu-card:hover { border-color: #818cf8; transform: translateY(-5px); box-shadow: 0 8px 20px rgba(129, 140, 248, 0.15); }
        .menu-title { font-size: 18px; font-weight: 600; color: #0f172a; margin: 0 0 5px 0; }
        .menu-resto { font-size: 13px; color: #64748b; margin-bottom: 15px; }
        .menu-price { font-size: 20px; font-weight: 600; color: #10b981; margin-bottom: 15px; }
        
        .order-form { display: flex; gap: 10px; align-items: center; }
        .order-form input[type="number"] { width: 60px; padding: 10px; border: 2px solid #e2e8f0; border-radius: 10px; text-align: center; font-family: 'Poppins', sans-serif; font-weight: 600; }
        .btn-order { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; flex: 1; transition: 0.3s; }
        .btn-order:hover { background: #059669; transform: scale(1.02); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Hai, <?= htmlspecialchars($username) ?>! 👋</h2>
            <a href="logout.php" class="logout-btn">Log Keluar</a>
        </div>

        <form action="menu_search.php" method="GET" class="search-box">
            <input type="text" name="q" placeholder="Nak makan apa hari ini? (Cth: Nasi)...">
            <button type="submit" class="btn-search">Cari 🔍</button>
        </form>

        <h3>Menu Pilihan</h3>
        
        <div class="menu-grid">
            <?php foreach ($menus as $menu): ?>
                <div class="menu-card">
                    <div>
                        <h4 class="menu-title"><?= htmlspecialchars($menu['item_name']) ?></h4>
                        <div class="menu-resto">📍 <?= htmlspecialchars($menu['restaurant_name']) ?></div>
                        <div class="menu-price">RM <?= number_format((float)$menu['price'], 2) ?></div>
                    </div>
                    <form action="place_order.php" method="POST" class="order-form">
                        <input type="hidden" name="menu_id" value="<?= $menu['id'] ?>">
                        <input type="hidden" name="address" value="123, Jalan Test, KL"> 
                        <input type="number" name="quantity" value="1" min="1" max="10">
                        <button type="submit" class="btn-order">Pesan</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>