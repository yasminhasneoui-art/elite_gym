<?php
require_once '../config/config.php';

// ── Récupération données ──
$membres = $pdo->query("
    SELECT m.*, c.prenom AS coach_prenom, c.nom AS coach_nom,
           (SELECT COUNT(*) FROM seances WHERE membre_id=m.id AND MONTH(date_seance)=MONTH(NOW()) AND YEAR(date_seance)=YEAR(NOW())) AS seances_mois,
           (SELECT COUNT(*) FROM seances WHERE membre_id=m.id) AS seances_total,
           (SELECT statut FROM paiements WHERE membre_id=m.id ORDER BY date_paiement DESC LIMIT 1) AS dernier_paiement_statut
    FROM membres m
    LEFT JOIN coachs c ON m.coach_id = c.id
    ORDER BY m.created_at DESC
")->fetchAll();

$coachs = $pdo->query("SELECT * FROM coachs ORDER BY prenom")->fetchAll();

$stats = $pdo->query("
    SELECT
      (SELECT COUNT(*) FROM membres) AS total_membres,
      (SELECT COUNT(*) FROM membres WHERE statut='actif') AS membres_actifs,
      (SELECT COUNT(*) FROM seances WHERE MONTH(date_seance)=MONTH(NOW())) AS seances_mois,
      (SELECT COALESCE(SUM(montant),0) FROM paiements WHERE MONTH(date_paiement)=MONTH(NOW()) AND statut='payé') AS ca_mois
")->fetch();

$tarifs = ['Essential'=>89,'Performance'=>149,'Elite'=>249,'Élite'=>249];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Élite Gym — Administration</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--white:#fff;--cream:#faf8f4;--dark:#1c1712;--mid:#4a4035;--muted:#9a8a78;--gold:#c9a84c;--gold2:#e8c96a;--border:#e8e0d4;--shadow:0 4px 32px rgba(28,23,18,.10);--sidebar:220px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{background:var(--cream);color:var(--dark);font-family:'DM Sans',sans-serif;display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:var(--sidebar);background:var(--dark);position:fixed;top:0;left:0;bottom:0;display:flex;flex-direction:column;padding:0;z-index:50;}
.sb-logo{padding:28px 24px;border-bottom:1px solid rgba(255,255,255,.07);}
.sb-logo a{font-family:'Montserrat',sans-serif;font-weight:900;font-size:18px;letter-spacing:3px;text-transform:uppercase;color:var(--white);text-decoration:none;}
.sb-logo a span{color:var(--gold);}
.sb-tag{font-size:9px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.25);margin-top:4px;display:block;}
.sb-nav{flex:1;padding:24px 0;}
.sb-section{font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.2);padding:0 24px;margin:20px 0 8px;}
.sb-link{display:flex;align-items:center;gap:12px;padding:11px 24px;color:rgba(255,255,255,.5);font-size:13px;font-weight:500;text-decoration:none;transition:all .2s;cursor:pointer;border:none;background:none;width:100%;text-align:left;}
.sb-link:hover,.sb-link.active{color:var(--white);background:rgba(255,255,255,.05);}
.sb-link.active{border-left:3px solid var(--gold);}
.sb-link .icon{font-size:16px;width:20px;text-align:center;}
.sb-bottom{padding:20px 24px;border-top:1px solid rgba(255,255,255,.07);}
.sb-user{font-size:12px;color:rgba(255,255,255,.35);margin-bottom:12px;}
.sb-user strong{color:rgba(255,255,255,.7);display:block;font-size:13px;}
.sb-logout{display:block;text-align:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.5);padding:9px;border-radius:4px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;}
.sb-logout:hover{border-color:rgba(230,57,70,.4);color:#fc6b77;}

/* MAIN */
.main{margin-left:var(--sidebar);flex:1;padding:32px 40px;min-height:100vh;}
.page{display:none;}
.page.active{display:block;}
.page-header{margin-bottom:32px;}
.page-tag{font-size:10px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:6px;}
.page-title{font-family:'Montserrat',sans-serif;font-weight:900;font-size:28px;color:var(--dark);}
.page-sub{font-size:13px;color:var(--muted);margin-top:4px;}

/* STATS CARDS */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:32px;}
.stat-card{background:var(--white);border:1px solid var(--border);border-radius:8px;padding:24px;box-shadow:var(--shadow);}
.stat-card-num{font-family:'Montserrat',sans-serif;font-weight:900;font-size:36px;color:var(--gold);line-height:1;}
.stat-card-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:6px;}
.stat-card-sub{font-size:12px;color:var(--muted);margin-top:4px;}

