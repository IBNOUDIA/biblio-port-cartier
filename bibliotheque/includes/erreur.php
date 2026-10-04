<?php
/**
 * Page affichée quand une erreur imprévue survient (voir set_exception_handler dans init.php).
 * Aucun détail technique n'est montré : l'erreur complète est écrite dans le journal PHP.
 */
?>
<!doctype html>
<html lang="fr-CA">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Un problème est survenu — Bibliothèque de Port-Cartier</title>
  <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light min-vh-100 d-flex align-items-center py-4">
  <main class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm border-0">
          <div class="card-body p-4 text-center">
            <i class="bi bi-exclamation-triangle text-warning display-4"></i>
            <h1 class="h4 mt-3">Un problème est survenu</h1>
            <p class="mb-4">Votre demande n'a pas pu être traitée et <strong>rien n'a été enregistré</strong>.
              Vérifiez les informations saisies, puis réessayez. Si le problème persiste, avisez l'administrateur du site.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
              <a href="javascript:history.back()" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Page précédente</a>
              <a href="index.php" class="btn btn-biblio"><i class="bi bi-house"></i> Accueil</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</body>
</html>
