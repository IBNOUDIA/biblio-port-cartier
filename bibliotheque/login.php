<?php
require 'includes/init.php';

if (utilisateur()) redirect('index.php');

$erreur = '';
$identifiant = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $identifiant = post('identifiant');
    $mdp = (string)($_POST['mot_de_passe'] ?? '');

    if ($identifiant === '' || $mdp === '') {
        $erreur = 'Veuillez saisir votre identifiant et votre mot de passe.';
    } else {
        $compte = null;

        // 1) Employé ou administrateur (courriel)
        $st = db()->prepare('SELECT code_employe AS code, nom, prenom, mot_de_passe, role FROM employe WHERE courriel = ?');
        $st->execute([$identifiant]);
        $ligne = $st->fetch();

        // 2) Sinon, membre (courriel ou code de membre)
        if (!$ligne) {
            $champ = ctype_digit($identifiant) ? 'code_membre' : 'courriel';
            $st = db()->prepare("SELECT code_membre AS code, nom, prenom, mot_de_passe, 'membre' AS role FROM membre WHERE $champ = ?");
            $st->execute([$identifiant]);
            $ligne = $st->fetch();
        }

        if ($ligne && password_verify($mdp, $ligne['mot_de_passe'])) {
            session_regenerate_id(true); // protège contre la fixation de session
            $_SESSION['user'] = [
                'code'   => (int)$ligne['code'],
                'nom'    => $ligne['nom'],
                'prenom' => $ligne['prenom'],
                'role'   => $ligne['role'],
            ];
            flash('success', "Bienvenue, {$ligne['prenom']} !");
            redirect('index.php');
        }
        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}
$messages = flashes();
?>
<!doctype html>
<html lang="fr-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — Bibliothèque de Port-Cartier</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="page-login min-vh-100 d-flex align-items-center py-4">
  <main class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <div class="text-center text-white mb-4">
          <i class="bi bi-book-half display-4"></i>
          <h1 class="h3 mt-2 mb-0">Bibliothèque municipale</h1>
          <p class="lead mb-0">de Port-Cartier</p>
        </div>

        <div class="card shadow-lg border-0">
          <div class="card-body p-4">
            <h2 class="h5 mb-3">Connexion</h2>

            <?php foreach ($messages as $f): ?>
              <div class="alert alert-<?= e($f['type']) ?> py-2"><?= e($f['msg']) ?></div>
            <?php endforeach; ?>
            <?php if ($erreur): ?>
              <div class="alert alert-danger py-2" role="alert"><i class="bi bi-exclamation-triangle"></i> <?= e($erreur) ?></div>
            <?php endif; ?>

            <form method="post" class="needs-validation" novalidate>
              <?= csrf_field() ?>
              <div class="mb-3">
                <label for="identifiant" class="form-label">Courriel ou code de membre</label>
                <input type="text" class="form-control form-control-lg" id="identifiant" name="identifiant"
                       value="<?= e($identifiant) ?>" required autofocus autocomplete="username">
                <div class="invalid-feedback">Ce champ est obligatoire.</div>
              </div>
              <div class="mb-4">
                <label for="mot_de_passe" class="form-label">Mot de passe</label>
                <div class="input-group">
                  <input type="password" class="form-control form-control-lg" id="mot_de_passe" name="mot_de_passe"
                         required autocomplete="current-password">
                  <button class="btn btn-outline-secondary" type="button" data-toggle-password="mot_de_passe"
                          aria-label="Afficher le mot de passe"><i class="bi bi-eye"></i></button>
                  <div class="invalid-feedback">Ce champ est obligatoire.</div>
                </div>
              </div>
              <button type="submit" class="btn btn-biblio btn-lg w-100">
                <i class="bi bi-box-arrow-in-right"></i> Se connecter
              </button>
            </form>
          </div>
          <div class="card-footer bg-light small text-muted text-center">
            Pas encore membre ? Présentez-vous au comptoir avec une preuve de résidence.
          </div>
        </div>
      </div>
    </div>
  </main>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>
</html>
