<?php
require 'includes/init.php';
exiger_connexion(['admin']);

$titre = 'Créer un membre';
$champs = ['nom', 'prenom', 'adresse_numero', 'adresse_rue', 'ville', 'province', 'telephone', 'courriel'];
$v = array_fill_keys($champs, '');
$v['ville'] = 'Port-Cartier';
$v['province'] = 'QC';
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    foreach ($champs as $c) $v[$c] = post($c);
    $mdp  = (string)($_POST['mot_de_passe'] ?? '');
    $mdp2 = (string)($_POST['mot_de_passe2'] ?? '');

    foreach (['nom' => 'Le nom', 'prenom' => 'Le prénom', 'adresse_numero' => 'Le numéro civique',
              'adresse_rue' => 'La rue', 'ville' => 'La ville'] as $c => $lib) {
        if ($v[$c] === '') $erreurs[] = "$lib est obligatoire.";
    }
    $provinces = ['QC', 'ON', 'NB', 'NS', 'PE', 'NL', 'MB', 'SK', 'AB', 'BC', 'YT', 'NT', 'NU'];
    if (!in_array($v['province'], $provinces, true)) $erreurs[] = 'Province invalide.';
    if (!preg_match('/^\(?\d{3}\)?[\s.-]?\d{3}[\s.-]?\d{4}$/', $v['telephone'])) $erreurs[] = 'Téléphone invalide (ex. 418-766-1234).';
    if (!filter_var($v['courriel'], FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Courriel invalide.';
    if (strlen($mdp) < 8) $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($mdp !== $mdp2) $erreurs[] = 'Les mots de passe ne correspondent pas.';

    if (!$erreurs) {
        $st = db()->prepare('SELECT 1 FROM membre WHERE courriel = ? UNION SELECT 1 FROM employe WHERE courriel = ?');
        $st->execute([$v['courriel'], $v['courriel']]);
        if ($st->fetch()) $erreurs[] = 'Ce courriel est déjà utilisé par un autre compte.';
    }

    if (!$erreurs) {
        $st = db()->prepare('INSERT INTO membre (nom, prenom, adresse_numero, adresse_rue, ville, province, telephone, courriel, mot_de_passe)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $st->execute([$v['nom'], $v['prenom'], $v['adresse_numero'], $v['adresse_rue'], $v['ville'], $v['province'],
                      $v['telephone'], $v['courriel'], password_hash($mdp, PASSWORD_DEFAULT)]);
        $code = (int)db()->lastInsertId();
        flash('success', "Membre créé : {$v['prenom']} {$v['nom']} — code de membre $code.");
        redirect('membre.php?code=' . $code);
    }
}

require 'includes/header.php';
?>

<h1 class="h3 mb-3"><i class="bi bi-person-plus text-biblio"></i> Créer un membre</h1>

<?php if ($erreurs): ?>
  <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<form method="post" class="card card-body needs-validation" novalidate>
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label" for="prenom">Prénom *</label>
      <input class="form-control" id="prenom" name="prenom" value="<?= e($v['prenom']) ?>" required maxlength="60"></div>
    <div class="col-md-6"><label class="form-label" for="nom">Nom *</label>
      <input class="form-control" id="nom" name="nom" value="<?= e($v['nom']) ?>" required maxlength="60"></div>

    <div class="col-4 col-md-2"><label class="form-label" for="adresse_numero">No civique *</label>
      <input class="form-control" id="adresse_numero" name="adresse_numero" value="<?= e($v['adresse_numero']) ?>" required maxlength="10"></div>
    <div class="col-8 col-md-4"><label class="form-label" for="adresse_rue">Rue *</label>
      <input class="form-control" id="adresse_rue" name="adresse_rue" value="<?= e($v['adresse_rue']) ?>" required maxlength="100"></div>
    <div class="col-8 col-md-4"><label class="form-label" for="ville">Ville *</label>
      <input class="form-control" id="ville" name="ville" value="<?= e($v['ville']) ?>" required maxlength="60"></div>
    <div class="col-4 col-md-2"><label class="form-label" for="province">Province *</label>
      <select class="form-select" id="province" name="province">
        <?php foreach (['QC', 'ON', 'NB', 'NS', 'PE', 'NL', 'MB', 'SK', 'AB', 'BC', 'YT', 'NT', 'NU'] as $p): ?>
          <option <?= $v['province'] === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select></div>

    <div class="col-md-6"><label class="form-label" for="telephone">Téléphone *</label>
      <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= e($v['telephone']) ?>" required placeholder="418-766-1234"
             pattern="\(?\d{3}\)?[\s.\-]?\d{3}[\s.\-]?\d{4}">
      <div class="invalid-feedback">Format attendu : 418-766-1234</div></div>
    <div class="col-md-6"><label class="form-label" for="courriel">Courriel *</label>
      <input type="email" class="form-control" id="courriel" name="courriel" value="<?= e($v['courriel']) ?>" required maxlength="120">
      <div class="invalid-feedback">Courriel invalide.</div></div>

    <div class="col-md-6"><label class="form-label" for="mot_de_passe">Mot de passe * <small class="text-muted">(8 caractères min.)</small></label>
      <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required minlength="8" autocomplete="new-password"></div>
    <div class="col-md-6"><label class="form-label" for="mot_de_passe2">Confirmer le mot de passe *</label>
      <input type="password" class="form-control" id="mot_de_passe2" name="mot_de_passe2" required minlength="8" autocomplete="new-password">
      <div class="invalid-feedback">Les mots de passe doivent être identiques.</div></div>
  </div>
  <div class="mt-4 d-flex gap-2">
    <button class="btn btn-biblio"><i class="bi bi-check-lg"></i> Créer le membre</button>
    <a href="membres.php" class="btn btn-outline-secondary">Annuler</a>
  </div>
</form>

<?php require 'includes/footer.php'; ?>
