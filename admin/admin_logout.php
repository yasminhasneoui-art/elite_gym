<?php
require_once '../config/config.php';
// Détruire uniquement la session admin
unset($_SESSION['admin_id'], $_SESSION['admin_nom']);
if (empty($_SESSION['membre_id'])) {
    session_destroy();
}
header('Location: admin_login.html');
exit;
