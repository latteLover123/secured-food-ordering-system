<?php
// db.php - Temporary PDO Database Wrapper
declare(strict_types=1);

$host    = '127.0.0.1';
$port    = '3306'; // Add your MariaDB port here
$db = 'schema.sql';
$user    = 'root';
$pass    = ''; // WampServer default root password is empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // Enforces true prepared statements against SQLi
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // Suppress raw database connection errors from the client
    die("Database connection failed. Please contact the administrator.");
}