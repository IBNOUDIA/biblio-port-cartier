<?php
/**
 * Reçoit tous les formulaires d'opération : prêt, réservation, retour,
 * annulation d'un prêt et annulation d'une réservation.
 */
require 'includes/init.php';
exiger_connexion();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('index.php');
csrf_verifier();

$action  = post('action');
$retour  = url_retour($_POST['retour'] ?? null);
$employe = code_employe_connecte();
$moi     = (int)utilisateur()['code'];

// Code du membre concerné : un membre agit toujours pour lui-même,
// un employé indique le membre pour qui il agit.
$codeMembre = est_membre() ? $moi : (int)post('code_membre');
$codeDoc    = (int)post('code_document');

// Actions réservées au personnel
$actionsPersonnel = ['retour', 'annuler_pret', 'annuler_reservation'];
if (in_array($action, $actionsPersonnel, true) && !est_personnel()) {
    flash('danger', 'Action réservée aux employés.');
    redirect($retour);
}

switch ($action) {
    case 'pret':
        $r = creer_pret($codeMembre, $codeDoc, $employe);
        break;
    case 'reserver':
        $r = creer_reservation($codeMembre, $codeDoc, $employe);
        break;
    case 'retour':
        $r = retourner_document($codeDoc, $employe);
        break;
    case 'annuler_pret':
        $r = annuler_pret((int)post('id_pret'), $employe);
        break;
    case 'annuler_reservation':
        $r = annuler_reservation((int)post('id_reservation'), $employe);
        break;
    default:
        $r = ['ok' => false, 'msg' => 'Action inconnue.'];
}

flash($r['ok'] ? 'success' : 'danger', $r['msg']);
if (!empty($r['alerte'])) {
    // Document retourné mais réservé : message bien visible pour l'employé
    flash('warning', $r['alerte']);
}
redirect($retour);