/* TABLE */
.card{background:var(--white);border:1px solid var(--border);border-radius:8px;padding:24px;box-shadow:var(--shadow);margin-bottom:20px;}
.card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap;}
.card-title{font-family:'Montserrat',sans-serif;font-weight:900;font-size:13px;letter-spacing:2px;text-transform:uppercase;color:var(--dark);}
.search-box{padding:9px 14px;border:1px solid var(--border);border-radius:4px;font-size:13px;font-family:'DM Sans',sans-serif;outline:none;width:220px;transition:border .2s;}
.search-box:focus{border-color:var(--gold);}

table{width:100%;border-collapse:collapse;}
thead tr{border-bottom:1px solid rgba(201,168,76,.25);}
thead th{text-align:left;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);padding:0 10px 12px;font-family:'Montserrat',sans-serif;}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:var(--cream);}
tbody td{padding:12px 10px;font-size:13px;color:var(--mid);}
td.bold{color:var(--dark);font-weight:600;}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;font-size:9px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;border-radius:12px;}
.badge-green{background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.2);}
.badge-orange{background:rgba(245,158,11,.1);color:#f59e0b;border:1px solid rgba(245,158,11,.2);}
.badge-red{background:rgba(230,57,70,.1);color:#e63946;border:1px solid rgba(230,57,70,.2);}
.badge-gold{background:rgba(201,168,76,.12);color:var(--gold);border:1px solid rgba(201,168,76,.3);}
.badge-blue{background:rgba(59,130,246,.1);color:#3b82f6;border:1px solid rgba(59,130,246,.2);}

/* ACTION BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;font-size:11px;font-weight:700;border-radius:4px;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;text-decoration:none;border:none;}
.btn-gold{background:var(--gold);color:var(--dark);}
.btn-gold:hover{background:var(--gold2);}
.btn-outline{background:transparent;color:var(--mid);border:1px solid var(--border);}
.btn-outline:hover{border-color:var(--gold);color:var(--gold);}
.btn-green{background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.25);}
.btn-green:hover{background:rgba(34,197,94,.2);}
.btn-red{background:rgba(230,57,70,.1);color:#e63946;border:1px solid rgba(230,57,70,.25);}
.btn-red:hover{background:rgba(230,57,70,.2);}
.btn-sm{padding:5px 10px;font-size:10px;}
.actions{display:flex;gap:6px;flex-wrap:wrap;}

/* MODAL */
.modal-bg{display:none;position:fixed;inset:0;background:rgba(28,23,18,.7);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(3px);}
.modal-bg.open{display:flex;}
.modal{background:var(--white);border-radius:10px;width:100%;max-width:540px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 64px rgba(0,0,0,.3);}
.modal-head{padding:24px 28px 0;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding-bottom:16px;}
.modal-title{font-family:'Montserrat',sans-serif;font-weight:900;font-size:16px;color:var(--dark);}
.modal-close{background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted);padding:4px;transition:color .2s;}
.modal-close:hover{color:var(--dark);}
.modal-body{padding:24px 28px;}
.modal-footer{padding:16px 28px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px;}

.field{margin-bottom:16px;}
.field label{display:block;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:7px;}
.field input,.field select,.field textarea{width:100%;background:var(--cream);border:1px solid var(--border);color:var(--dark);padding:11px 14px;font-family:'DM Sans',sans-serif;font-size:14px;outline:none;transition:border .2s;border-radius:4px;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--gold);}
.field textarea{resize:vertical;min-height:80px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}

/* PAYMENT MODE ICONS */
.pay-mode{display:flex;align-items:center;gap:6px;font-size:12px;}
.pay-mode-icon{font-size:14px;}

/* TOAST */
.toast{position:fixed;bottom:32px;right:32px;background:var(--dark);color:var(--white);padding:14px 22px;border-radius:6px;font-size:13px;font-weight:600;box-shadow:0 8px 24px rgba(0,0,0,.3);border-left:4px solid var(--gold);z-index:999;transform:translateY(20px);opacity:0;transition:all .3s;pointer-events:none;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.error{border-color:#e63946;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-logo">
    <a href="../public/index.html">ÉLITE<span>GYM</span></a>
    <span class="sb-tag">Administration</span>
  </div>
  <nav class="sb-nav">
    <span class="sb-section">Tableau de bord</span>
    <button class="sb-link active" onclick="showPage('dashboard',this)"><span class="icon">📊</span> Vue d'ensemble</button>

    <span class="sb-section">Membres</span>
    <button class="sb-link" onclick="showPage('membres',this)"><span class="icon">👥</span> Tous les membres</button>
    <button class="sb-link" onclick="showPage('paiements',this)"><span class="icon">💳</span> Paiements</button>
    <button class="sb-link" onclick="showPage('seances',this)"><span class="icon">🗓</span> Séances</button>
  </nav>
  <div class="sb-bottom">
    <div class="sb-user">Connecté en tant que<strong><?= htmlspecialchars($_SESSION['admin_nom']) ?></strong></div>
    <a href="admin_logout.php" class="sb-logout">Déconnexion →</a>
  </div>
</aside>

<!-- MAIN -->
<main class="main">

  <!-- PAGE: DASHBOARD -->
  <div class="page active" id="page-dashboard">
    <div class="page-header">
      <p class="page-tag">Vue d'ensemble</p>
      <h1 class="page-title">Tableau de bord</h1>
      <p class="page-sub">Bienvenue, <?= htmlspecialchars($_SESSION['admin_nom']) ?>. Voici l'état actuel de la salle.</p>
    </div>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-card-num"><?= $stats['total_membres'] ?></div>
        <div class="stat-card-label">Total membres</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-num"><?= $stats['membres_actifs'] ?></div>
        <div class="stat-card-label">Membres actifs</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-num"><?= $stats['seances_mois'] ?></div>
        <div class="stat-card-label">Séances ce mois</div>
      </div>
      <div class="stat-card">
        <div class="stat-card-num"><?= number_format($stats['ca_mois'], 0) ?></div>
        <div class="stat-card-label">CA ce mois (DT)</div>
        <div class="stat-card-sub">Paiements reçus</div>
      </div>
    </div>

    <!-- Derniers membres -->
    <div class="card">
      <div class="card-head">
        <span class="card-title">Derniers membres inscrits</span>
        <button class="btn btn-gold" onclick="showPage('membres',document.querySelector('[onclick*=membres]'))">Voir tous →</button>
      </div>
      <table>
        <thead><tr><th>Nom</th><th>Abonnement</th><th>Statut</th><th>Paiement</th><th>Inscrit le</th></tr></thead>
        <tbody>
          <?php foreach(array_slice($membres,0,8) as $m): ?>
          <tr>
            <td class="bold"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></td>
            <td><?= htmlspecialchars($m['abonnement']) ?></td>
            <td><span class="badge <?= $m['statut']==='actif'?'badge-green':($m['statut']==='en_attente'?'badge-orange':'badge-red') ?>"><?= $m['statut'] ?></span></td>
            <td><span class="badge <?= $m['dernier_paiement_statut']==='payé'?'badge-green':($m['dernier_paiement_statut']==='en_attente'?'badge-orange':($m['dernier_paiement_statut']==='retard'?'badge-red':'badge-gold')) ?>"><?= $m['dernier_paiement_statut'] ?? 'aucun' ?></span></td>
            <td><?= date('d/m/Y', strtotime($m['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PAGE: MEMBRES -->
  <div class="page" id="page-membres">
    <div class="page-header">
      <p class="page-tag">Gestion</p>
      <h1 class="page-title">Tous les membres</h1>
    </div>
    <div class="card">
      <div class="card-head">
        <span class="card-title"><?= count($membres) ?> membres</span>
        <input type="text" class="search-box" placeholder="🔍 Rechercher..." oninput="filterTable(this,'membres-tbody')">
      </div>
      <table>
        <thead>
          <tr><th>Membre</th><th>Abonnement</th><th>Coach</th><th>Séances/mois</th><th>Statut</th><th>Paiement</th><th>Actions</th></tr>
        </thead>
        <tbody id="membres-tbody">
          <?php foreach($membres as $m): ?>
          <tr data-search="<?= strtolower(htmlspecialchars($m['prenom'].' '.$m['nom'].' '.$m['email'].' '.$m['abonnement'])) ?>">
            <td>
              <div class="bold"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?></div>
              <div style="font-size:11px;color:var(--muted)"><?= htmlspecialchars($m['email']) ?></div>
            </td>
            <td><span class="badge badge-gold"><?= htmlspecialchars($m['abonnement']) ?></span></td>
            <td><?= $m['coach_prenom'] ? htmlspecialchars($m['coach_prenom'].' '.$m['coach_nom']) : '<span style="color:var(--muted)">—</span>' ?></td>
            <td style="text-align:center"><?= $m['seances_mois'] ?></td>
            <td>
              <select class="statut-select" onchange="updateStatut(<?= $m['id'] ?>,this.value)" style="font-size:11px;padding:4px 8px;border:1px solid var(--border);border-radius:4px;background:var(--cream);cursor:pointer;">
                <option value="actif" <?= $m['statut']==='actif'?'selected':'' ?>>✅ Actif</option>
                <option value="en_attente" <?= $m['statut']==='en_attente'?'selected':'' ?>>⏳ En attente</option>
                <option value="suspendu" <?= $m['statut']==='suspendu'?'selected':'' ?>>🚫 Suspendu</option>
              </select>
            </td>
            <td><span class="badge <?= $m['dernier_paiement_statut']==='payé'?'badge-green':($m['dernier_paiement_statut']==='en_attente'?'badge-orange':($m['dernier_paiement_statut']==='retard'?'badge-red':'badge-gold')) ?>"><?= $m['dernier_paiement_statut'] ?? 'aucun' ?></span></td>
            <td>
              <div class="actions">
                <button class="btn btn-green btn-sm" onclick="openPayModal(<?= $m['id'] ?>,'<?= htmlspecialchars(addslashes($m['prenom'].' '.$m['nom'])) ?>',<?= $tarifs[$m['abonnement']] ?? 89 ?>)">💳 Paiement</button>
                <button class="btn btn-gold btn-sm" onclick="openSeanceModal(<?= $m['id'] ?>,'<?= htmlspecialchars(addslashes($m['prenom'].' '.$m['nom'])) ?>')">🗓 Séance</button>
                <button class="btn btn-outline btn-sm" onclick="openCoachModal(<?= $m['id'] ?>,<?= (int)$m['coach_id'] ?>)">👤 Coach</button>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PAGE: PAIEMENTS -->
  <div class="page" id="page-paiements">
    <div class="page-header">
      <p class="page-tag">Finances</p>
      <h1 class="page-title">Historique paiements</h1>
    </div>
    <div class="card">
      <div class="card-head">
        <span class="card-title">Tous les paiements</span>
        <input type="text" class="search-box" placeholder="🔍 Rechercher..." oninput="filterTable(this,'paie-tbody')">
      </div>
      <table>
        <thead><tr><th>Membre</th><th>Montant</th><th>Mode</th><th>Date</th><th>Statut</th><th>Note</th></tr></thead>
        <tbody id="paie-tbody">
          <?php
          $allPayments = $pdo->query("
            SELECT p.*, m.prenom, m.nom
            FROM paiements p
            JOIN membres m ON m.id = p.membre_id
            ORDER BY p.date_paiement DESC
          ")->fetchAll();
          foreach($allPayments as $p):
            $modeIcon = ['espèces'=>'💵','carte'=>'💳','e-dinar_jeune'=>'📱'][$p['mode_paiement']] ?? '💰';
          ?>
          <tr data-search="<?= strtolower(htmlspecialchars($p['prenom'].' '.$p['nom'])) ?>">
            <td class="bold"><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></td>
            <td class="bold"><?= number_format($p['montant'],0) ?> DT</td>
            <td><span class="pay-mode"><?= $modeIcon ?> <?= htmlspecialchars($p['mode_paiement']) ?></span></td>
            <td><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
            <td><span class="badge <?= $p['statut']==='payé'?'badge-green':($p['statut']==='en_attente'?'badge-orange':'badge-red') ?>"><?= $p['statut'] ?></span></td>
            <td style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($p['note'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- PAGE: SÉANCES -->
  <div class="page" id="page-seances">
    <div class="page-header">
      <p class="page-tag">Suivi</p>
      <h1 class="page-title">Historique séances</h1>
    </div>
    <div class="card">
      <div class="card-head">
        <span class="card-title">Toutes les séances</span>
        <input type="text" class="search-box" placeholder="🔍 Rechercher..." oninput="filterTable(this,'seances-tbody')">
      </div>
      <table>
        <thead><tr><th>Membre</th><th>Date</th><th>Heure</th><th>Type</th><th>Durée</th><th>Calories</th><th>Note</th></tr></thead>
        <tbody id="seances-tbody">
          <?php
          $allSeances = $pdo->query("
            SELECT s.*, m.prenom, m.nom
            FROM seances s
            JOIN membres m ON m.id = s.membre_id
            ORDER BY s.date_seance DESC, s.heure DESC
            LIMIT 200
          ")->fetchAll();
          foreach($allSeances as $s):
          ?>
          <tr data-search="<?= strtolower(htmlspecialchars($s['prenom'].' '.$s['nom'].' '.$s['type'])) ?>">
            <td class="bold"><?= htmlspecialchars($s['prenom'].' '.$s['nom']) ?></td>
            <td><?= date('d/m/Y', strtotime($s['date_seance'])) ?></td>
            <td><?= date('H:i', strtotime($s['heure'])) ?></td>
            <td><span class="badge badge-blue"><?= htmlspecialchars($s['type']) ?></span></td>
            <td><?= $s['duree_min'] ?> min</td>
            <td><?= $s['calories'] ? $s['calories'].' kcal' : '—' ?></td>
            <td style="font-size:12px;color:var(--muted);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($s['note'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- ╔═══════════════════ MODALS ═══════════════════╗ -->

<!-- MODAL: PAIEMENT -->
<div class="modal-bg" id="payModal">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">💳 Enregistrer un paiement</span>
      <button class="modal-close" onclick="closeModal('payModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:20px">Membre : <strong id="pay-member-name" style="color:var(--dark)"></strong></p>
      <input type="hidden" id="pay-member-id">
      <div class="row2">
        <div class="field">
          <label>Montant (DT)</label>
          <input type="number" id="pay-montant" min="1" step="1" placeholder="89">
        </div>
        <div class="field">
          <label>Date de paiement</label>
          <input type="date" id="pay-date">
        </div>
      </div>
      <div class="field">
        <label>Mode de paiement</label>
        <select id="pay-mode">
          <option value="espèces">💵 Espèces</option>
          <option value="carte">💳 Carte bancaire</option>
          <option value="e-dinar_jeune">📱 E-Dinar Jeune</option>
        </select>
      </div>
      <div class="field">
        <label>Statut</label>
        <select id="pay-statut">
          <option value="payé">✅ Payé</option>
          <option value="en_attente">⏳ En attente</option>
          <option value="retard">⚠️ Retard</option>
        </select>
      </div>
      <div class="field">
        <label>Note (optionnel)</label>
        <textarea id="pay-note" placeholder="Ex : mois de mars, renouvellement..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('payModal')">Annuler</button>
      <button class="btn btn-gold" onclick="submitPaiement()">Enregistrer →</button>
    </div>
  </div>
</div>

<!-- MODAL: SÉANCE -->
<div class="modal-bg" id="seanceModal">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">🗓 Enregistrer une séance</span>
      <button class="modal-close" onclick="closeModal('seanceModal')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:20px">Membre : <strong id="seance-member-name" style="color:var(--dark)"></strong></p>
      <input type="hidden" id="seance-member-id">
      <div class="row2">
        <div class="field">
          <label>Date de séance</label>
          <input type="date" id="seance-date">
        </div>
        <div class="field">
          <label>Heure</label>
          <input type="time" id="seance-heure">
        </div>
      </div>
      <div class="row2">
        <div class="field">
          <label>Type de séance</label>
          <select id="seance-type">
            <option value="Libre">🏋️ Libre</option>
            <option value="Musculation">💪 Musculation</option>
            <option value="CrossFit">🔥 CrossFit</option>
            <option value="Boxe">🥊 Boxe</option>
            <option value="Yoga & Récupération">🧘 Yoga & Récup</option>
            <option value="Coaching Personnel">👤 Coaching Perso</option>
            <option value="Cardio">🏃 Cardio</option>
          </select>
        </div>
        <div class="field">
          <label>Durée (minutes)</label>
          <input type="number" id="seance-duree" value="60" min="10" max="240">
        </div>
      </div>
      <div class="field">
        <label>Calories brûlées (optionnel)</label>
        <input type="number" id="seance-calories" placeholder="350" min="0">
      </div>
      <div class="field">
        <label>Note / Observation</label>
        <textarea id="seance-note" placeholder="Ex : bonne progression, PR au squat..."></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('seanceModal')">Annuler</button>
      <button class="btn btn-gold" onclick="submitSeance()">Enregistrer →</button>
    </div>
  </div>
</div>

<!-- MODAL: COACH -->
<div class="modal-bg" id="coachModal">
  <div class="modal">
    <div class="modal-head">
      <span class="modal-title">👤 Assigner un coach</span>
      <button class="modal-close" onclick="closeModal('coachModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="coach-member-id">
      <div class="field">
        <label>Coach</label>
        <select id="coach-select">
          <option value="">— Aucun coach —</option>
          <?php foreach($coachs as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['prenom'].' '.$c['nom']) ?> — <?= htmlspecialchars($c['specialites']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline" onclick="closeModal('coachModal')">Annuler</button>
      <button class="btn btn-gold" onclick="submitCoach()">Assigner →</button>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ─── Navigation ───
function showPage(id, el) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.sb-link').forEach(l => l.classList.remove('active'));
  document.getElementById('page-' + id).classList.add('active');
  if (el) el.classList.add('active');
}

// ─── Search / Filter ───
function filterTable(input, tbodyId) {
  const q = input.value.toLowerCase();
  document.querySelectorAll('#' + tbodyId + ' tr').forEach(tr => {
    tr.style.display = tr.dataset.search?.includes(q) !== false ? '' : 'none';
  });
}

// ─── Toast ───
function toast(msg, isError=false) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = 'toast show' + (isError ? ' error' : '');
  setTimeout(() => t.className = 'toast', 3000);
}

// ─── Modals ───
function openPayModal(id, name, montant) {
  document.getElementById('pay-member-id').value = id;
  document.getElementById('pay-member-name').textContent = name;
  document.getElementById('pay-montant').value = montant;
  document.getElementById('pay-date').value = new Date().toISOString().split('T')[0];
  document.getElementById('payModal').classList.add('open');
}
function openSeanceModal(id, name) {
  document.getElementById('seance-member-id').value = id;
  document.getElementById('seance-member-name').textContent = name;
  const now = new Date();
  document.getElementById('seance-date').value = now.toISOString().split('T')[0];
  document.getElementById('seance-heure').value = now.toTimeString().slice(0,5);
  document.getElementById('seanceModal').classList.add('open');
}
function openCoachModal(memberId, coachId) {
  document.getElementById('coach-member-id').value = memberId;
  document.getElementById('coach-select').value = coachId || '';
  document.getElementById('coachModal').classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}
// Close on bg click
document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', e => { if(e.target===bg) bg.classList.remove('open'); });
});

// ─── Update Statut ───
async function updateStatut(id, statut) {
  const fd = new FormData();
  fd.append('action','update_statut');
  fd.append('id', id);
  fd.append('statut', statut);
  const r = await fetch('admin_api.php', {method:'POST',body:fd});
  const d = await r.json();
  toast(d.success ? '✅ Statut mis à jour' : '❌ '+d.message, !d.success);
}

// ─── Submit Paiement ───
async function submitPaiement() {
  const fd = new FormData();
  fd.append('action','add_paiement');
  fd.append('membre_id', document.getElementById('pay-member-id').value);
  fd.append('montant', document.getElementById('pay-montant').value);
  fd.append('date', document.getElementById('pay-date').value);
  fd.append('mode', document.getElementById('pay-mode').value);
  fd.append('statut', document.getElementById('pay-statut').value);
  fd.append('note', document.getElementById('pay-note').value);
  const r = await fetch('admin_api.php', {method:'POST',body:fd});
  const d = await r.json();
  if(d.success) { closeModal('payModal'); toast('✅ Paiement enregistré !'); setTimeout(()=>location.reload(),1200); }
  else toast('❌ '+d.message, true);
}

// ─── Submit Séance ───
async function submitSeance() {
  const fd = new FormData();
  fd.append('action','add_seance');
  fd.append('membre_id', document.getElementById('seance-member-id').value);
  fd.append('date', document.getElementById('seance-date').value);
  fd.append('heure', document.getElementById('seance-heure').value);
  fd.append('type', document.getElementById('seance-type').value);
  fd.append('duree', document.getElementById('seance-duree').value);
  fd.append('calories', document.getElementById('seance-calories').value);
  fd.append('note', document.getElementById('seance-note').value);
  const r = await fetch('admin_api.php', {method:'POST',body:fd});
  const d = await r.json();
  if(d.success) { closeModal('seanceModal'); toast('✅ Séance enregistrée !'); setTimeout(()=>location.reload(),1200); }
  else toast('❌ '+d.message, true);
}

// ─── Submit Coach ───
async function submitCoach() {
  const fd = new FormData();
  fd.append('action','assign_coach');
  fd.append('membre_id', document.getElementById('coach-member-id').value);
  fd.append('coach_id', document.getElementById('coach-select').value);
  const r = await fetch('admin_api.php', {method:'POST',body:fd});
  const d = await r.json();
  if(d.success) { closeModal('coachModal'); toast('✅ Coach assigné !'); setTimeout(()=>location.reload(),1200); }
  else toast('❌ '+d.message, true);
}
</script>
</body>
</html>
