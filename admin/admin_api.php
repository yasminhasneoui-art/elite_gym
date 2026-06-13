<?php
header('Content-Type: application/json');
require_once '../config/config.php';
requireAdmin();

$action = $_POST['action'] ?? '';

switch ($action) {

  // ── Mettre à jour le statut d'un membre ──
  case 'update_statut':
    $id     = (int)($_POST['id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    if (!$id || !in_array($statut, ['actif','en_attente','suspendu'])) {
      die(json_encode(['success'=>false,'message'=>'Données invalides.']));
    }
    $pdo->prepare("UPDATE membres SET statut=? WHERE id=?")->execute([$statut, $id]);
    echo json_encode(['success'=>true]);
    break;

  // ── Mettre à jour le paiement existant (UPDATE, pas INSERT) ──
  case 'add_paiement':
    $membre_id = (int)($_POST['membre_id'] ?? 0);
    $montant   = (float)($_POST['montant'] ?? 0);
    $date      = $_POST['date'] ?? date('Y-m-d');
    $mode      = $_POST['mode'] ?? 'espèces';
    $statut    = $_POST['statut'] ?? 'payé';
    $note      = trim($_POST['note'] ?? '');

    if (!$membre_id || $montant <= 0) {
      die(json_encode(['success'=>false,'message'=>'Montant invalide.']));
    }
    if (!in_array($mode, ['espèces','carte','e-dinar_jeune'])) {
      die(json_encode(['success'=>false,'message'=>'Mode de paiement invalide.']));
    }
    if (!in_array($statut, ['payé','en_attente','retard'])) {
      die(json_encode(['success'=>false,'message'=>'Statut invalide.']));
    }

    // Chercher la ligne la plus récente (en_attente ou retard) pour ce membre
    $existing = $pdo->prepare("
        SELECT id FROM paiements
        WHERE membre_id = ? AND statut IN ('en_attente','retard')
        ORDER BY date_paiement DESC
        LIMIT 1
    ");
    $existing->execute([$membre_id]);
    $row = $existing->fetch();

    if ($row) {
      // UPDATE la ligne existante
      $pdo->prepare("
          UPDATE paiements
          SET montant=?, date_paiement=?, mode_paiement=?, statut=?, note=?
          WHERE id=?
      ")->execute([$montant, $date, $mode, $statut, $note, $row['id']]);
    } else {
      // Aucune ligne en attente : INSERT nouvelle ligne (paiement supplémentaire)
      $pdo->prepare("
          INSERT INTO paiements (membre_id,montant,date_paiement,mode_paiement,statut,note)
          VALUES (?,?,?,?,?,?)
      ")->execute([$membre_id, $montant, $date, $mode, $statut, $note]);
    }

    // Si payé : mettre à jour prochain_paiement (+1 mois) et créer la prochaine échéance
    if ($statut === 'payé') {
      $nextDate = date('Y-m-d', strtotime($date . ' +1 month'));
      $pdo->prepare("UPDATE membres SET prochain_paiement=? WHERE id=?")->execute([$nextDate, $membre_id]);

      // Créer automatiquement la prochaine échéance en_attente
      $pdo->prepare("
          INSERT INTO paiements (membre_id,montant,date_paiement,mode_paiement,statut,note)
          VALUES (?,?,?,?,?,?)
      ")->execute([$membre_id, $montant, $nextDate, $mode, 'en_attente', 'Prochaine échéance']);
    }

    echo json_encode(['success'=>true]);
    break;

  // ── Ajouter une séance ──
  case 'add_seance':
    $membre_id = (int)($_POST['membre_id'] ?? 0);
    $date      = $_POST['date'] ?? date('Y-m-d');
    $heure     = $_POST['heure'] ?? date('H:i');
    $type      = $_POST['type'] ?? 'Libre';
    $duree     = (int)($_POST['duree'] ?? 60);
    $calories  = ($_POST['calories'] ?? '') !== '' ? (int)$_POST['calories'] : null;
    $note      = trim($_POST['note'] ?? '');

    if (!$membre_id) {
      die(json_encode(['success'=>false,'message'=>'Membre invalide.']));
    }

    $pdo->prepare("INSERT INTO seances (membre_id,date_seance,heure,duree_min,type,calories,note) VALUES (?,?,?,?,?,?,?)")
        ->execute([$membre_id, $date, $heure.':00', $duree, $type, $calories, $note]);

    // Mettre à jour les compteurs du membre
    $pdo->prepare("
      UPDATE membres SET
        nb_seances_total = nb_seances_total + 1,
        derniere_seance  = ?,
        nb_seances_mois  = (SELECT COUNT(*) FROM seances WHERE membre_id=? AND MONTH(date_seance)=MONTH(NOW()) AND YEAR(date_seance)=YEAR(NOW()))
      WHERE id=?
    ")->execute([$date, $membre_id, $membre_id]);

    echo json_encode(['success'=>true]);
    break;

  // ── Assigner un coach ──
  case 'assign_coach':
    $membre_id = (int)($_POST['membre_id'] ?? 0);
    $coach_id  = ($_POST['coach_id'] ?? '') !== '' ? (int)$_POST['coach_id'] : null;
    if (!$membre_id) {
      die(json_encode(['success'=>false,'message'=>'Membre invalide.']));
    }
    $pdo->prepare("UPDATE membres SET coach_id=? WHERE id=?")->execute([$coach_id, $membre_id]);
    echo json_encode(['success'=>true]);
    break;

  default:
    die(json_encode(['success'=>false,'message'=>'Action inconnue.']));
}
