# Scénario de démonstration — Bibliothèque de Port-Cartier

À suivre devant l'instructeur, dans l'ordre du barème (environ 15 minutes).
Mot de passe de tous les comptes : **Biblio123!**

## Avant de commencer

- [ ] Double-cliquer sur `demarrer_site.bat` : le site s'ouvre sur la page de connexion.
- [ ] Double-cliquer sur `reinitialiser_bd.bat`, puis taper **O** : la base revient à son état de démonstration,
      avec des dates recalculées à partir d'aujourd'hui (les essais des répétitions sont effacés).
- [ ] Les données de départ sont en place : *Les Misérables* (50001) est prêté à Julie, en retard,
      et réservé par Awa ; *Le Petit Prince* (50005) est réservé par Nicolas ; *Mission Galaxie* (50008) est prêté à Nicolas.
- [ ] Après 20 minutes sans rien faire, le site déconnecte automatiquement : il suffit de se reconnecter.

## 1. Partie membre — compte `10001` (Julie Bouchard)

| # | Barème | Faire | Résultat attendu |
|---|---|---|---|
| 1 | Connexion (10) | Identifiant `10001`, mot de passe `Biblio123!` | « Bonjour Julie ! » et ses chiffres : prêts, réservations, retards |
| 2 | Recherche par mots-clés (10) | **Catalogue** → taper `verne` → Rechercher | *Vingt mille lieues sous les mers* |
| 3 | | Taper `enfant`, cocher **Disponibles**, puis Rechercher | Les documents pour enfants, puis seulement ceux qui sont libres |
| 4 | Prêt par un membre (10) | Sur *Le Comte de Monte-Cristo*, cliquer **Emprunter** → OK | « Prêt enregistré… Date de retour prévue : … » (dans 21 jours) |
| 5 | Réservation par un membre (10) | Sur *Mission Galaxie* (déjà prêté), cliquer **Réserver** | « Réservation enregistrée… il sera mis de côté à son retour » |
| 6 | Règle de réservation | Regarder *Le Petit Prince* | Badge « Réservé » et pas de bouton Emprunter : il est réservé par un autre membre |
| 7 | | Menu **Mon dossier** | Ses prêts (*Les Misérables* en rouge « En retard ») et ses réservations |

Cliquer sur **Déconnexion**.

## 2. Partie employé — compte `employe@biblio-portcartier.ca` (Marc Gagnon)

| # | Barème | Faire | Résultat attendu |
|---|---|---|---|
| 8 | Connexion (10) | Se connecter avec le compte employé | « Bonjour Marc ! », menus Comptoir, Membres, Rapports |
| 9 | Retour (5) | **Comptoir** → Retour d'un document → code `50001` | Message vert « Retour enregistré… en retard de … jour(s) » **et** message jaune « ATTENTION : ce document est RÉSERVÉ par Awa Diallo… » |
| 10 | Règle de réservation | Comptoir → Nouveau prêt → membre `10001`, document `50005` | Refusé : « réservé par un autre membre (Nicolas Côté)… » |
| 11 | Rechercher un membre (10) | **Membres** → taper `Côté` → Rechercher | Le dossier de Nicolas s'ouvre : emprunts actifs et réservations actives |
| 12 | Annuler une réservation (10) | Dans le dossier de Nicolas, ligne *Le Petit Prince* → **Annuler** → OK | « La réservation no … a été annulée. » |
| 13 | Annuler un prêt | Ligne *Mission Galaxie* → **Annuler** → OK | « Le prêt no … a été annulé. Le document est de nouveau disponible. » |
| 14 | Rapports (20) | **Rapports** → cliquer chacun des 5 onglets | Liste des membres, des retards, de tous les documents, des documents réservés, des documents prêtés |

Cliquer sur **Déconnexion**.

## 3. Partie administrateur — compte `admin@biblio-portcartier.ca` (Amar Dia)

| # | Barème | Faire | Résultat attendu |
|---|---|---|---|
| 15 | Connexion | Se connecter avec le compte administrateur | « Bonjour Amar ! » et le menu **Administration** en plus |
| 16 | Créer un membre (10) | Administration → **Créer un membre** → remplir le formulaire | « Membre créé : … — code de membre … » et son dossier s'ouvre |
| 17 | Créer un employé | Administration → **Créer un employé** → choisir **Employé (normal)** | Le nouveau compte apparaît dans la liste du personnel |
| 18 | Créer un administrateur | Même formulaire, choisir **Administrateur** | Apparaît avec le badge « Admin » |
| 19 | | (Facultatif) se connecter avec le nouveau membre | La connexion fonctionne avec son code ou son courriel |

## 4. Site réactif (5)

| # | Faire | Résultat attendu |
|---|---|---|
| 20 | Rétrécir la fenêtre du navigateur, ou F12 puis Ctrl+Maj+M et choisir un téléphone | Le menu se replie en bouton ☰ ; dans le catalogue, chaque document devient une carte avec ses boutons visibles |

## Si une question est posée

- **Où est l'historique d'un document ?** Ouvrir sa fiche avec le compte employé : tous ses prêts et réservations y sont, même les anciens.
- **Que se passe-t-il si on ferme le navigateur sans se déconnecter ?** Déconnexion automatique après 20 minutes.
- **Les mots de passe sont-ils protégés ?** Oui, ils sont enregistrés chiffrés dans la base.
