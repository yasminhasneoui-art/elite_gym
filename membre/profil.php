<?php
require_once '../config/config.php';
requireLogin();

$id = $_SESSION['membre_id'];

// Membre + coach
$stmt = $pdo->prepare('
    SELECT m.*, c.prenom AS coach_prenom, c.nom AS coach_nom,
           c.specialites, c.horaire_debut, c.horaire_fin, c.jours
    FROM membres m
    LEFT JOIN coachs c ON m.coach_id = c.id
    WHERE m.id = ?
');
$stmt->execute([$id]);
$m = $stmt->fetch();
if (!$m) { session_destroy(); header('Location: /public/login.html'); exit; }

// Séances
$seances = $pdo->prepare('SELECT * FROM seances WHERE membre_id=? ORDER BY date_seance DESC LIMIT 10');
$seances->execute([$id]);
$seances = $seances->fetchAll();

// Paiements
$paies = $pdo->prepare('SELECT * FROM paiements WHERE membre_id=? ORDER BY date_paiement DESC LIMIT 5');
$paies->execute([$id]);
$paies = $paies->fetchAll();

// Stats séances ce mois
$nbMois = $pdo->prepare("SELECT COUNT(*) FROM seances WHERE membre_id=? AND MONTH(date_seance)=MONTH(NOW()) AND YEAR(date_seance)=YEAR(NOW())");
$nbMois->execute([$id]); $nbMois = $nbMois->fetchColumn();

$nbTotal = $pdo->prepare("SELECT COUNT(*) FROM seances WHERE membre_id=?");
$nbTotal->execute([$id]); $nbTotal = $nbTotal->fetchColumn();

$imc = $m['imc'];
$imcLabel = '';
$imcColor = '#c9a84c';
if ($imc) {
    if ($imc < 18.5)      { $imcLabel = 'Insuffisance pondérale'; $imcColor='#3b82f6'; }
    elseif ($imc < 25)    { $imcLabel = 'Poids normal ✓';         $imcColor='#22c55e'; }
    elseif ($imc < 30)    { $imcLabel = 'Surpoids';               $imcColor='#f59e0b'; }
    else                  { $imcLabel = 'Obésité';                $imcColor='#ef4444'; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Élite Gym — Mon Profil</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700;900&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root{--white:#fff;--cream:#faf8f4;--light:#f0ece4;--dark:#1c1712;--mid:#4a4035;--muted:#9a8a78;--gold:#c9a84c;--gold2:#e8c96a;--border:#e8e0d4;--shadow:0 4px 32px rgba(28,23,18,.10);}
*{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{background:var(--cream);color:var(--dark);font-family:'DM Sans',sans-serif;font-weight:400;}

/* NAV */
nav{display:flex;align-items:center;justify-content:space-between;padding:0 60px;height:74px;background:rgba(255,255,255,.97);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.logo{font-family:'Montserrat',sans-serif;font-weight:900;font-size:22px;letter-spacing:3px;color:var(--dark);text-decoration:none;text-transform:uppercase;}
.logo span{color:var(--gold);}
.nav-right{display:flex;align-items:center;gap:16px;}
.nav-user{font-size:13px;color:var(--muted);}
.nav-user strong{color:var(--dark);}
.btn-logout{background:transparent;border:1px solid var(--border);color:var(--muted);padding:8px 18px;border-radius:4px;font-size:12px;font-weight:600;text-decoration:none;transition:all .2s;font-family:'DM Sans',sans-serif;cursor:pointer;}
.btn-logout:hover{border-color:var(--muted);color:var(--dark);}

/* HEADER PROFIL */
.profil-header{background:var(--dark);padding:56px 60px 48px;position:relative;overflow:hidden;}
.profil-header::before{content:'';position:absolute;left:0;top:0;bottom:0;width:5px;background:var(--gold);}
.profil-header::after{content:'';position:absolute;right:-80px;top:-80px;width:320px;height:320px;border-radius:50%;background:rgba(201,168,76,.07);}
.ph-tag{font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:var(--gold);margin-bottom:12px;}
.ph-name{font-family:'Montserrat',sans-serif;font-weight:900;font-size:clamp(36px,5vw,64px);color:var(--white);line-height:1;margin-bottom:8px;}
.ph-sub{font-size:14px;color:rgba(255,255,255,.5);margin-bottom:24px;}
.ph-badges{display:flex;gap:10px;flex-wrap:wrap;}
.badge{display:inline-block;padding:5px 14px;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;border-radius:20px;}
.badge-gold{background:rgba(201,168,76,.15);border:1px solid rgba(201,168,76,.4);color:var(--gold);}
.badge-green{background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.3);color:#22c55e;}
.badge-blue{background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.3);color:#60a5fa;}

/* MAIN GRID */
.main{max-width:1200px;margin:0 auto;padding:48px 60px 80px;display:grid;grid-template-columns:1fr 1fr;gap:20px;}
.full{grid-column:1/-1;}

/* CARDS */
.card{background:var(--white);border:1px solid var(--border);border-radius:8px;padding:28px;box-shadow:var(--shadow);}
.card-title{font-family:'Montserrat',sans-serif;font-weight:900;font-size:14px;letter-spacing:2px;text-transform:uppercase;color:var(--dark);margin-bottom:20px;display:flex;align-items:center;gap:10px;}
.card-title span{color:var(--gold);}

/* STATS GRID */
.stats-mini{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;}
.stat-mini{background:var(--cream);border-radius:6px;padding:18px;text-align:center;border:1px solid var(--border);}
.stat-mini-num{font-family:'Montserrat',sans-serif;font-weight:900;font-size:32px;color:var(--gold);line-height:1;margin-bottom:4px;}
.stat-mini-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);}

/* COACH CARD */
.coach-info{display:flex;flex-direction:column;gap:12px;}
.coach-name-big{font-family:'Montserrat',sans-serif;font-weight:900;font-size:28px;color:var(--dark);}
.coach-row{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--mid);}
.coach-row strong{color:var(--dark);font-weight:600;}

/* METRICS */
.metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.metric{background:var(--cream);border-radius:6px;padding:18px;text-align:center;border:1px solid var(--border);}
.metric-val{font-family:'Montserrat',sans-serif;font-weight:900;font-size:28px;color:var(--dark);line-height:1;margin-bottom:4px;}
.metric-unit{font-size:11px;color:var(--muted);}
.metric-label{font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:4px;}

/* IMC BAR */
.imc-bar-wrap{margin-top:16px;}
.imc-bar{height:8px;border-radius:4px;background:linear-gradient(to right,#3b82f6 0%,#22c55e 25%,#f59e0b 55%,#ef4444 100%);position:relative;margin:10px 0;}
.imc-cursor{position:absolute;top:-4px;width:16px;height:16px;border-radius:50%;background:var(--dark);border:2px solid var(--white);box-shadow:0 2px 6px rgba(0,0,0,.2);transform:translateX(-50%);}
.imc-labels{display:flex;justify-content:space-between;font-size:10px;color:var(--muted);}

/* TABLE */
table{width:100%;border-collapse:collapse;}
thead tr{border-bottom:1px solid rgba(201,168,76,.3);}
thead th{text-align:left;font-size:10px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);padding:0 12px 12px;font-family:'Montserrat',sans-serif;}
tbody tr{border-bottom:1px solid var(--border);transition:background .15s;}
tbody tr:hover{background:var(--cream);}
tbody td{padding:12px;font-size:13px;color:var(--mid);}
td.td-name{color:var(--dark);font-weight:600;}
.td-badge{display:inline-block;padding:3px 10px;font-size:9px;font-weight:700;letter-spacing:2px;text-transform:uppercase;border-radius:12px;}
.td-badge.gold{background:rgba(201,168,76,.12);color:var(--gold);border:1px solid rgba(201,168,76,.3);}
.td-badge.green{background:rgba(34,197,94,.1);color:#22c55e;border:1px solid rgba(34,197,94,.2);}
.td-badge.orange{background:rgba(245,158,11,.1);color:#f59e0b;border:1px solid rgba(245,158,11,.2);}
.td-badge.red{background:rgba(230,57,70,.1);color:#e63946;border:1px solid rgba(230,57,70,.3);}
.mode-pay{font-size:11px;color:var(--muted);margin-top:2px;}

/* NEXT PAY */
.next-pay{display:flex;justify-content:space-between;align-items:center;background:rgba(201,168,76,.07);border:1px solid rgba(201,168,76,.25);border-radius:6px;padding:20px 24px;margin-bottom:16px;}
.next-pay-label{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--gold);margin-bottom:6px;}
.next-pay-date{font-family:'Montserrat',sans-serif;font-weight:900;font-size:22px;color:var(--dark);}
.next-pay-amount{font-family:'Montserrat',sans-serif;font-weight:900;font-size:36px;color:var(--gold);}
.next-pay-sub{font-size:11px;color:var(--muted);text-align:right;}

/* EMPTY */
.empty-state{text-align:center;padding:32px;color:var(--muted);font-size:13px;}

/* BTN */
.btn-primary{display:inline-flex;align-items:center;gap:8px;background:var(--gold);color:var(--dark);padding:12px 26px;font-size:13px;font-weight:700;border:none;border-radius:4px;cursor:pointer;text-decoration:none;font-family:'DM Sans',sans-serif;transition:all .2s;}
.btn-primary:hover{background:var(--gold2);transform:translateY(-1px);}

/* RESPONSIVE */
@media(max-width:900px){
  nav{padding:0 20px;}
  .profil-header{padding:40px 24px 36px;}
  .main{padding:30px 20px 60px;grid-template-columns:1fr;}
  .full{grid-column:1;}
  .metrics{grid-template-columns:1fr 1fr;}
}
</style>
</head>
<body>

<nav>
  <a href="../public/index.html" class="logo">ÉLITE<span>GYM</span></a>
  <div class="nav-right">
    <span class="nav-user">Bonjour, <strong><?= htmlspecialchars($m['prenom']) ?></strong></span>
    <a href="logout.php" class="btn-logout">Déconnexion →</a>
  </div>
</nav>

<!-- HEADER -->
<div class="profil-header">
  <p class="ph-tag">👤 Mon Espace Membre</p>
  <h1 class="ph-name"><?= htmlspecialchars(strtoupper($m['prenom']).' '.strtoupper($m['nom'])) ?></h1>
  <p class="ph-sub">@<?= htmlspecialchars($m['username']) ?> · Membre depuis le <?= date('d/m/Y', strtotime($m['created_at'])) ?></p>
  <div class="ph-badges">
    <span class="badge badge-gold">⭐ <?= htmlspecialchars($m['abonnement']) ?></span>
    <span class="badge badge-green">✓ <?= ucfirst($m['statut']) ?></span>
    <span class="badge badge-blue">🏋️ <?= $nbTotal ?> séances au total</span>
  </div>
</div>

<div class="main">

  <!-- STATS SÉANCES -->
  <div class="card">
    <div class="card-title"><span>📊</span> Mes séances</div>
    <div class="stats-mini">
      <div class="stat-mini">
        <div class="stat-mini-num"><?= $nbTotal ?></div>
        <div class="stat-mini-label">Total</div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-num"><?= $nbMois ?></div>
        <div class="stat-mini-label">Ce mois</div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-num"><?= $m['derniere_seance'] ? date('d/m', strtotime($m['derniere_seance'])) : '—' ?></div>
        <div class="stat-mini-label">Dernière séance</div>
      </div>
      <div class="stat-mini">
        <div class="stat-mini-num"><?= $m['date_debut'] ? date('d/m/Y', strtotime($m['date_debut'])) : '—' ?></div>
        <div class="stat-mini-label">Début programme</div>
      </div>
    </div>
  </div>

  <!-- COACH -->
  <div class="card">
    <div class="card-title"><span>👤</span> Mon coach</div>
    <div class="coach-info">
      <div class="coach-name-big"><?= $m['coach_prenom'] ? htmlspecialchars($m['coach_prenom'].' '.$m['coach_nom']) : 'Non affecté' ?></div>
      <?php if ($m['coach_prenom']): ?>
        <div class="coach-row">🎯 <strong>Spécialités :</strong> <?= htmlspecialchars($m['specialites']) ?></div>
        <div class="coach-row">🕐 <strong>Horaires :</strong> <?= date('H:i',strtotime($m['horaire_debut'])) ?> – <?= date('H:i',strtotime($m['horaire_fin'])) ?></div>
        <div class="coach-row">📅 <strong>Jours :</strong> <?= htmlspecialchars($m['jours']) ?></div>
      <?php endif; ?>
      <div class="coach-row">🏋️ <strong>Programmes :</strong> <?= htmlspecialchars($m['programmes']) ?></div>
    </div>
  </div>

  <!-- MÉTRIQUES -->
  <div class="card full">
    <div class="card-title"><span>📏</span> Mes métriques</div>
    <div class="metrics">
      <div class="metric">
        <div class="metric-val"><?= $m['poids'] ? number_format($m['poids'],1) : '—' ?></div>
        <div class="metric-unit">kg</div>
        <div class="metric-label">Poids actuel</div>
      </div>
      <div class="metric">
        <div class="metric-val"><?= $m['taille'] ? number_format($m['taille'],0) : '—' ?></div>
        <div class="metric-unit">cm</div>
        <div class="metric-label">Taille</div>
      </div>
      <div class="metric">
        <div class="metric-val" style="color:<?= $imcColor ?>"><?= $m['imc'] ?? '—' ?></div>
        <div class="metric-unit"><?= $imcLabel ?></div>
        <div class="metric-label">IMC</div>
      </div>
      <div class="metric">
        <div class="metric-val"><?= $m['calories_jour'] ?? '—' ?></div>
        <div class="metric-unit">kcal/jour</div>
        <div class="metric-label">Besoins caloriques</div>
      </div>
      <div class="metric">
        <div class="metric-val"><?= $m['objectif_poids'] ? number_format($m['objectif_poids'],1) : '—' ?></div>
        <div class="metric-unit">kg</div>
        <div class="metric-label">Objectif poids</div>
      </div>
      <div class="metric">
        <div class="metric-val"><?= ($m['poids'] && $m['objectif_poids']) ? number_format(abs($m['poids'] - $m['objectif_poids']),1) : '—' ?></div>
        <div class="metric-unit">kg à <?= ($m['poids'] && $m['objectif_poids'] && $m['objectif_poids'] < $m['poids']) ? 'perdre' : 'gagner' ?></div>
        <div class="metric-label">Écart objectif</div>
      </div>
    </div>
    <?php if ($m['imc']): ?>
    <div class="imc-bar-wrap">
      <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-bottom:8px;">Indice de masse corporelle</div>
      <div class="imc-bar">
        <?php $pct = min(max(($m['imc']-15)/25*100, 0), 100); ?>
        <div class="imc-cursor" style="left:<?= $pct ?>%"></div>
      </div>
      <div class="imc-labels"><span>15 — Maigreur</span><span>18.5 — Normal</span><span>25 — Surpoids</span><span>30+ — Obésité</span></div>
    </div>
    <?php endif; ?>
  </div>

  <!-- PAIEMENT -->
  <div class="card">
    <div class="card-title"><span>💳</span> Paiements</div>
    <div class="next-pay">
      <div>
        <div class="next-pay-label">Prochain paiement</div>
        <div class="next-pay-date"><?= $m['prochain_paiement'] ? date('d/m/Y', strtotime($m['prochain_paiement'])) : '—' ?></div>
      </div>
      <div>
        <?php
          $tarifs = ['Essential'=>89,'Performance'=>149,'Elite'=>249,'Élite'=>249];
          $montant = $tarifs[$m['abonnement']] ?? '—';
        ?>
        <div class="next-pay-amount"><?= $montant ?> DT</div>
        <div class="next-pay-sub"><?= htmlspecialchars($m['abonnement']) ?>/mois</div>
      </div>
    </div>
    <?php if ($paies): ?>
    <table>
      <thead><tr><th>Date</th><th>Montant</th><th>Mode</th><th>Statut</th></tr></thead>
      <tbody>
        <?php foreach ($paies as $p):
          $modeIcon  = ['espèces'=>'💵','carte'=>'💳','e-dinar_jeune'=>'📱'][$p['mode_paiement']] ?? '💰';
          $badgeCls  = $p['statut']==='payé' ? 'green' : ($p['statut']==='en_attente' ? 'orange' : 'red');
        ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($p['date_paiement'])) ?></td>
          <td class="td-name"><?= number_format($p['montant'],0) ?> DT</td>
          <td><?= $modeIcon ?> <span style="font-size:12px;color:var(--mid)"><?= htmlspecialchars($p['mode_paiement']) ?></span></td>
          <td><span class="td-badge <?= $badgeCls ?>"><?= $p['statut'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="empty-state">Aucun paiement enregistré.</div>
    <?php endif; ?>
  </div>

  <!-- SÉANCES -->
  <div class="card">
    <div class="card-title"><span>🗓</span> Historique séances</div>
    <?php if ($seances): ?>
    <table>
      <thead><tr><th>Date</th><th>Type</th><th>Durée</th><th>Calories</th></tr></thead>
      <tbody>
        <?php foreach ($seances as $s): ?>
        <tr>
          <td class="td-name"><?= date('d/m/Y', strtotime($s['date_seance'])) ?></td>
          <td><?= htmlspecialchars($s['type']) ?></td>
          <td><?= $s['duree_min'] ?> min</td>
          <td><?= $s['calories'] ? $s['calories'].' kcal' : '—' ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <div class="empty-state">Aucune séance enregistrée.<br>Commence dès le <?= $m['date_debut'] ? date('d/m/Y', strtotime($m['date_debut'])) : '—' ?>.</div>
    <?php endif; ?>
  </div>

</div>
</body>
</html>
