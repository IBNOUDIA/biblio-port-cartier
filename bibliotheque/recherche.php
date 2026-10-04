<?php
require 'includes/init.php';
exiger_connexion();

$titre = 'Catalogue';
$q         = trim((string)($_GET['q'] ?? ''));
$categorie = (int)($_GET['categorie'] ?? 0);
$type      = (string)($_GET['type'] ?? '');
$dispo     = isset($_GET['dispo']);

$categories = db()->query('SELECT id_categorie, nom FROM categorie ORDER BY nom')->fetchAll();

/*
 * Recherche par mots-clés : chaque mot doit apparaître dans au moins un des champs
 * (titre, auteur, description, ISBN, catégorie, genre, année ou code du document).
 */
$where  = [];
$params = [];
foreach (preg_split('/\s+/', $q, -1, PREG_SPLIT_NO_EMPTY) as $mot) {
    $like = '%' . $mot . '%';
    $where[] = '(d.titre LIKE ? OR d.auteur LIKE ? OR d.description LIKE ? OR d.isbn LIKE ?
                 OR c.nom LIKE ? OR g.nom LIKE ? OR CAST(d.annee_publication AS CHAR) = ? OR CAST(d.code_document AS CHAR) = ?)';
    array_push($params, $like, $like, $like, $like, $like, $like, $mot, $mot);
}
if ($categorie) { $where[] = 'd.id_categorie = ?'; $params[] = $categorie; }
if (in_array($type, ['enfant', 'ado', 'adulte'], true)) { $where[] = 'd.type_public = ?'; $params[] = $type; }

$sql = 'SELECT d.*, c.nom AS categorie, g.nom AS genre, ' . sql_colonnes_statut() . '
          FROM document d
          JOIN categorie c ON c.id_categorie = d.id_categorie
          JOIN genre g     ON g.id_genre     = d.id_genre'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ($dispo ? ' HAVING pret_membre IS NULL AND res_membre IS NULL' : '')
     . ' ORDER BY d.titre LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$documents = $st->fetchAll();

$moi = (int)utilisateur()['code'];
$urlRetour = 'recherche.php?' . http_build_query(array_filter(['q' => $q, 'categorie' => $categorie ?: null, 'type' => $type ?: null]));

require 'includes/header.php';
?>

<h1 class="h3 mb-3"><i class="bi bi-search text-biblio"></i> Catalogue des documents</h1>

<form method="get" class="card card-body mb-4" role="search">
  <div class="row g-2 align-items-end">
    <div class="col-12 col-lg-5">
      <label for="q" class="form-label small mb-1">Mots-clés</label>
      <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>" placeholder="ex. : Verne aventure, 978-2…, horreur">
    </div>
    <div class="col-6 col-lg-2">
      <label for="categorie" class="form-label small mb-1">Catégorie</label>
      <select id="categorie" name="categorie" class="form-select">
        <option value="">Toutes</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int)$c['id_categorie'] ?>" <?= $categorie === (int)$c['id_categorie'] ? 'selected' : '' ?>><?= e($c['nom']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-lg-2">
      <label for="type" class="form-label small mb-1">Public</label>
      <select id="type" name="type" class="form-select">
        <option value="">Tous</option>
        <?php foreach (['enfant' => 'Enfant', 'ado' => 'Ado', 'adulte' => 'Adulte'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $type === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-12 col-lg-3 d-flex gap-2 align-items-center">
      <div class="form-check text-nowrap">
        <input class="form-check-input" type="checkbox" id="dispo" name="dispo" <?= $dispo ? 'checked' : '' ?>>
        <label class="form-check-label small" for="dispo">Disponibles</label>
      </div>
      <button class="btn btn-biblio flex-grow-1"><i class="bi bi-search"></i> Rechercher</button>
    </div>
  </div>
</form>

<p class="text-muted small"><?= count($documents) ?> document(s) trouvé(s)<?= $q !== '' ? ' pour « ' . e($q) . ' »' : '' ?>.</p>

<?php if (!$documents): ?>
  <div class="alert alert-light border"><i class="bi bi-info-circle"></i> Aucun document ne correspond à votre recherche. Essayez d'autres mots-clés.</div>
<?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th>Code</th><th>Titre</th><th class="d-none d-md-table-cell">Auteur / producteur</th>
          <th class="d-none d-lg-table-cell">Catégorie</th><th class="d-none d-lg-table-cell">Public</th>
          <th>Disponibilité</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($documents as $d):
          $code = (int)$d['code_document'];
          $libre = empty($d['pret_membre']) && empty($d['res_membre']);
          $reserveParMoi = (int)$d['res_membre'] === $moi;
      ?>
        <tr>
          <td class="text-muted small"><?= $code ?></td>
          <td>
            <a href="document.php?code=<?= $code ?>" class="fw-semibold text-decoration-none"><?= e($d['titre']) ?></a>
            <div class="small text-muted d-md-none"><?= e($d['auteur']) ?></div>
            <div class="small text-muted"><?= e($d['genre']) ?><?= $d['annee_publication'] ? ' · ' . (int)$d['annee_publication'] : '' ?></div>
          </td>
          <td class="d-none d-md-table-cell"><?= e($d['auteur']) ?></td>
          <td class="d-none d-lg-table-cell"><?= e($d['categorie']) ?></td>
          <td class="d-none d-lg-table-cell text-capitalize"><?= e($d['type_public']) ?></td>
          <td><?= badge_statut($d) ?></td>
          <td class="text-end text-nowrap">
            <?php if (est_membre()): ?>
              <?php if ($libre || ($reserveParMoi && empty($d['pret_membre']))): ?>
                <form action="actions.php" method="post" class="d-inline" data-confirm="Emprunter « <?= e($d['titre']) ?> » pour <?= DUREE_PRET_JOURS ?> jours ?">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="pret">
                  <input type="hidden" name="code_document" value="<?= $code ?>">
                  <input type="hidden" name="retour" value="<?= e($urlRetour) ?>">
                  <button class="btn btn-sm btn-success"><i class="bi bi-journal-arrow-up"></i> Emprunter</button>
                </form>
              <?php endif; ?>
              <?php if (empty($d['res_membre']) && (int)$d['pret_membre'] !== $moi): ?>
                <form action="actions.php" method="post" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="reserver">
                  <input type="hidden" name="code_document" value="<?= $code ?>">
                  <input type="hidden" name="retour" value="<?= e($urlRetour) ?>">
                  <button class="btn btn-sm btn-outline-primary"><i class="bi bi-bookmark-plus"></i> Réserver</button>
                </form>
              <?php elseif ($reserveParMoi): ?>
                <span class="badge text-bg-primary">Réservé pour vous</span>
              <?php endif; ?>
            <?php else: ?>
              <a href="comptoir.php?doc=<?= $code ?>" class="btn btn-sm btn-outline-primary" title="Prêt / réservation / retour"><i class="bi bi-arrow-left-right"></i> Comptoir</a>
              <a href="document.php?code=<?= $code ?>" class="btn btn-sm btn-outline-secondary" title="Fiche et historique"><i class="bi bi-clock-history"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
