<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3307');
define('DB_NAME', 'elite_gym');
define('DB_USER', 'root');
define('DB_PASS', 'admin1234');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'message' => 'Erreur de connexion DB : ' . $e->getMessage()
    ]));
}

function requireLogin() {
    if (empty($_SESSION['membre_id'])) {
        header('Location: /public/login.html');
        exit;
    }
}

function requireAdmin() {
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/admin_login.html');
        exit;
    }
}
