<?php
require 'includes/init.php';
exiger_connexion(['admin']);

$titre = 'Créer un employé';
$v = ['nom' => '', 'prenom' => '', 'courriel' => '', 'role' => 'employe'];
$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    foreach (array_keys($v) as $c) $v[$c] = post($c);
    foreach (['prenom', 'nom'] as $c) $v[$c] = majuscules_nom($v[$c]);
    $mdp  = (string)($_POST['mot_de_passe'] ?? '');
    $mdp2 = (string)($_POST['mot_de_passe2'] ?? '');

    if ($v['nom'] === '')    $erreurs[] = 'Le nom est obligatoire.';
    if ($v['prenom'] === '') $erreurs[] = 'Le prénom est obligatoire.';
    if (!filter_var($v['courriel'], FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Courriel invalide.';
    if (!in_array($v['role'], ['employe', 'admin'], true))  $erreurs[] = 'Type d\'employé invalide.';
    if (strlen($mdp) < 8) $erreurs[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    if ($mdp !== $mdp2)   $erreurs[] = 'Les mots de passe ne correspondent pas.';
    array_push($erreurs, ...erreurs_longueur($v, [
        'prenom' => ['Le prénom', 60], 'nom' => ['Le nom', 60], 'courriel' => ['Le courriel', 120],
    ]));

    if (!$erreurs) {
        $st = db()->prepare('SELECT 1 FROM membre WHERE courriel = ? UNION SELECT 1 FROM employe WHERE courriel = ?');
        $st->execute([$v['courriel'], $v['courriel']]);
        if ($st->fetch()) $erreurs[] = 'Ce courriel est déjà utilisé par un autre compte.';
    }

    if (!$erreurs) {
        try {
            db()->prepare('INSERT INTO employe (nom, prenom, courriel, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)')
                ->execute([$v['nom'], $v['prenom'], $v['courriel'], password_hash($mdp, PASSWORD_DEFAULT), $v['role']]);
            $type = $v['role'] === 'admin' ? 'administrateur' : 'employé';
            flash('success', "Compte $type créé pour {$v['prenom']} {$v['nom']} (code " . db()->lastInsertId() . ').');
            redirect('admin_employe.php');
        } catch (PDOException $ex) {
            if (!est_doublon($ex)) throw $ex;
            $erreurs[] = 'Ce courriel est déjà utilisé par un autre compte.';
        }
    }
}

$employes = db()->query('SELECT * FROM employe ORDER BY role, nom')->fetchAll();
require 'includes/header.php';
?>

<h1 class="h3 mb-3"><i class="bi bi-person-gear text-biblio"></i> Employés</h1>

<div class="row g-4">
  <div class="col-12 col-lg-5">
    <?php if ($erreurs): ?>
      <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($erreurs as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="card needs-validation" novalidate>
      <div class="card-header fw-semibold">Nouvel employé</div>
      <div class="card-body">
        <?= csrf_field() ?>
        <div class="row g-3">
          <div class="col-sm-6"><label class="form-label" for="prenom">Prénom *</label>
            <input class="form-control" id="prenom" name="prenom" value="<?= e($v['prenom']) ?>" required maxlength="60"></div>
          <div class="col-sm-6"><label class="form-label" for="nom">Nom *</label>
            <input class="form-control" id="nom" name="nom" value="<?= e($v['nom']) ?>" required maxlength="60"></div>
          <div class="col-12"><label class="form-label" for="courriel">Courriel (identifiant de connexion) *</label>
            <input type="email" class="form-control" id="courriel" name="courriel" value="<?= e($v['courriel']) ?>" required maxlength="120"></div>
          <div class="col-12">
            <span class="form-label d-block">Type de compte *</span>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="role" id="role_emp" value="employe" <?= $v['role'] !== 'admin' ? 'checked' : '' ?>>
              <label class="form-check-label" for="role_emp">Employé (normal)</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="role" id="role_adm" value="admin" <?= $v['role'] === 'admin' ? 'checked' : '' ?>>
              <label class="form-check-label" for="role_adm">Administrateur</label>
            </div>
          </div>
          <div class="col-sm-6"><label class="form-label" for="mot_de_passe">Mot de passe *</label>
            <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe" required minlength="8" autocomplete="new-password"></div>
          <div class="col-sm-6"><label class="form-label" for="mot_de_passe2">Confirmation *</label>
            <input type="password" class="form-control" id="mot_de_passe2" name="mot_de_passe2" required minlength="8" autocomplete="new-password">
            <div class="invalid-feedback">Les mots de passe doivent être identiques.</div></div>
        </div>
      </div>
      <div class="card-footer"><button class="btn btn-biblio"><i class="bi bi-check-lg"></i> Créer le compte</button></div>
    </form>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-header fw-semibold">Personnel (<?= count($employes) ?>)</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead class="table-light"><tr><th>Code</th><th>Nom</th><th>Courriel</th><th>Type</th></tr></thead>
          <tbody>
          <?php foreach ($employes as $em): ?>
            <tr>
              <td><?= (int)$em['code_employe'] ?></td>
              <td><?= e($em['prenom'] . ' ' . $em['nom']) ?></td>
              <td class="small"><?= e($em['courriel']) ?></td>
              <td><?= $em['role'] === 'admin' ? '<span class="badge text-bg-dark">Admin</span>' : '<span class="badge text-bg-secondary">Employé</span>' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require 'includes/footer.php'; ?>
