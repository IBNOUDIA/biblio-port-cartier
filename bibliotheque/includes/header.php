<?php
/** @var string $titre  Titre de la page (défini avant l'inclusion) */
$titre = $titre ?? NOM_SITE;
$page  = basename($_SERVER['SCRIPT_NAME']);
$u     = utilisateur();
$actif = fn(string ...$p) => in_array($page, $p, true) ? ' active" aria-current="page' : '';
?>
<!doctype html>
<html lang="fr-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titre) ?> — Bibliothèque de Port-Cartier</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-biblio shadow-sm d-print-none">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
      <i class="bi bi-book-half fs-4"></i>
      <span class="lh-1">Bibliothèque<br><small class="fw-light">Port-Cartier</small></span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
            aria-controls="menu" aria-expanded="false" aria-label="Ouvrir le menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menu">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link<?= $actif('index.php') ?>" href="index.php"><i class="bi bi-house"></i> Accueil</a></li>
        <li class="nav-item"><a class="nav-link<?= $actif('recherche.php', 'document.php') ?>" href="recherche.php"><i class="bi bi-search"></i> Catalogue</a></li>

        <?php if (est_membre()): ?>
          <li class="nav-item"><a class="nav-link<?= $actif('mon_compte.php') ?>" href="mon_compte.php"><i class="bi bi-person-badge"></i> Mon dossier</a></li>
        <?php endif; ?>

        <?php if (est_personnel()): ?>
          <li class="nav-item"><a class="nav-link<?= $actif('comptoir.php') ?>" href="comptoir.php"><i class="bi bi-arrow-left-right"></i> Comptoir</a></li>
          <li class="nav-item"><a class="nav-link<?= $actif('membres.php', 'membre.php') ?>" href="membres.php"><i class="bi bi-people"></i> Membres</a></li>
          <li class="nav-item"><a class="nav-link<?= $actif('rapports.php') ?>" href="rapports.php"><i class="bi bi-clipboard-data"></i> Rapports</a></li>
        <?php endif; ?>

        <?php if (est_admin()): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle<?= $actif('admin_membre.php', 'admin_employe.php') ?>" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-shield-lock"></i> Administration
            </a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="admin_membre.php"><i class="bi bi-person-plus"></i> Créer un membre</a></li>
              <li><a class="dropdown-item" href="admin_employe.php"><i class="bi bi-person-gear"></i> Créer un employé</a></li>
            </ul>
          </li>
        <?php endif; ?>
      </ul>

      <?php if ($u): ?>
        <div class="d-flex align-items-center gap-2 text-white mt-2 mt-lg-0">
          <span class="small">
            <i class="bi bi-person-circle"></i> <?= e($u['prenom'] . ' ' . $u['nom']) ?>
            <span class="badge bg-light text-dark ms-1"><?= e(['membre' => 'Membre', 'employe' => 'Employé', 'admin' => 'Administrateur'][$u['role']]) ?></span>
          </span>
          <a class="btn btn-sm btn-outline-light" href="logout.php"><i class="bi bi-box-arrow-right"></i> Déconnexion</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</nav>

<main class="container my-4 flex-grow-1">
  <?php foreach (flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
      <?= nl2br(e($f['msg'])) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
  <?php endforeach; ?>
