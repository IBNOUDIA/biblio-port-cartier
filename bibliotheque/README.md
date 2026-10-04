# Bibliothèque municipale de Port-Cartier
Projet d'intégration 2 — Programmation web
Technologies : **HTML5, CSS3, JavaScript, PHP 8 (PDO), MySQL / MariaDB, Bootstrap 5.3**

---

## 1. Installation (XAMPP, WAMP ou Laragon)

1. Copier le dossier `biblio-port-cartier` dans :
   - XAMPP : `C:\xampp\htdocs\`
   - WAMP : `C:\wamp64\www\`
   - Laragon : `C:\laragon\www\`
2. Démarrer **Apache** et **MySQL**.
3. Ouvrir **phpMyAdmin** (`http://localhost/phpmyadmin`) → onglet **Importer** →
   choisir `database/bibliotheque.sql` → **Exécuter**.
   (La base `bibliotheque_port_cartier` est créée avec des données de démonstration.)
4. Si votre MySQL a un mot de passe, modifier `config.php` (`DB_USER`, `DB_PASS`).
5. Ouvrir `http://localhost/biblio-port-cartier/`

Ouvrir le dossier dans **Visual Studio Code** (Fichier → Ouvrir le dossier).
Extensions utiles : *PHP Intelephense*, *PHP Server*, *MySQL* (cweijan).

> Bootstrap et Bootstrap Icons sont inclus dans `assets/vendor/` : le site fonctionne même sans Internet.

## 2. Comptes de démonstration

Mot de passe de tous les comptes : **`Biblio123!`**

| Rôle | Identifiant |
|---|---|
| Administrateur | `admin@biblio-portcartier.ca` |
| Employé | `employe@biblio-portcartier.ca` |
| Membre (Julie Bouchard, code 10001) | `julie@exemple.ca` ou `10001` |
| Membre (Nicolas Côté, 10002) | `nicolas@exemple.ca` |
| Membre (Awa Diallo, 10003) | `awa@exemple.ca` |

Situations prêtes à tester :
- **50001 – Les Misérables** : prêté à Julie, **en retard**, et **réservé par Awa** → faire le retour au comptoir affiche l'avertissement de réservation.
- **50005 – Le Petit Prince** : disponible mais réservé par Nicolas → seul Nicolas peut l'emprunter.

## 3. Modèle de données (normalisé, 3FN)

```
categorie (id_categorie PK, nom)
genre     (id_genre PK, nom)

membre  (code_membre PK, nom, prenom, adresse_numero, adresse_rue, ville, province,
         telephone, courriel UNIQUE, mot_de_passe, date_inscription)
employe (code_employe PK, nom, prenom, courriel UNIQUE, mot_de_passe,
         role ENUM('employe','admin'), date_creation)

document (code_document PK, titre, auteur, annee_publication,
          id_categorie FK→categorie, type_public ENUM('enfant','ado','adulte'),
          id_genre FK→genre, description, isbn UNIQUE NULL)

pret (id_pret PK, code_membre FK→membre, code_document FK→document,
      code_employe FK→employe NULL, date_pret, date_retour_prevue,
      date_retour_effective NULL, statut ENUM('actif','retourne','annule'),
      code_employe_cloture FK→employe NULL)

reservation (id_reservation PK, code_membre FK→membre, code_document FK→document,
             code_employe FK→employe NULL, date_reservation,
             statut ENUM('active','completee','annulee'), date_fin NULL,
             code_employe_cloture FK→employe NULL)
```

Relations :
- membre 1—N pret, document 1—N pret (table associative = **historique complet** des prêts)
- membre 1—N reservation, document 1—N reservation
- categorie 1—N document, genre 1—N document
- employe 1—N pret / reservation (qui a traité la transaction)

**Application transactionnelle :** aucune ligne n'est jamais supprimée. Un retour ou une annulation
change le `statut` et ajoute une date ; on conserve donc tout l'historique de chaque document.

