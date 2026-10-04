<?php
/**
 * Fichier inclus en tête de chaque page : configuration, BD, session, sécurité.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/biblio.php';

// Erreur imprévue : on la journalise et on affiche une page claire, sans détails techniques
ini_set('display_errors', '0');
set_exception_handler(function (Throwable $ex): void {
    error_log((string)$ex);
    while (ob_get_level()) ob_end_clean();
    if (!headers_sent()) http_response_code(500);
    require __DIR__ . '/erreur.php';
});

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Déconnexion automatique (ex. : employé qui oublie de se déconnecter au comptoir)
if (isset($_SESSION['user'], $_SESSION['derniere_activite'])
    && time() - $_SESSION['derniere_activite'] > INACTIVITE_MINUTES * 60) {
    unset($_SESSION['user']);
    session_regenerate_id(true);
    flash('info', 'Vous avez été déconnecté après ' . INACTIVITE_MINUTES . ' minutes d\'inactivité. Veuillez vous reconnecter.');
}
$_SESSION['derniere_activite'] = time();

/* ---------- Authentification et rôles ---------- */
// Rôles possibles : 'membre', 'employe', 'admin'

function utilisateur(): ?array
{
    return $_SESSION['user'] ?? null;
}

function role(): ?string
{
    return $_SESSION['user']['role'] ?? null;
}

function est_membre(): bool    { return role() === 'membre'; }
function est_personnel(): bool { return in_array(role(), ['employe', 'admin'], true); }
function est_admin(): bool     { return role() === 'admin'; }

/** Bloque la page si l'utilisateur n'est pas connecté ou n'a pas le bon rôle. */
function exiger_connexion(array $roles = []): void
{
    if (!utilisateur()) {
        // Pas de second message si on vient d'annoncer la déconnexion automatique
        if (empty($_SESSION['flash'])) flash('warning', 'Veuillez vous connecter pour accéder au site.');
        redirect('login.php');
    }
    if ($roles && !in_array(role(), $roles, true)) {
        flash('danger', "Accès refusé : vous n'avez pas les droits nécessaires pour cette page.");
        redirect('index.php');
    }
}

/** Code de l'employé connecté (null si c'est un membre). */
function code_employe_connecte(): ?int
{
    return est_personnel() ? (int)$_SESSION['user']['code'] : null;
}
