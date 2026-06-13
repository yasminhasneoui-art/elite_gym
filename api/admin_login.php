<?php
header('Content-Type: application/json');
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success'=>false,'message'=>'Méthode non autorisée.']));
}

$username = trim($_POST['username'] ?? '');
$mdp      = trim($_POST['mot_de_passe'] ?? '');

if (!$username || !$mdp) {
    die(json_encode(['success'=>false,'message'=>'Identifiant et mot de passe requis.']));
}

$stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ?');
$stmt->execute([$username]);
$admin = $stmt->fetch();

// Comparaison mot de passe en clair
if (!$admin || $admin['mot_de_passe'] !== $mdp) {
    http_response_code(401);
    die(json_encode(['success'=>false,'message'=>'Identifiant ou mot de passe incorrect.']));
}

$_SESSION['admin_id']  = $admin['id'];
$_SESSION['admin_nom'] = $admin['nom'];

echo json_encode(['success'=>true, 'redirect'=>'../admin/admin.php']);
