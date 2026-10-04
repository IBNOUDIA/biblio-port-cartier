<?php
require 'includes/init.php';
exiger_connexion(['membre']);

$titre  = 'Mon dossier';
$code   = (int)utilisateur()['code'];
$membre = trouver_membre($code);

$st = db()->prepare("SELECT p.*, d.titre, d.auteur FROM pret p JOIN document d ON d.code_document = p.code_document
                      WHERE p.code_membre = ? AND p.statut = 'actif' ORDER BY p.date_retour_prevue");
$st->execute([$code]);
$prets = $st->fetchAll();

$st = db()->prepare("SELECT r.*, d.titre, d.auteur,
                            (SELECT 1 FROM pret p WHERE p.code_document = r.code_document AND p.statut = 'actif' LIMIT 1) AS encore_prete
                       FROM reservation r JOIN document d ON d.code_document = r.code_document
                      WHERE r.code_membre = ? AND r.statut = 'active' ORDER BY r.date_reservation");
$st->execute([$code]);
$reservations = $st->fetchAll();

$st = db()->prepare("SELECT p.*, d.titre FROM pret p JOIN document d ON d.code_document = p.code_document
                      WHERE p.code_membre = ? AND p.statut <> 'actif' ORDER BY p.date_pret DESC LIMIT 25");
$st->execute([$code]);
$historique = $st->fetchAll();

$aujourdhui = date('Y-m-d');
require 'includes/header.php';
?>

<h1 class="h3 mb-3"><i class="bi bi-person-badge text-biblio"></i> Mon dossier</h1>

<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="card h-100">
      <div class="card-header fw-semibold">Mes informations</div>
      <div class="card-body small">
        <p class="mb-1"><strong><?= e($membre['prenom'] . ' ' . $membre['nom']) ?></strong></p>
        <p class="mb-1">Code de membre : <strong><?= $code ?></strong></p>
        <p class="mb-1"><?= e($membre['adresse_numero'] . ', ' . $membre['adresse_rue']) ?><br><?= e($membre['ville'] . ' (' . $membre['province'] . ')') ?></p>
        <p class="mb-1"><i class="bi bi-telephone"></i> <?= e($membre['telephone']) ?></p>
        <p class="mb-0"><i class="bi bi-envelope"></i> <?= e($membre['courriel']) ?></p>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card mb-4">
      <div class="card-header fw-semibold">Mes prêts en cours (<?= count($prets) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light"><tr><th>Titre</th><th>Emprunté le</th><th>À rendre le</th></tr></thead>
          <tbody>
          <?php foreach ($prets as $p): $retard = $p['date_retour_prevue'] < $aujourdhui; ?>
            <tr class="<?= $retard ? 'table-danger' : '' ?>">
              <td><a href="document.php?code=<?= (int)$p['code_document'] ?>"><?= e($p['titre']) ?></a><div class="small text-muted"><?= e($p['auteur']) ?></div></td>
              <td><?= date_fr($p['date_pret']) ?></td>
              <td><?= date_fr($p['date_retour_prevue']) ?><?= $retard ? ' <span class="badge text-bg-danger">En retard</span>' : '' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$prets): ?><tr><td colspan="3" class="text-muted">Aucun prêt en cours.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header fw-semibold">Mes réservations (<?= count($reservations) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light"><tr><th>Titre</th><th>Réservé le</th><th>État</th></tr></thead>
          <tbody>
          <?php foreach ($reservations as $r): ?>
            <tr>
              <td><a href="document.php?code=<?= (int)$r['code_document'] ?>"><?= e($r['titre']) ?></a></td>
              <td><?= date_fr($r['date_reservation']) ?></td>
              <td><?= $r['encore_prete'] ? '<span class="badge text-bg-warning">En attente du retour</span>'
                                         : '<span class="badge text-bg-success">Prêt à emprunter</span>' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$reservations): ?><tr><td colspan="3" class="text-muted">Aucune réservation.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="card-footer small text-muted">Pour annuler une réservation, communiquez avec le personnel de la bibliothèque.</div>
    </div>

    <div class="card">
      <div class="card-header fw-semibold">Mon historique d'emprunts</div>
      <div class="table-responsive">
        <table class="table table-sm mb-0">
          <thead class="table-light"><tr><th>Titre</th><th>Emprunté le</th><th>Rendu le</th><th>Statut</th></tr></thead>
          <tbody>
          <?php foreach ($historique as $h): ?>
            <tr>
              <td><?= e($h['titre']) ?></td><td><?= date_fr($h['date_pret']) ?></td><td><?= date_fr($h['date_retour_effective']) ?></td>
              <td><?= $h['statut'] === 'retourne' ? 'Retourné' : 'Annulé' ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$historique): ?><tr><td colspan="4" class="text-muted">Aucun emprunt passé.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
