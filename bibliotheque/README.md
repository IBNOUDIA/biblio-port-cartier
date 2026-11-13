# Bibliothèque municipale de Port-Cartier

Projet d'intégration 2 — Programmation web.
Site de gestion de la bibliothèque : les membres cherchent, empruntent et réservent des documents ;
les employés s'occupent des prêts, des retours, des annulations et consultent les rapports ;
l'administrateur crée les comptes.

Fait avec **PHP 8, MySQL, HTML, CSS, JavaScript et Bootstrap 5** (inclus dans le projet, le site marche sans Internet).

## Installation

1. Copier le dossier `biblio-port-cartier` dans le dossier web du serveur
   (Laragon : `C:\laragon\www\`, XAMPP : `C:\xampp\htdocs\`, WAMP : `C:\wamp64\www\`).
2. Démarrer Apache et MySQL.
3. Importer `bibliotheque/database/bibliotheque.sql` (HeidiSQL, Adminer ou phpMyAdmin).
   Ce fichier crée la base `bibliotheque_port_cartier` avec des données d'essai.
4. Si MySQL a un mot de passe, l'indiquer dans `config.php`.
5. Ouvrir http://localhost/biblio-port-cartier/bibliotheque/

Avec Laragon déjà installé, un double-clic sur `demarrer_site.bat` démarre Apache et MySQL et ouvre le site.
Juste avant une démonstration, un double-clic sur `reinitialiser_bd.bat` remet les données d'essai à neuf
(les dates des prêts sont recalculées à partir du jour même).

## Comptes d'essai

Mot de passe de tous les comptes : **Biblio123!**

| Rôle | Identifiant |
|---|---|
| Administrateur (Amar Dia) | `admin@biblio-portcartier.ca` |
| Employé (Marc Gagnon) | `employe@biblio-portcartier.ca` |
| Membre (Julie Bouchard) | `10001` ou `julie@exemple.ca` |
| Membre (Nicolas Côté) | `10002` ou `nicolas@exemple.ca` |
| Membre (Awa Diallo) | `10003` ou `awa@exemple.ca` |

Deux cas sont prêts pour l'essai :
- *Les Misérables* (50001) est prêté à Julie, en retard, et réservé par Awa : au retour, un message avertit l'employé.
- *Le Petit Prince* (50005) est réservé par Nicolas : personne d'autre ne peut l'emprunter.

## Ce que chacun peut faire

| Qui | Quoi | Où |
|---|---|---|
| Tous | Se connecter | Page de connexion |
| Tous | Chercher des documents par mots-clés (titre, auteur, genre, catégorie, ISBN…) | Catalogue |
| Membre | Emprunter et réserver | Boutons « Emprunter » et « Réserver » du catalogue |
| Membre | Voir ses prêts et ses réservations | Mon dossier |
| Employé | Prêt, retour et réservation pour un membre | Comptoir |
| Employé | Annuler un prêt ou une réservation | Dossier du membre ou fiche du document |
| Employé | Chercher un membre et voir ses prêts et réservations en cours | Membres |
| Employé | Rapports : membres, retards, tous les documents, documents réservés, documents prêtés | Rapports |
| Administrateur | Tout ce que fait l'employé, plus créer des membres et des employés (normal ou admin) | Administration |

Règles respectées :
- un prêt dure 21 jours (modifiable dans `config.php`) ;
- un document réservé ne peut être prêté qu'au membre qui l'a réservé, sauf si un employé annule la réservation ;
- un document déjà prêté peut être réservé ; à son retour, l'employé est averti ;
- rien n'est jamais effacé : chaque prêt et chaque réservation reste dans l'historique.

Le site s'adapte au téléphone : menu repliable, et sur petit écran les listes s'affichent en cartes.

## Base de données

| Table | Contenu |
|---|---|
| `membre` | code (clé), nom, prénom, adresse (numéro, rue, ville, province), téléphone, courriel, mot de passe |
| `employe` | code (clé), nom, prénom, courriel, mot de passe, rôle (employé ou admin) |
| `document` | code (clé), titre, auteur, année, catégorie, type (enfant, ado, adulte), genre, description, ISBN |
| `categorie`, `genre` | Listes des catégories (roman, DVD, jeu vidéo…) et des genres (drame, horreur…) |
| `pret` | membre, document, date du prêt, date de retour prévue, date de retour réelle, statut |
| `reservation` | membre, document, date de réservation, statut |

Les mots de passe sont enregistrés chiffrés (`password_hash`).

## Fichiers

```
biblio-port-cartier/
├── demarrer_site.bat      démarre le site (Laragon)
├── reinitialiser_bd.bat   remet les données de démonstration à neuf
├── sauvegarder_bd.bat     crée la sauvegarde de la base à remettre
├── Documentation/         récapitulatif Word et schémas
└── bibliotheque/
    ├── config.php         connexion à la base, durée d'un prêt
    ├── login.php, logout.php
    ├── index.php          accueil
    ├── recherche.php      catalogue et recherche
    ├── document.php       fiche d'un document
    ├── mon_compte.php     dossier du membre connecté
    ├── comptoir.php       prêts, retours, réservations (employés)
    ├── membres.php        recherche d'un membre
    ├── membre.php         dossier d'un membre (employés)
    ├── rapports.php       les 5 rapports
    ├── admin_membre.php   créer un membre
    ├── admin_employe.php  créer un employé
    ├── actions.php        enregistre les prêts, retours, réservations et annulations
    ├── includes/          fonctions communes, en-tête et pied de page
    ├── assets/            style, script, Bootstrap
    └── database/          script de création de la base
```

## Documentation

Le dossier `Documentation/` (à la racine du projet) contient :
- `Recapitulatif_Bibliotheque.docx` : présentation, technologies, architecture, modèle de données,
  correspondance pages ↔ tables, cas d'utilisation et barème, processus de prêt, sécurité ;
- `schemas/` : les 5 schémas en image (modèle de données, architecture, correspondance pages ↔ tables,
  cas d'utilisation, processus de prêt).

## Remise

Juste avant la remise, double-cliquer sur `sauvegarder_bd.bat` (MySQL doit être démarré).
Le fichier `bibliotheque/database/sauvegarde_bd_AAAA-MM-JJ_HHMM.sql` est créé : c'est la sauvegarde à remettre.
