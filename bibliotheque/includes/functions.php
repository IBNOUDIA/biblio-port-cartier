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
        http_response_code(400);
        exit('Jeton de sécurité invalide. Retournez à la page précédente et rechargez-la.');
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

/** URL de retour sûre (seulement une page locale du site). */
function url_retour(?string $url, string $defaut = 'index.php'): string
{
    $url = (string)$url;
    return preg_match('#^[a-z_]+\.php(\?[\w=&%.\-+]*)?$#i', $url) ? $url : $defaut;
}