## 4. Règles d'affaires implantées (`includes/biblio.php`)

| Règle | Où |
|---|---|
| Un document déjà prêté ne peut pas être reprêté | `creer_pret()` |
| Un document réservé ne peut être prêté qu'au membre qui l'a réservé (sinon l'employé annule la réservation) | `creer_pret()` |
| Quand le réservataire emprunte, sa réservation passe à « complétée » | `creer_pret()` |
| Un document prêté peut être réservé (une réservation active par document) | `creer_reservation()` |
| Au retour d'un document réservé, un avertissement s'affiche pour l'employé | `retourner_document()` |
| Durée d'un prêt : 21 jours (modifiable dans `config.php`) | `DUREE_PRET_JOURS` |
| Transactions SQL + `SELECT … FOR UPDATE` pour éviter deux prêts simultanés | `creer_pret()` |

## 5. Correspondance avec le barème

| Élément du barème | Pts | Pages |
|---|---|---|
| Membres et employés se connectent | 10 | `login.php` (courriel ou code membre), `logout.php` |
| Les membres peuvent faire des prêts | 10 | Bouton **Emprunter** dans `recherche.php` / `document.php` ; employés : `comptoir.php` |
| Les membres peuvent faire des réservations | 10 | Bouton **Réserver** ; employés : `comptoir.php` |
| Recherche de documents par mots-clés | 10 | `recherche.php` (titre, auteur, description, ISBN, genre, catégorie, année + filtres) |
| Les employés font des retours | 5 | `comptoir.php`, `membre.php`, `document.php` |
| Annuler des prêts et des réservations | 10 | `membre.php` (boutons Annuler) |
| Rechercher un membre (emprunts et réservations actifs) | 10 | `membres.php` → `membre.php` |
| Rapports à l'écran (5) | 20 | `rapports.php` : membres, retards, documents, réservés, prêtés (+ impression) |
| L'administrateur crée membres et employés (normal/admin) | 10 | `admin_membre.php`, `admin_employe.php` |
| Site réactif | 5 | Bootstrap 5 : menu hamburger, grilles, tableaux défilants sur mobile |

## 6. Structure des fichiers

```
biblio-port-cartier/
├── config.php              Paramètres BD et durée de prêt
├── login.php / logout.php  Connexion
├── index.php               Tableau de bord (selon le rôle)
├── recherche.php           Catalogue + recherche par mots-clés
├── document.php            Fiche d'un document + historique (personnel)
├── mon_compte.php          Dossier du membre connecté
├── comptoir.php            Prêt / retour / réservation (personnel)
├── membres.php, membre.php Recherche et dossier d'un membre (personnel)
├── rapports.php            5 rapports (personnel)
├── admin_membre.php        Création de membres (admin)
├── admin_employe.php       Création d'employés (admin)
├── actions.php             Traitement de toutes les transactions (POST)
├── includes/               init, db (PDO), fonctions, logique métier, gabarit
├── assets/                 css, js, vendor (Bootstrap)
└── database/bibliotheque.sql
```

## 7. Sécurité et traitement des erreurs
- Requêtes préparées PDO partout (injection SQL).
- Mots de passe hachés avec `password_hash()` / `password_verify()`.
- Jeton CSRF sur tous les formulaires POST ; `htmlspecialchars()` sur tout affichage (XSS).
- Contrôle d'accès par rôle sur chaque page (`exiger_connexion()`), régénération de l'ID de session.
- Validation côté client (Bootstrap + JavaScript) **et** côté serveur.
- Erreurs SQL journalisées (`error_log`) et message clair à l'utilisateur.

## 8. Remise — sauvegarde de la base de données
phpMyAdmin → sélectionner `bibliotheque_port_cartier` → **Exporter** → SQL → Exécuter.
Ou en ligne de commande :
```
mysqldump -u root -p --default-character-set=utf8mb4 bibliotheque_port_cartier > sauvegarde.sql
```
