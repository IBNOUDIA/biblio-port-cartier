<?php
require 'includes/init.php';
exiger_connexion(['employe', 'admin']);

$titre = 'Recherche de membres';
$q = trim((string)($_GET['q'] ?? ''));

$where = [];
$params = [];
foreach (preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) as $mot) {
    $like = like_contient($mot);
    $where[] = '(m.nom LIKE ? OR m.prenom LIKE ? OR m.courriel LIKE ? OR m.telephone LIKE ? OR m.ville LIKE ? OR CAST(m.code_membre AS CHAR) = ?)';
    array_push($params, $like, $like, $like, $like, $like, $mot);
}

$sql = "SELECT m.*,
               (SELECT COUNT(*) FROM pret p WHERE p.code_membre = m.code_membre AND p.statut = 'actif') AS nb_prets,
               (SELECT COUNT(*) FROM pret p WHERE p.code_membre = m.code_membre AND p.statut = 'actif' AND p.date_retour_prevue < CURDATE()) AS nb_retards,
               (SELECT COUNT(*) FROM reservation r WHERE r.code_membre = m.code_membre AND r.statut = 'active') AS nb_res
          FROM membre m"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY m.nom, m.prenom LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$membres = $st->fetchAll();

// Un seul résultat : on ouvre directement le dossier
if ($q !== '' && count($membres) === 1) {
    redirect('membre.php?code=' . (int)$membres[0]['code_membre']);
}

require 'includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <h1 class="h3 mb-0"><i class="bi bi-people text-biblio"></i> Membres</h1>
  <?php if (est_admin()): ?><a href="admin_membre.php" class="btn btn-sm btn-biblio"><i class="bi bi-person-plus"></i> Nouveau membre</a><?php endif; ?>
</div>

<form method="get" class="row g-2 mb-4" role="search">
  <div class="col-12 col-md-9">
    <input type="search" name="q" class="form-control form-control-lg" value="<?= e($q) ?>" placeholder="Nom, prénom, code, courriel, téléphone…" aria-label="Rechercher un membre" autofocus>
  </div>
  <div class="col-12 col-md-3 d-grid"><button class="btn btn-biblio btn-lg"><i class="bi bi-search"></i> Rechercher</button></div>
</form>

<p class="text-muted small"><?= count($membres) ?> membre(s).</p>
<div class="table-responsive">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr><th>Code</th><th>Nom</th><th class="d-none d-md-table-cell">Téléphone</th><th class="d-none d-lg-table-cell">Courriel</th>
          <th class="text-center">Prêts</th><th class="text-center">Réserv.</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($membres as $m): ?>
      <tr>
        <td><?= (int)$m['code_membre'] ?></td>
        <td><a href="membre.php?code=<?= (int)$m['code_membre'] ?>" class="fw-semibold text-decoration-none"><?= e($m['nom'] . ', ' . $m['prenom']) ?></a></td>
        <td class="d-none d-md-table-cell"><?= e($m['telephone']) ?></td>
        <td class="d-none d-lg-table-cell"><?= e($m['courriel']) ?></td>
        <td class="text-center"><?= (int)$m['nb_prets'] ?><?= $m['nb_retards'] ? ' <span class="badge text-bg-danger">' . (int)$m['nb_retards'] . ' retard</span>' : '' ?></td>
        <td class="text-center"><?= (int)$m['nb_res'] ?></td>
        <td class="text-end"><a href="membre.php?code=<?= (int)$m['code_membre'] ?>" class="btn btn-sm btn-outline-secondary">Dossier</a></td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$membres): ?><tr><td colspan="7" class="text-muted">Aucun membre trouvé.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require 'includes/footer.php'; ?>
