<?php
/**
 * Règles de la bibliothèque : prêts, réservations, retours, annulations.
 * Chaque fonction retourne ['ok' => vrai ou faux, 'msg' => message à afficher].
 * Prêt, réservation et retour se font dans une transaction SQL : tout est enregistré, ou rien.
 */

/* ---------------- Lectures ---------------- */

function trouver_membre(int $code): ?array
{
    $st = db()->prepare('SELECT * FROM membre WHERE code_membre = ?');
    $st->execute([$code]);
    return $st->fetch() ?: null;
}

function trouver_document(int $code): ?array
{
    $st = db()->prepare(
        'SELECT d.*, c.nom AS categorie, g.nom AS genre
           FROM document d
           JOIN categorie c ON c.id_categorie = d.id_categorie
           JOIN genre g     ON g.id_genre     = d.id_genre
          WHERE d.code_document = ?'
    );
    $st->execute([$code]);
    return $st->fetch() ?: null;
}

/** Prêt en cours d'un document (ou null s'il est sur les rayons). */
function pret_actif(int $codeDoc): ?array
{
    $st = db()->prepare(
        "SELECT p.*, m.nom, m.prenom
           FROM pret p JOIN membre m ON m.code_membre = p.code_membre
          WHERE p.code_document = ? AND p.statut = 'actif'
          LIMIT 1"
    );
    $st->execute([$codeDoc]);
    return $st->fetch() ?: null;
}

/** Réservation active d'un document (ou null). */
function reservation_active(int $codeDoc): ?array
{
    $st = db()->prepare(
        "SELECT r.*, m.nom, m.prenom, m.telephone
           FROM reservation r JOIN membre m ON m.code_membre = r.code_membre
          WHERE r.code_document = ? AND r.statut = 'active'
          ORDER BY r.date_reservation
          LIMIT 1"
    );
    $st->execute([$codeDoc]);
    return $st->fetch() ?: null;
}

/** Fragment SQL réutilisable : statut de disponibilité d'un document « d ». */
function sql_colonnes_statut(): string
{
    return "(SELECT p.code_membre FROM pret p
              WHERE p.code_document = d.code_document AND p.statut = 'actif' LIMIT 1) AS pret_membre,
            (SELECT p.date_retour_prevue FROM pret p
              WHERE p.code_document = d.code_document AND p.statut = 'actif' LIMIT 1) AS pret_retour_prevu,
            (SELECT r.code_membre FROM reservation r
              WHERE r.code_document = d.code_document AND r.statut = 'active' LIMIT 1) AS res_membre";
}

/** Badge Bootstrap de disponibilité. */
function badge_statut(array $doc): string
{
    if (!empty($doc['pret_membre'])) {
        $txt = 'Prêté';
        if (!empty($doc['pret_retour_prevu'])) {
            $txt .= ' (retour ' . date_fr($doc['pret_retour_prevu']) . ')';
        }
        $html = '<span class="badge text-bg-warning">' . e($txt) . '</span>';
        if (!empty($doc['res_membre'])) {
            $html .= ' <span class="badge text-bg-info">Réservé</span>';
        }
        return $html;
    }
    if (!empty($doc['res_membre'])) {
        return '<span class="badge text-bg-info">Réservé</span>';
    }
    return '<span class="badge text-bg-success">Disponible</span>';
}

/* ---------------- Transactions ---------------- */

/**
 * Enregistre un prêt.
 * Règles : le document ne doit pas déjà être prêté ; s'il est réservé,
 * seul le membre qui l'a réservé peut l'emprunter (sa réservation devient « complétée »).
 */
function creer_pret(int $codeMembre, int $codeDoc, ?int $codeEmploye): array
{
    $membre = trouver_membre($codeMembre);
    if (!$membre) return ['ok' => false, 'msg' => "Aucun membre ne porte le code $codeMembre."];
    $doc = trouver_document($codeDoc);
    if (!$doc) return ['ok' => false, 'msg' => "Aucun document ne porte le code $codeDoc."];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Verrouille la ligne du document pour éviter deux prêts simultanés
        $pdo->prepare('SELECT code_document FROM document WHERE code_document = ? FOR UPDATE')->execute([$codeDoc]);

        $pret = pret_actif($codeDoc);
        if ($pret) {
            $pdo->rollBack();
            $qui = $pret['code_membre'] == $codeMembre ? 'à ce membre' : 'à un autre membre';
            return ['ok' => false, 'msg' => "« {$doc['titre']} » est déjà prêté $qui (retour prévu le "
                . date_fr($pret['date_retour_prevue']) . '). Il peut toutefois être réservé.'];
        }

        $res = reservation_active($codeDoc);
        if ($res && (int)$res['code_membre'] !== $codeMembre) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => "« {$doc['titre']} » est réservé par un autre membre "
                . "({$res['prenom']} {$res['nom']}, code {$res['code_membre']}). "
                . "Il ne peut être prêté qu'à ce membre, à moins qu'un employé n'annule la réservation."];
        }

        $st = $pdo->prepare(
            'INSERT INTO pret (code_membre, code_document, code_employe, date_pret, date_retour_prevue)
             VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? DAY))'
        );
        $st->execute([$codeMembre, $codeDoc, $codeEmploye, DUREE_PRET_JOURS]);

        if ($res) {
            $pdo->prepare("UPDATE reservation SET statut = 'completee', date_fin = NOW(), code_employe_cloture = ?
                            WHERE id_reservation = ?")
                ->execute([$codeEmploye, $res['id_reservation']]);
        }
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($ex->getMessage());
        return ['ok' => false, 'msg' => "Erreur technique : le prêt n'a pas été enregistré."];
    }

    $retour = date_fr(date('Y-m-d', strtotime('+' . DUREE_PRET_JOURS . ' days')));
    return ['ok' => true, 'msg' => "Prêt enregistré : « {$doc['titre']} » à {$membre['prenom']} {$membre['nom']}. "
        . "Date de retour prévue : $retour."];
}

