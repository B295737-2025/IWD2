<?php
$config = require '/home/s2809725/ica_private/config.php';

$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=s2809725_ica;charset=utf8mb4';
$user = $config['db_user'] ?? '';
$pass = $config['db_pass'] ?? '';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>