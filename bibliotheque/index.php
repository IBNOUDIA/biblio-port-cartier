<?php
require 'includes/init.php';
exiger_connexion();

$u = utilisateur();
$titre = 'Accueil';

if (est_membre()) {
    $st = db()->prepare("SELECT
            (SELECT COUNT(*) FROM pret WHERE code_membre = :m AND statut = 'actif') AS prets,
            (SELECT COUNT(*) FROM pret WHERE code_membre = :m2 AND statut = 'actif' AND date_retour_prevue < CURDATE()) AS retards,
            (SELECT COUNT(*) FROM reservation WHERE code_membre = :m3 AND statut = 'active') AS reservations");
    $st->execute(['m' => $u['code'], 'm2' => $u['code'], 'm3' => $u['code']]);
    $stats = $st->fetch();
} else {
    $stats = db()->query("SELECT
            (SELECT COUNT(*) FROM document) AS documents,
            (SELECT COUNT(*) FROM membre) AS membres,
            (SELECT COUNT(*) FROM pret WHERE statut = 'actif') AS prets,
            (SELECT COUNT(*) FROM pret WHERE statut = 'actif' AND date_retour_prevue < CURDATE()) AS retards,
            (SELECT COUNT(*) FROM reservation WHERE statut = 'active') AS reservations")->fetch();
}

// Nouveautés (6 derniers documents ajoutés)
$nouveautes = db()->query('SELECT d.code_document, d.titre, d.auteur, c.nom AS categorie, ' . sql_colonnes_statut() . '
                             FROM document d JOIN categorie c ON c.id_categorie = d.id_categorie
                            ORDER BY d.code_document DESC LIMIT 6')->fetchAll();

require 'includes/header.php';
?>

<section class="hero rounded-3 p-4 mb-4">
  <h1 class="h3 text-biblio mb-1">Bonjour <?= e($u['prenom']) ?> !</h1>
  <p class="mb-3 text-muted">
    <?= est_membre() ? 'Recherchez un livre, un film, un jeu ou un CD et réservez-le en ligne.'
                     : 'Tableau de bord du personnel de la bibliothèque.' ?>
  </p>
  <form action="recherche.php" method="get" class="row g-2" role="search">
    <div class="col-12 col-md-9">
      <input type="search" name="q" class="form-control form-control-lg" placeholder="Titre, auteur, ISBN, genre, mot-clé…" aria-label="Mots-clés">
    </div>
    <div class="col-12 col-md-3 d-grid">
      <button class="btn btn-biblio btn-lg"><i class="bi bi-search"></i> Rechercher</button>
    </div>
  </form>
</section>

<?php if (est_membre()): ?>
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <a href="mon_compte.php" class="card carte-stat text-decoration-none h-100"><div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="valeur text-biblio"><?= (int)$stats['prets'] ?></div><div class="text-muted">Prêt(s) en cours</div></div><i class="bi bi-journal-arrow-up text-biblio"></i>
      </div></a>
    </div>
    <div class="col-12 col-md-4">
      <a href="mon_compte.php" class="card carte-stat text-decoration-none h-100"><div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="valeur text-info"><?= (int)$stats['reservations'] ?></div><div class="text-muted">Réservation(s)</div></div><i class="bi bi-bookmark-star text-info"></i>
      </div></a>
    </div>
    <div class="col-12 col-md-4">
      <a href="mon_compte.php" class="card carte-stat text-decoration-none h-100 <?= $stats['retards'] ? 'border-danger' : '' ?>"><div class="card-body d-flex justify-content-between align-items-center">
        <div><div class="valeur <?= $stats['retards'] ? 'text-danger' : 'text-success' ?>"><?= (int)$stats['retards'] ?></div><div class="text-muted">Document(s) en retard</div></div><i class="bi bi-alarm <?= $stats['retards'] ? 'text-danger' : 'text-success' ?>"></i>
      </div></a>
    </div>
  </div>
<?php else: ?>
  <div class="row g-3 mb-4 row-cols-2 row-cols-md-3 row-cols-xl-5">
    <?php foreach ([
        ['Documents', $stats['documents'], 'bi-collection', 'text-biblio', 'rapports.php?r=documents'],
        ['Membres', $stats['membres'], 'bi-people', 'text-biblio', 'rapports.php?r=membres'],
        ['Prêts en cours', $stats['prets'], 'bi-journal-arrow-up', 'text-warning', 'rapports.php?r=pretes'],
        ['Retards', $stats['retards'], 'bi-alarm', $stats['retards'] ? 'text-danger' : 'text-success', 'rapports.php?r=retards'],
        ['Réservations', $stats['reservations'], 'bi-bookmark-star', 'text-info', 'rapports.php?r=reserves'],
    ] as [$lib, $val, $ico, $coul, $lien]): ?>
      <div class="col">
        <a href="<?= $lien ?>" class="card carte-stat text-decoration-none h-100"><div class="card-body d-flex justify-content-between align-items-center">
          <div><div class="valeur <?= $coul ?>"><?= (int)$val ?></div><div class="text-muted small"><?= $lib ?></div></div><i class="bi <?= $ico ?> <?= $coul ?>"></i>
        </div></a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="d-flex flex-wrap gap-2 mb-4">
    <a href="comptoir.php" class="btn btn-biblio"><i class="bi bi-arrow-left-right"></i> Prêt / retour / réservation</a>
    <a href="membres.php" class="btn btn-outline-secondary"><i class="bi bi-person-lines-fill"></i> Rechercher un membre</a>
    <?php if (est_admin()): ?>
      <a href="admin_membre.php" class="btn btn-outline-secondary"><i class="bi bi-person-plus"></i> Nouveau membre</a>
      <a href="admin_employe.php" class="btn btn-outline-secondary"><i class="bi bi-person-gear"></i> Nouvel employé</a>
    <?php endif; ?>
  </div>
<?php endif; ?>

<h2 class="h5 mb-3">Nouveautés au catalogue</h2>
<div class="row g-3 row-cols-1 row-cols-sm-2 row-cols-lg-3">
  <?php foreach ($nouveautes as $d): ?>
    <div class="col">
      <div class="card h-100">
        <div class="card-body">
          <span class="badge text-bg-light border mb-2"><?= e($d['categorie']) ?></span>
          <h3 class="h6 card-title mb-1"><a href="document.php?code=<?= (int)$d['code_document'] ?>" class="stretched-link text-decoration-none"><?= e($d['titre']) ?></a></h3>
          <p class="small text-muted mb-2"><?= e($d['auteur']) ?></p>
          <?= badge_statut($d) ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require 'includes/footer.php'; ?>
