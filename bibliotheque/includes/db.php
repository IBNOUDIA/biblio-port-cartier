<?php
/**
 * Connexion à la base de données (une seule connexion, réutilisée par toute la page).
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            error_log('Connexion BD impossible : ' . $e->getMessage());
            http_response_code(500);
            exit('<h1>Service indisponible</h1><p>Impossible de se connecter à la base de données. '
               . 'Vérifiez que MySQL est démarré et que <code>config.php</code> est bien configuré.</p>');
        }
    }
    return $pdo;
}
