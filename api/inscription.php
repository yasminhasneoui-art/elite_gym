<?php
header('Content-Type: application/json');
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success'=>false,'message'=>'Méthode non autorisée.']));
}

$required = ['prenom','nom','email','tel','genre','username','mot_de_passe','programmes','abonnement'];
foreach ($required as $f) {
    if (empty($_POST[$f])) {
        die(json_encode(['success'=>false,'message'=>"Champ manquant : $f"]));
    }
}

$prenom        = trim($_POST['prenom']);
$nom           = trim($_POST['nom']);
$email         = trim($_POST['email']);
$tel           = trim($_POST['tel']);
$genre         = $_POST['genre'];
$username      = trim($_POST['username']);
$mdp           = $_POST['mot_de_passe'];   // mot de passe en clair
$programmes    = trim($_POST['programmes']);
$abonnement    = trim($_POST['abonnement']);
$mode_paiement = $_POST['mode_paiement'] ?? 'espèces';
$message       = trim($_POST['message'] ?? '');
$sante_type    = $_POST['sante_type'] ?? 'aucun';
$sante_detail  = trim($_POST['sante_detail'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die(json_encode(['success'=>false,'message'=>'Adresse email invalide.']));
}
if (strlen($mdp) < 4) {
    die(json_encode(['success'=>false,'message'=>'Mot de passe trop court (4 caractères min).']));
}
if (!in_array($genre, ['Homme','Femme'])) {
    die(json_encode(['success'=>false,'message'=>'Genre invalide.']));
}
if (!in_array($mode_paiement, ['espèces','carte','e-dinar_jeune'])) {
    $mode_paiement = 'espèces';
}

// Vérifier unicité
$dup = $pdo->prepare('SELECT id FROM membres WHERE username=? OR email=?');
$dup->execute([$username, $email]);
if ($dup->fetch()) {
    die(json_encode(['success'=>false,'message'=>'Nom d\'utilisateur ou email déjà utilisé.']));
}

// Métriques physiques
$poids    = !empty($_POST['poids'])          ? (float)$_POST['poids']          : null;
$taille   = !empty($_POST['taille'])         ? (float)$_POST['taille']         : null;
$objPoids = !empty($_POST['objectif_poids']) ? (float)$_POST['objectif_poids'] : null;
$imc      = null;
$calories = null;
if ($poids && $taille) {
    $imc      = round($poids / pow($taille / 100, 2), 2);
    $calories = round(10 * $poids + 6.25 * $taille - 5 * 25 + ($genre === 'Homme' ? 5 : -161));
}

$dateDebut        = date('Y-m-d');
$prochainPaiement = date('Y-m-d', strtotime('+1 month'));

try {
    $stmt = $pdo->prepare("
        INSERT INTO membres
          (username, mot_de_passe, prenom, nom, email, tel, genre,
           sante_type, sante_detail, programmes, abonnement, message,
           date_debut, prochain_paiement, statut,
           poids, taille, imc, calories_jour, objectif_poids)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,'actif',?,?,?,?,?)
    ");
    $stmt->execute([
        $username, $mdp, $prenom, $nom, $email, $tel, $genre,
        $sante_type, $sante_detail, $programmes, $abonnement, $message,
        $dateDebut, $prochainPaiement,
        $poids, $taille, $imc, $calories, $objPoids
    ]);
    $newId = $pdo->lastInsertId();

    // Premier paiement en attente
    $tarifs  = ['Essential'=>89,'Performance'=>149,'Élite'=>249,'Elite'=>249];
    $montant = $tarifs[$abonnement] ?? 89;
    $pdo->prepare("
        INSERT INTO paiements (membre_id, montant, date_paiement, mode_paiement, statut, note)
        VALUES (?,?,?,?,?,?)
    ")->execute([$newId, $montant, $dateDebut, $mode_paiement, 'en_attente', 'Premier paiement à l\'inscription']);

    echo json_encode(['success'=>true, 'redirect'=>'../public/login.html']);

} catch (PDOException $e) {
    die(json_encode(['success'=>false,'message'=>'Erreur DB : '.$e->getMessage()]));
}
