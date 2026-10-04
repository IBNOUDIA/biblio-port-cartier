<?php
/** Échappe une valeur pour l'affichage HTML (protection XSS). */
function e($valeur): string
{
    return htmlspecialchars((string)($valeur ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Messages flash (affichés une seule fois, après une redirection). */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Jeton anti-CSRF pour tous les formulaires POST. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_verifier(): void
{
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        // Souvent : page restée ouverte trop longtemps (session expirée). Rien n'est enregistré.
        flash('warning', "La page était restée ouverte trop longtemps : rien n'a été enregistré. Veuillez recommencer.");
        redirect(url_retour($_POST['retour'] ?? basename($_SERVER['SCRIPT_NAME'])));
    }
}

function date_fr(?string $date): string
{
    return $date ? date('d/m/Y', strtotime($date)) : '—';
}

/** Valeur d'un champ POST nettoyée. */
function post(string $cle): string
{
    return trim((string)($_POST[$cle] ?? ''));
}

/** Motif LIKE « contient ce mot » : les caractères spéciaux % et _ sont cherchés tels quels. */
function like_contient(string $mot): string
{
    return '%' . addcslashes($mot, '%_\\') . '%';
}

/**
 * Vérifie que les champs ne dépassent pas la taille prévue dans la base de données.
 * $limites : ['champ' => ['Libellé', longueur max], ...]. Retourne la liste des erreurs.
 */
function erreurs_longueur(array $valeurs, array $limites): array
{
    $erreurs = [];
    foreach ($limites as $champ => [$libelle, $max]) {
        if (mb_strlen($valeurs[$champ] ?? '') > $max) {
            $erreurs[] = "$libelle ne doit pas dépasser $max caractères.";
        }
    }
    return $erreurs;
}

/** Vrai si l'exception vient d'une valeur en double (ex. courriel déjà utilisé). */
function est_doublon(PDOException $ex): bool
{
    return ($ex->errorInfo[1] ?? null) === 1062;
}

/** URL de retour sûre (seulement une page locale du site). */
function url_retour(?string $url, string $defaut = 'index.php'): string
{
    $url = (string)$url;
    return preg_match('#^[a-z_]+\.php(\?[\w=&%.\-+]*)?$#i', $url) ? $url : $defaut;
}
