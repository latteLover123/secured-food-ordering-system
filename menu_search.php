<?php
// menu_search.php
declare(strict_types=1);
require_once 'session.php';
require_once 'db.php';

requireAuthentication();

$search = trim($_GET['q'] ?? '');
$menus = [];

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT id, restaurant_name, item_name, price FROM menus WHERE item_name LIKE ? AND is_available = 1");
    $stmt->execute(['%' . $search . '%']);
    $menus = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Hasil Carian</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; margin: 0; padding: 20px; color: #334155; }
        .container { max-width: 800px; margin: auto; background: #ffffff; padding: 30px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .nav-link { display: inline-block; margin-bottom: 20px; color: #818cf8; text-decoration: none; font-weight: 600; }
        .nav-link:hover { text-decoration: underline; }
        
        form { display: flex; gap: 10px; margin-bottom: 30px; }
        input[type="text"] { flex: 1; padding: 15px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-family: 'Poppins', sans-serif; outline: none; }
        input[type="text"]:focus { border-color: #818cf8; }
        .btn-search { background: #818cf8; color: white; border: none; padding: 0 25px; border-radius: 12px; font-weight: 600; cursor: pointer; }
        
        .result-item { background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 12px; padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center; }
        .result-title { font-size: 16px; font-weight: 600; color: #1e293b; margin: 0; }
        .result-resto { font-size: 13px; color: #64748b; }
        .result-price { font-size: 18px; font-weight: 600; color: #10b981; }
        
        .no-result { text-align: center; padding: 40px; color: #94a3b8; background: #f8fafc; border-radius: 12px; border: 2px dashed #cbd5e1; }
    </style>
</head>
<body>
    <div class="container">
        <a href="main_module.php" class="nav-link">⬅ Kembali ke Utama</a>
        <h2>Hasil Carian</h2>

        <form method="GET">
            <input type="text" name="q" placeholder="Cari makanan..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn-search">Cari</button>
        </form>

        <?php if (count($menus) > 0): ?>
            <?php foreach ($menus as $m): ?>
                <div class="result-item">
                    <div>
                        <p class="result-title"><?= htmlspecialchars($m['item_name']) ?></p>
                        <span class="result-resto">📍 <?= htmlspecialchars($m['restaurant_name']) ?></span>
                    </div>
                    <div class="result-price">RM <?= number_format((float)$m['price'], 2) ?></div>
                </div>
            <?php endforeach; ?>
        <?php elseif ($search !== ''): ?>
            <div class="no-result">
                <h3>😔 Maaf, tiada makanan ditemui untuk "<?= htmlspecialchars($search) ?>"</h3>
                <p>Cuba cari dengan kata kunci lain.</p>
            </div>
        <?php else: ?>
            <div class="no-result">
                <p>Sila taip kata kunci carian di atas.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>