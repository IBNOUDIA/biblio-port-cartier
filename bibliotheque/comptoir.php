<?php
/**
 * Comptoir du personnel : prêt, réservation et retour de documents pour les membres.
 */
require 'includes/init.php';
exiger_connexion(['employe', 'admin']);

$titre = 'Comptoir';
$doc    = (int)($_GET['doc'] ?? 0) ?: '';
$membre = (int)($_GET['membre'] ?? 0) ?: '';

require 'includes/header.php';
?>

<h1 class="h3 mb-1"><i class="bi bi-arrow-left-right text-biblio"></i> Comptoir</h1>
<p class="text-muted mb-4">Saisissez les codes (ou scannez-les). Durée d'un prêt : <?= DUREE_PRET_JOURS ?> jours.</p>

<div class="row g-4">
  <!-- PRÊT -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 border-success">
      <div class="card-header bg-success text-white fw-semibold"><i class="bi bi-journal-arrow-up"></i> Nouveau prêt</div>
      <form action="actions.php" method="post" class="card-body needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="pret">
        <input type="hidden" name="retour" value="comptoir.php">
        <div class="mb-3">
          <label class="form-label" for="pret_membre">Code du membre</label>
          <input type="number" class="form-control" id="pret_membre" name="code_membre" value="<?= e($membre) ?>" required min="1">
          <div class="invalid-feedback">Code du membre requis.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="pret_doc">Code du document</label>
          <input type="number" class="form-control" id="pret_doc" name="code_document" value="<?= e($doc) ?>" required min="1">
          <div class="invalid-feedback">Code du document requis.</div>
        </div>
        <button class="btn btn-success w-100">Enregistrer le prêt</button>
      </form>
    </div>
  </div>

  <!-- RETOUR -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 border-primary">
      <div class="card-header bg-primary text-white fw-semibold"><i class="bi bi-box-arrow-in-down"></i> Retour d'un document</div>
      <form action="actions.php" method="post" class="card-body needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="retour">
        <input type="hidden" name="retour" value="comptoir.php">
        <div class="mb-3">
          <label class="form-label" for="ret_doc">Code du document</label>
          <input type="number" class="form-control" id="ret_doc" name="code_document" value="<?= e($doc) ?>" required min="1">
          <div class="invalid-feedback">Code du document requis.</div>
        </div>
        <p class="small text-muted">Si le document est réservé, un avertissement s'affichera pour vous indiquer de le mettre de côté.</p>
        <button class="btn btn-primary w-100">Enregistrer le retour</button>
      </form>
    </div>
  </div>

  <!-- RÉSERVATION -->
  <div class="col-12 col-md-6 col-xl-4">
    <div class="card h-100 border-info">
      <div class="card-header bg-info fw-semibold"><i class="bi bi-bookmark-plus"></i> Nouvelle réservation</div>
      <form action="actions.php" method="post" class="card-body needs-validation" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reserver">
        <input type="hidden" name="retour" value="comptoir.php">
        <div class="mb-3">
          <label class="form-label" for="res_membre">Code du membre</label>
          <input type="number" class="form-control" id="res_membre" name="code_membre" value="<?= e($membre) ?>" required min="1">
          <div class="invalid-feedback">Code du membre requis.</div>
        </div>
        <div class="mb-3">
          <label class="form-label" for="res_doc">Code du document</label>
          <input type="number" class="form-control" id="res_doc" name="code_document" value="<?= e($doc) ?>" required min="1">
          <div class="invalid-feedback">Code du document requis.</div>
        </div>
        <button class="btn btn-info w-100">Enregistrer la réservation</button>
      </form>
    </div>
  </div>
</div>

<p class="small text-muted mt-3"><i class="bi bi-info-circle"></i> Vous ne connaissez pas un code ?
  Cherchez le <a href="recherche.php">document</a> ou le <a href="membres.php">membre</a>.
  Pour annuler un prêt ou une réservation, ouvrez le dossier du membre ou la fiche du document.</p>

<?php require 'includes/footer.php'; ?>
