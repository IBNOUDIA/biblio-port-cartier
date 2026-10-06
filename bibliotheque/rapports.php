<?php
require 'includes/init.php';
exiger_connexion(['employe', 'admin']);

$rapports = [
    'membres'   => ['Liste des membres',         'bi-people'],
    'retards'   => ['Liste des retards',         'bi-alarm'],
    'documents' => ['Liste de tous les documents', 'bi-collection'],
    'reserves'  => ['Documents réservés',        'bi-bookmark-star'],
    'pretes'    => ['Documents prêtés',          'bi-journal-arrow-up'],
];
$r = array_key_exists($_GET['r'] ?? '', $rapports) ? $_GET['r'] : 'membres';
$titre = $rapports[$r][0];

switch ($r) {
    case 'membres':
        $lignes = db()->query("SELECT m.*,
                (SELECT COUNT(*) FROM pret p WHERE p.code_membre = m.code_membre AND p.statut = 'actif') AS nb_prets,
                (SELECT COUNT(*) FROM reservation x WHERE x.code_membre = m.code_membre AND x.statut = 'active') AS nb_res
              FROM membre m ORDER BY m.nom, m.prenom")->fetchAll();
        break;
    case 'retards':
        $lignes = db()->query("SELECT p.*, d.titre, m.nom, m.prenom, m.telephone, m.courriel,
                                      DATEDIFF(CURDATE(), p.date_retour_prevue) AS jours
                                 FROM pret p
                                 JOIN document d ON d.code_document = p.code_document
                                 JOIN membre m   ON m.code_membre   = p.code_membre
                                WHERE p.statut = 'actif' AND p.date_retour_prevue < CURDATE()
                                ORDER BY jours DESC")->fetchAll();
        break;
    case 'documents':
        $lignes = db()->query('SELECT d.*, c.nom AS categorie, g.nom AS genre, ' . sql_colonnes_statut() . '
                                 FROM document d
                                 JOIN categorie c ON c.id_categorie = d.id_categorie
                                 JOIN genre g     ON g.id_genre     = d.id_genre
                                ORDER BY c.nom, d.titre')->fetchAll();
        break;
    case 'reserves':
        $lignes = db()->query("SELECT r.*, d.titre, m.nom, m.prenom, m.telephone,
                                      (SELECT p.date_retour_prevue FROM pret p WHERE p.code_document = r.code_document AND p.statut = 'actif' LIMIT 1) AS retour_prevu
                                 FROM reservation r
                                 JOIN document d ON d.code_document = r.code_document
                                 JOIN membre m   ON m.code_membre   = r.code_membre
                                WHERE r.statut = 'active'
                                ORDER BY r.date_reservation")->fetchAll();
        break;
    case 'pretes':
        $lignes = db()->query("SELECT p.*, d.titre, m.nom, m.prenom,
                                      (SELECT 1 FROM reservation x WHERE x.code_document = p.code_document AND x.statut = 'active' LIMIT 1) AS reserve
                                 FROM pret p
                                 JOIN document d ON d.code_document = p.code_document
                                 JOIN membre m   ON m.code_membre   = p.code_membre
                                WHERE p.statut = 'actif'
                                ORDER BY p.date_retour_prevue")->fetchAll();
        break;
}
$aujourdhui = date('Y-m-d');
require 'includes/header.php';
?>

<h1 class="h3 mb-3"><i class="bi bi-clipboard-data text-biblio"></i> Rapports</h1>

<ul class="nav nav-pills flex-column flex-sm-row mb-4">
  <?php foreach ($rapports as $cle => [$lib, $ico]): ?>
    <li class="nav-item"><a class="nav-link<?= $cle === $r ? ' active' : '' ?>" href="?r=<?= $cle ?>"><i class="bi <?= $ico ?>"></i> <?= $lib ?></a></li>
  <?php endforeach; ?>
</ul>

<h2 class="h5"><?= e($titre) ?> <span class="badge text-bg-secondary"><?= count($lignes) ?></span></h2>
<p class="small text-muted">Généré le <?= date('d/m/Y à H:i') ?></p>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
<?php if ($r === 'membres'): ?>
  <thead class="table-light"><tr><th>Code</th><th>Nom</th><th>Adresse</th><th>Téléphone</th><th>Courriel</th><th class="text-center">Prêts</th><th class="text-center">Réserv.</th></tr></thead>
  <tbody>
  <?php foreach ($lignes as $m): ?>
    <tr>
      <td><?= (int)$m['code_membre'] ?></td>
      <td><a href="membre.php?code=<?= (int)$m['code_membre'] ?>"><?= e($m['nom'] . ', ' . $m['prenom']) ?></a></td>
      <td class="small"><?= e($m['adresse_numero'] . ', ' . $m['adresse_rue'] . ', ' . $m['ville'] . ' (' . $m['province'] . ')') ?></td>
      <td class="text-nowrap"><?= e($m['telephone']) ?></td>
      <td><?= e($m['courriel']) ?></td>
      <td class="text-center"><?= (int)$m['nb_prets'] ?></td>
      <td class="text-center"><?= (int)$m['nb_res'] ?></td>
    </tr>
  <?php endforeach; ?>

<?php elseif ($r === 'retards'): ?>
  <thead class="table-light"><tr><th>Document</th><th>Membre</th><th>Téléphone</th><th>Prêté le</th><th>Retour prévu</th><th class="text-center">Jours de retard</th></tr></thead>
  <tbody>
  <?php foreach ($lignes as $p): ?>
    <tr>
      <td><a href="document.php?code=<?= (int)$p['code_document'] ?>"><?= e($p['titre']) ?></a></td>
      <td><a href="membre.php?code=<?= (int)$p['code_membre'] ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?></a> <span class="small text-muted">(<?= (int)$p['code_membre'] ?>)</span></td>
      <td class="text-nowrap"><?= e($p['telephone']) ?></td>
      <td><?= date_fr($p['date_pret']) ?></td>
      <td><?= date_fr($p['date_retour_prevue']) ?></td>
      <td class="text-center"><span class="badge text-bg-danger"><?= (int)$p['jours'] ?></span></td>
    </tr>
  <?php endforeach; ?>

<?php elseif ($r === 'documents'): ?>
  <thead class="table-light"><tr><th>Code</th><th>Titre</th><th>Auteur</th><th>Année</th><th>Catégorie</th><th>Public</th><th>Genre</th><th>ISBN</th><th>Statut</th></tr></thead>
  <tbody>
  <?php foreach ($lignes as $d): ?>
    <tr>
      <td><?= (int)$d['code_document'] ?></td>
      <td><a href="document.php?code=<?= (int)$d['code_document'] ?>"><?= e($d['titre']) ?></a></td>
      <td><?= e($d['auteur']) ?></td>
      <td><?= e($d['annee_publication']) ?></td>
      <td><?= e($d['categorie']) ?></td>
      <td class="text-capitalize"><?= e($d['type_public']) ?></td>
      <td><?= e($d['genre']) ?></td>
      <td class="small text-nowrap"><?= e($d['isbn'] ?: '—') ?></td>
      <td><?= badge_statut($d) ?></td>
    </tr>
  <?php endforeach; ?>

<?php elseif ($r === 'reserves'): ?>
  <thead class="table-light"><tr><th>No</th><th>Document</th><th>Réservé par</th><th>Téléphone</th><th>Date</th><th>État du document</th></tr></thead>
  <tbody>
  <?php foreach ($lignes as $x): ?>
    <tr>
      <td><?= (int)$x['id_reservation'] ?></td>
      <td><a href="document.php?code=<?= (int)$x['code_document'] ?>"><?= e($x['titre']) ?></a></td>
      <td><a href="membre.php?code=<?= (int)$x['code_membre'] ?>"><?= e($x['prenom'] . ' ' . $x['nom']) ?></a></td>
      <td class="text-nowrap"><?= e($x['telephone']) ?></td>
      <td><?= date_fr($x['date_reservation']) ?></td>
      <td><?= $x['retour_prevu'] ? '<span class="badge text-bg-warning">Prêté — retour ' . date_fr($x['retour_prevu']) . '</span>'
                                 : '<span class="badge text-bg-success">Au comptoir</span>' ?></td>
    </tr>
  <?php endforeach; ?>

<?php else: /* pretes */ ?>
  <thead class="table-light"><tr><th>No</th><th>Document</th><th>Emprunteur</th><th>Prêté le</th><th>Retour prévu</th><th>État</th></tr></thead>
  <tbody>
  <?php foreach ($lignes as $p): $retard = $p['date_retour_prevue'] < $aujourdhui; ?>
    <tr class="<?= $retard ? 'table-danger' : '' ?>">
      <td><?= (int)$p['id_pret'] ?></td>
      <td><a href="document.php?code=<?= (int)$p['code_document'] ?>"><?= e($p['titre']) ?></a></td>
      <td><a href="membre.php?code=<?= (int)$p['code_membre'] ?>"><?= e($p['prenom'] . ' ' . $p['nom']) ?></a></td>
      <td><?= date_fr($p['date_pret']) ?></td>
      <td><?= date_fr($p['date_retour_prevue']) ?></td>
      <td><?= $retard ? '<span class="badge text-bg-danger">En retard</span>' : '<span class="badge text-bg-success">Dans les délais</span>' ?>
          <?= $p['reserve'] ? '<span class="badge text-bg-info">Réservé</span>' : '' ?></td>
    </tr>
  <?php endforeach; ?>
<?php endif; ?>
<?php if (!$lignes): ?><tr><td colspan="9" class="text-muted">Aucune donnée pour ce rapport.</td></tr><?php endif; ?>
  </tbody>
</table>
</div>

<?php require 'includes/footer.php'; ?>
