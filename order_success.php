<?php
require_once 'session.php';
enforce_authenticated_session();
$total = $_GET['total'] ?? '0.00';
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Pesanan Berjaya</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 20px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        h1 { color: #10b981; font-size: 50px; margin: 0; }
        h2 { color: #1e293b; }
        p { color: #64748b; font-size: 18px; }
        .btn { display: inline-block; margin-top: 20px; background: #818cf8; color: white; text-decoration: none; padding: 12px 25px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🎉</h1>
        <h2>Pesanan Berjaya!</h2>
        <p>Jumlah bayaran: <strong>RM <?= number_format((float)$total, 2) ?></strong></p>
        <a href="main_module.php" class="btn">Kembali ke Utama</a>
    </div>
</body>
</html>