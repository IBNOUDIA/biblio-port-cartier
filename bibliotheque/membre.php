<?php
/**
 * Dossier d'un membre (personnel) : informations, emprunts et réservations actifs,
 * avec retour / annulation, et historique.
 */
require 'includes/init.php';
exiger_connexion(['employe', 'admin']);

$code   = (int)($_GET['code'] ?? 0);
$membre = trouver_membre($code);
if (!$membre) {
    flash('warning', 'Membre introuvable.');
    redirect('membres.php');
}
$titre = $membre['prenom'] . ' ' . $membre['nom'];

$st = db()->prepare("SELECT p.*, d.titre,
                            (SELECT 1 FROM reservation r WHERE r.code_document = p.code_document AND r.statut = 'active' LIMIT 1) AS reserve
                       FROM pret p JOIN document d ON d.code_document = p.code_document
                      WHERE p.code_membre = ? AND p.statut = 'actif' ORDER BY p.date_retour_prevue");
$st->execute([$code]);
$prets = $st->fetchAll();

$st = db()->prepare("SELECT r.*, d.titre,
                            (SELECT 1 FROM pret p WHERE p.code_document = r.code_document AND p.statut = 'actif' LIMIT 1) AS encore_prete
                       FROM reservation r JOIN document d ON d.code_document = r.code_document
                      WHERE r.code_membre = ? AND r.statut = 'active' ORDER BY r.date_reservation");
$st->execute([$code]);
$reservations = $st->fetchAll();

$st = db()->prepare("SELECT p.*, d.titre FROM pret p JOIN document d ON d.code_document = p.code_document
                      WHERE p.code_membre = ? AND p.statut <> 'actif' ORDER BY p.date_pret DESC LIMIT 50");
$st->execute([$code]);
$historique = $st->fetchAll();

$retour = 'membre.php?code=' . $code;
$aujourdhui = date('Y-m-d');
require 'includes/header.php';
?>

<nav aria-label="fil d'Ariane"><ol class="breadcrumb">
  <li class="breadcrumb-item"><a href="membres.php">Membres</a></li>
  <li class="breadcrumb-item active" aria-current="page"><?= e($titre) ?></li>
</ol></nav>

<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-body">
        <h1 class="h4 mb-1"><?= e($titre) ?></h1>
        <p class="text-muted mb-3">Membre no <strong><?= $code ?></strong> · inscrit le <?= date_fr($membre['date_inscription']) ?></p>
        <p class="small mb-1"><i class="bi bi-geo-alt"></i> <?= e($membre['adresse_numero'] . ', ' . $membre['adresse_rue']) ?>, <?= e($membre['ville'] . ' (' . $membre['province'] . ')') ?></p>
        <p class="small mb-1"><i class="bi bi-telephone"></i> <?= e($membre['telephone']) ?></p>
        <p class="small mb-3"><i class="bi bi-envelope"></i> <?= e($membre['courriel']) ?></p>
        <div class="d-grid gap-2">
          <a href="comptoir.php?membre=<?= $code ?>" class="btn btn-biblio btn-sm"><i class="bi bi-arrow-left-right"></i> Prêt / réservation pour ce membre</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card mb-4">
      <div class="card-header fw-semibold">Emprunts actifs (<?= count($prets) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle table-empilee">
          <thead class="table-light"><tr><th>Document</th><th>Prêté le</th><th>Retour prévu</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($prets as $p): $retard = $p['date_retour_prevue'] < $aujourdhui; ?>
            <tr class="<?= $retard ? 'table-danger' : '' ?>">
              <td><a href="document.php?code=<?= (int)$p['code_document'] ?>"><?= e($p['titre']) ?></a>
                  <div class="small text-muted">Doc. <?= (int)$p['code_document'] ?> · prêt no <?= (int)$p['id_pret'] ?></div>
                  <?= $p['reserve'] ? '<span class="badge text-bg-info">Réservé par un autre membre</span>' : '' ?></td>
              <td data-label="Prêté le"><?= date_fr($p['date_pret']) ?></td>
              <td data-label="Retour prévu"><?= date_fr($p['date_retour_prevue']) ?><?= $retard ? ' <span class="badge text-bg-danger">Retard</span>' : '' ?></td>
              <td class="text-end text-nowrap">
                <form action="actions.php" method="post" class="d-inline">
                  <?= csrf_field() ?><input type="hidden" name="action" value="retour">
                  <input type="hidden" name="code_document" value="<?= (int)$p['code_document'] ?>">
                  <input type="hidden" name="retour" value="<?= $retour ?>">
                  <button class="btn btn-sm btn-success" title="Enregistrer le retour"><i class="bi bi-box-arrow-in-down"></i> Retour</button>
                </form>
                <form action="actions.php" method="post" class="d-inline" data-confirm="Annuler ce prêt ? (à utiliser en cas d'erreur de saisie)">
                  <?= csrf_field() ?><input type="hidden" name="action" value="annuler_pret">
                  <input type="hidden" name="id_pret" value="<?= (int)$p['id_pret'] ?>">
                  <input type="hidden" name="retour" value="<?= $retour ?>">
                  <button class="btn btn-sm btn-outline-danger" title="Annuler le prêt"><i class="bi bi-x-circle"></i> Annuler</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$prets): ?><tr><td colspan="4" class="text-muted">Aucun emprunt actif.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header fw-semibold">Réservations actives (<?= count($reservations) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle table-empilee">
          <thead class="table-light"><tr><th>Document</th><th>Réservé le</th><th>État</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php foreach ($reservations as $r): ?>
            <tr>
              <td><a href="document.php?code=<?= (int)$r['code_document'] ?>"><?= e($r['titre']) ?></a><div class="small text-muted">Réservation no <?= (int)$r['id_reservation'] ?></div></td>
              <td data-label="Réservé le"><?= date_fr($r['date_reservation']) ?></td>
              <td data-label="État"><?= $r['encore_prete'] ? '<span class="badge text-bg-warning">Encore prêté</span>' : '<span class="badge text-bg-success">Prêt à remettre</span>' ?></td>
              <td class="text-end text-nowrap">
                <?php if (!$r['encore_prete']): ?>
                  <form action="actions.php" method="post" class="d-inline">
                    <?= csrf_field() ?><input type="hidden" name="action" value="pret">
                    <input type="hidden" name="code_membre" value="<?= $code ?>"><input type="hidden" name="code_document" value="<?= (int)$r['code_document'] ?>">
                    <input type="hidden" name="retour" value="<?= $retour ?>">
                    <button class="btn btn-sm btn-success"><i class="bi bi-journal-arrow-up"></i> Prêter</button>
                  </form>
                <?php endif; ?>
                <form action="actions.php" method="post" class="d-inline" data-confirm="Annuler cette réservation ?">
                  <?= csrf_field() ?><input type="hidden" name="action" value="annuler_reservation">
                  <input type="hidden" name="id_reservation" value="<?= (int)$r['id_reservation'] ?>">
                  <input type="hidden" name="retour" value="<?= $retour ?>">
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Annuler</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$reservations): ?><tr><td colspan="4" class="text-muted">Aucune réservation active.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header fw-semibold">Historique des emprunts</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>No</th><th>Document</th><th>Prêté le</th><th>Rendu le</th><th>Statut</th></tr></thead>
          <tbody>
          <?php foreach ($historique as $h): ?>
            <tr><td><?= (int)$h['id_pret'] ?></td><td><?= e($h['titre']) ?></td><td><?= date_fr($h['date_pret']) ?></td>
                <td><?= date_fr($h['date_retour_effective']) ?></td><td><?= $h['statut'] === 'retourne' ? 'Retourné' : 'Annulé' ?></td></tr>
          <?php endforeach; ?>
          <?php if (!$historique): ?><tr><td colspan="5" class="text-muted">Aucun historique.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
