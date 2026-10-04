<?php
require 'includes/init.php';
exiger_connexion();

$code = (int)($_GET['code'] ?? 0);
$doc  = trouver_document($code);
if (!$doc) {
    flash('warning', 'Document introuvable.');
    redirect('recherche.php');
}
$titre = $doc['titre'];
$pret  = pret_actif($code);
$res   = reservation_active($code);
$moi   = (int)utilisateur()['code'];

// Historique complet (personnel seulement)
if (est_personnel()) {
    $st = db()->prepare("SELECT p.*, m.nom, m.prenom FROM pret p JOIN membre m ON m.code_membre = p.code_membre
                          WHERE p.code_document = ? ORDER BY p.date_pret DESC, p.id_pret DESC");
    $st->execute([$code]);
    $historiquePrets = $st->fetchAll();

    $st = db()->prepare("SELECT r.*, m.nom, m.prenom FROM reservation r JOIN membre m ON m.code_membre = r.code_membre
                          WHERE r.code_document = ? ORDER BY r.date_reservation DESC");
    $st->execute([$code]);
    $historiqueRes = $st->fetchAll();
}

$statutDoc = ['pret_membre' => $pret['code_membre'] ?? null, 'pret_retour_prevu' => $pret['date_retour_prevue'] ?? null,
              'res_membre' => $res['code_membre'] ?? null];
// Statut en base => [couleur du badge, libellé affiché]
$badgesPret = ['actif' => ['warning', 'En cours'], 'retourne' => ['success', 'Retourné'], 'annule' => ['secondary', 'Annulé']];
$badgesRes  = ['active' => ['info', 'Active'], 'completee' => ['success', 'Complétée (prêtée)'], 'annulee' => ['secondary', 'Annulée']];
$retourUrl  = 'document.php?code=' . $code;

require 'includes/header.php';
?>

<nav aria-label="fil d'Ariane"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="recherche.php">Catalogue</a></li>
  <li class="breadcrumb-item active" aria-current="page"><?= e($doc['titre']) ?></li>
</ol></nav>

<div class="row g-4">
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-wrap gap-2 mb-2">
          <span class="badge text-bg-light border"><?= e($doc['categorie']) ?></span>
          <span class="badge text-bg-light border"><?= e($doc['genre']) ?></span>
          <span class="badge text-bg-light border text-capitalize"><?= e($doc['type_public']) ?></span>
        </div>
        <h1 class="h3 mb-1"><?= e($doc['titre']) ?></h1>
        <p class="text-muted"><?= e($doc['auteur']) ?><?= $doc['annee_publication'] ? ' · ' . (int)$doc['annee_publication'] : '' ?></p>
        <p><?= nl2br(e($doc['description'] ?: 'Aucune description.')) ?></p>
        <dl class="row small mb-0">
          <dt class="col-sm-4">Code du document</dt><dd class="col-sm-8"><?= $code ?></dd>
          <?php if ($doc['isbn']): ?><dt class="col-sm-4">ISBN</dt><dd class="col-sm-8"><?= e($doc['isbn']) ?></dd><?php endif; ?>
          <dt class="col-sm-4">Disponibilité</dt><dd class="col-sm-8"><?= badge_statut($statutDoc) ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-header fw-semibold">Actions</div>
      <div class="card-body d-grid gap-2">
        <?php if (est_membre()): ?>
          <?php if (!$pret && (!$res || (int)$res['code_membre'] === $moi)): ?>
            <form action="actions.php" method="post" class="d-grid" data-confirm="Emprunter ce document pour <?= DUREE_PRET_JOURS ?> jours ?">
              <?= csrf_field() ?><input type="hidden" name="action" value="pret">
              <input type="hidden" name="code_document" value="<?= $code ?>"><input type="hidden" name="retour" value="<?= $retourUrl ?>">
              <button class="btn btn-success"><i class="bi bi-journal-arrow-up"></i> Emprunter</button>
            </form>
          <?php endif; ?>
          <?php if (!$res && (!$pret || (int)$pret['code_membre'] !== $moi)): ?>
            <form action="actions.php" method="post" class="d-grid">
              <?= csrf_field() ?><input type="hidden" name="action" value="reserver">
              <input type="hidden" name="code_document" value="<?= $code ?>"><input type="hidden" name="retour" value="<?= $retourUrl ?>">
              <button class="btn btn-outline-primary"><i class="bi bi-bookmark-plus"></i> Réserver</button>
            </form>
          <?php endif; ?>
          <?php if ($pret && (int)$pret['code_membre'] === $moi): ?>
            <div class="alert alert-light border small mb-0">Vous avez ce document en main. Retour prévu le <strong><?= date_fr($pret['date_retour_prevue']) ?></strong>.</div>
          <?php elseif ($res && (int)$res['code_membre'] === $moi): ?>
            <div class="alert alert-info small mb-0">Ce document est réservé à votre nom.</div>
          <?php elseif ($res): ?>
            <div class="alert alert-light border small mb-0">Ce document est déjà réservé par un autre membre.</div>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($pret): ?>
            <p class="small mb-1">Prêté à <a href="membre.php?code=<?= (int)$pret['code_membre'] ?>"><?= e($pret['prenom'] . ' ' . $pret['nom']) ?></a>
               — retour prévu le <?= date_fr($pret['date_retour_prevue']) ?></p>
            <form action="actions.php" method="post" class="d-grid">
              <?= csrf_field() ?><input type="hidden" name="action" value="retour">
              <input type="hidden" name="code_document" value="<?= $code ?>"><input type="hidden" name="retour" value="<?= $retourUrl ?>">
              <button class="btn btn-success"><i class="bi bi-box-arrow-in-down"></i> Enregistrer le retour</button>
            </form>
          <?php endif; ?>
          <?php if ($res): ?>
            <p class="small mb-1 mt-2">Réservé par <a href="membre.php?code=<?= (int)$res['code_membre'] ?>"><?= e($res['prenom'] . ' ' . $res['nom']) ?></a>
               le <?= date_fr($res['date_reservation']) ?></p>
          <?php endif; ?>
          <a href="comptoir.php?doc=<?= $code ?>" class="btn btn-biblio"><i class="bi bi-arrow-left-right"></i> Prêter / réserver au comptoir</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if (est_personnel()): ?>
  <h2 class="h5 mt-4">Historique des prêts (<?= count($historiquePrets) ?>)</h2>
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead class="table-light"><tr><th>No</th><th>Membre</th><th>Date du prêt</th><th>Retour prévu</th><th>Retour effectif</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($historiquePrets as $p): ?>
        <tr>
          <td><?= (int)$p['id_pret'] ?></td>
          <td><a href="membre.php?code=<?= (int)$p['code_membre'] ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?></a></td>
          <td><?= date_fr($p['date_pret']) ?></td>
          <td><?= date_fr($p['date_retour_prevue']) ?></td>
          <td><?= date_fr($p['date_retour_effective']) ?></td>
          <td><span class="badge text-bg-<?= $badgesPret[$p['statut']][0] ?>"><?= $badgesPret[$p['statut']][1] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$historiquePrets): ?><tr><td colspan="6" class="text-muted">Ce document n'a jamais été prêté.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <h2 class="h5 mt-4">Historique des réservations (<?= count($historiqueRes) ?>)</h2>
  <div class="table-responsive">
    <table class="table table-sm table-striped align-middle">
      <thead class="table-light"><tr><th>No</th><th>Membre</th><th>Date</th><th>Fin</th><th>Statut</th></tr></thead>
      <tbody>
      <?php foreach ($historiqueRes as $r): ?>
        <tr>
          <td><?= (int)$r['id_reservation'] ?></td>
          <td><a href="membre.php?code=<?= (int)$r['code_membre'] ?>"><?= e($r['prenom'] . ' ' . $r['nom']) ?></a></td>
          <td><?= date_fr($r['date_reservation']) ?></td>
          <td><?= date_fr($r['date_fin']) ?></td>
          <td><span class="badge text-bg-<?= $badgesRes[$r['statut']][0] ?>"><?= $badgesRes[$r['statut']][1] ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$historiqueRes): ?><tr><td colspan="5" class="text-muted">Aucune réservation.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require 'includes/footer.php'; ?>