/**
 * Enregistre une réservation.
 * Règles : un document (même prêté) peut être réservé ; une seule réservation active par document ;
 * un membre ne peut pas réserver un document qu'il a déjà en main.
 */
function creer_reservation(int $codeMembre, int $codeDoc, ?int $codeEmploye): array
{
    $membre = trouver_membre($codeMembre);
    if (!$membre) return ['ok' => false, 'msg' => "Aucun membre ne porte le code $codeMembre."];
    $doc = trouver_document($codeDoc);
    if (!$doc) return ['ok' => false, 'msg' => "Aucun document ne porte le code $codeDoc."];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('SELECT code_document FROM document WHERE code_document = ? FOR UPDATE')->execute([$codeDoc]);

        $res = reservation_active($codeDoc);
        if ($res) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => (int)$res['code_membre'] === $codeMembre
                ? "Ce membre a déjà une réservation active pour « {$doc['titre']} »."
                : "« {$doc['titre']} » est déjà réservé par un autre membre."];
        }
        $pret = pret_actif($codeDoc);
        if ($pret && (int)$pret['code_membre'] === $codeMembre) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => "Ce membre a déjà « {$doc['titre']} » en prêt."];
        }

        $pdo->prepare('INSERT INTO reservation (code_membre, code_document, code_employe, date_reservation)
                       VALUES (?, ?, ?, NOW())')
            ->execute([$codeMembre, $codeDoc, $codeEmploye]);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($ex->getMessage());
        return ['ok' => false, 'msg' => "Erreur technique : la réservation n'a pas été enregistrée."];
    }

    $suite = $pret ? ' Le document est présentement prêté ; il sera mis de côté à son retour.'
                   : ' Le document est mis de côté au comptoir.';
    return ['ok' => true, 'msg' => "Réservation enregistrée : « {$doc['titre']} » pour {$membre['prenom']} {$membre['nom']}." . $suite];
}

/**
 * Retour d'un document. Si le document est réservé, le message avertit l'employé.
 */
function retourner_document(int $codeDoc, ?int $codeEmploye): array
{
    $doc = trouver_document($codeDoc);
    if (!$doc) return ['ok' => false, 'msg' => "Aucun document ne porte le code $codeDoc."];

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Verrouille la ligne du document pour éviter deux retours simultanés
        $pdo->prepare('SELECT code_document FROM document WHERE code_document = ? FOR UPDATE')->execute([$codeDoc]);

        $pret = pret_actif($codeDoc);
        if (!$pret) {
            $pdo->rollBack();
            return ['ok' => false, 'msg' => "« {$doc['titre']} » n'est pas présentement prêté."];
        }

        $pdo->prepare("UPDATE pret SET statut = 'retourne', date_retour_effective = CURDATE(), code_employe_cloture = ?
                        WHERE id_pret = ?")
            ->execute([$codeEmploye, $pret['id_pret']]);
        $res = reservation_active($codeDoc);
        $pdo->commit();
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log($ex->getMessage());
        return ['ok' => false, 'msg' => "Erreur technique : le retour n'a pas été enregistré."];
    }

    $retard = (strtotime($pret['date_retour_prevue']) < strtotime(date('Y-m-d')))
        ? ' Retour en retard de ' . (int)((strtotime(date('Y-m-d')) - strtotime($pret['date_retour_prevue'])) / 86400) . ' jour(s).'
        : '';

    $resultat = ['ok' => true, 'msg' => "Retour enregistré : « {$doc['titre']} » (emprunté par {$pret['prenom']} {$pret['nom']}).$retard"];

    if ($res) {
        $resultat['alerte'] = "ATTENTION : ce document est RÉSERVÉ par {$res['prenom']} {$res['nom']} "
            . "(code {$res['code_membre']}, tél. {$res['telephone']}) depuis le " . date_fr($res['date_reservation'])
            . '. Ne le remettez pas en rayon : mettez-le de côté et avisez le membre.';
    }
    return $resultat;
}

function annuler_pret(int $idPret, ?int $codeEmploye): array
{
    $st = db()->prepare("UPDATE pret SET statut = 'annule', code_employe_cloture = ?
                          WHERE id_pret = ? AND statut = 'actif'");
    $st->execute([$codeEmploye, $idPret]);
    return $st->rowCount()
        ? ['ok' => true,  'msg' => "Le prêt no $idPret a été annulé. Le document est de nouveau disponible."]
        : ['ok' => false, 'msg' => "Ce prêt n'existe pas ou n'est plus actif."];
}

function annuler_reservation(int $idRes, ?int $codeEmploye): array
{
    $st = db()->prepare("UPDATE reservation SET statut = 'annulee', date_fin = NOW(), code_employe_cloture = ?
                          WHERE id_reservation = ? AND statut = 'active'");
    $st->execute([$codeEmploye, $idRes]);
    return $st->rowCount()
        ? ['ok' => true,  'msg' => "La réservation no $idRes a été annulée."]
        : ['ok' => false, 'msg' => "Cette réservation n'existe pas ou n'est plus active."];
}
