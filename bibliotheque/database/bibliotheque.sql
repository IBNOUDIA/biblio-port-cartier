-- =====================================================================
--  Bibliothèque municipale de Port-Cartier
--  Projet d'intégration 2 — Programmation web
--  Script de création + données de démonstration (MySQL 8 / MariaDB 10+)
--
--  Mot de passe de TOUS les comptes de démonstration : Biblio123!
-- =====================================================================

SET NAMES utf8mb4;   -- indispensable pour que les accents soient bien importés

DROP DATABASE IF EXISTS bibliotheque_port_cartier;
CREATE DATABASE bibliotheque_port_cartier
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bibliotheque_port_cartier;

-- ---------------------------------------------------------------------
--  Tables de référence (normalisation : évite la répétition de texte)
-- ---------------------------------------------------------------------
CREATE TABLE categorie (
  id_categorie INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE genre (
  id_genre INT AUTO_INCREMENT PRIMARY KEY,
  nom      VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Membres
-- ---------------------------------------------------------------------
CREATE TABLE membre (
  code_membre      INT AUTO_INCREMENT PRIMARY KEY,
  nom              VARCHAR(60)  NOT NULL,
  prenom           VARCHAR(60)  NOT NULL,
  adresse_numero   VARCHAR(10)  NOT NULL,
  adresse_rue      VARCHAR(100) NOT NULL,
  ville            VARCHAR(60)  NOT NULL,
  province         CHAR(2)      NOT NULL DEFAULT 'QC',
  telephone        VARCHAR(20)  NOT NULL,
  courriel         VARCHAR(120) NOT NULL UNIQUE,
  mot_de_passe     VARCHAR(255) NOT NULL,          -- hachage password_hash()
  date_inscription TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=10001;

-- ---------------------------------------------------------------------
--  Employés (rôle normal ou administrateur)
-- ---------------------------------------------------------------------
CREATE TABLE employe (
  code_employe INT AUTO_INCREMENT PRIMARY KEY,
  nom          VARCHAR(60)  NOT NULL,
  prenom       VARCHAR(60)  NOT NULL,
  courriel     VARCHAR(120) NOT NULL UNIQUE,
  mot_de_passe VARCHAR(255) NOT NULL,
  role         ENUM('employe','admin') NOT NULL DEFAULT 'employe',
  date_creation TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB AUTO_INCREMENT=101;

-- ---------------------------------------------------------------------
--  Documents (livres, films, jeux, consoles, CD)
-- ---------------------------------------------------------------------
CREATE TABLE document (
  code_document     INT AUTO_INCREMENT PRIMARY KEY,
  titre             VARCHAR(200) NOT NULL,
  auteur            VARCHAR(150) NOT NULL,          -- auteur ou producteur
  annee_publication SMALLINT     NULL,
  id_categorie      INT          NOT NULL,
  type_public       ENUM('enfant','ado','adulte') NOT NULL,
  id_genre          INT          NOT NULL,
  description       TEXT         NULL,
  isbn              VARCHAR(17)  NULL UNIQUE,       -- seulement pour les livres
  CONSTRAINT fk_doc_categorie FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie),
  CONSTRAINT fk_doc_genre     FOREIGN KEY (id_genre)     REFERENCES genre(id_genre),
  INDEX idx_doc_titre (titre),
  INDEX idx_doc_auteur (auteur)
) ENGINE=InnoDB AUTO_INCREMENT=50001;

-- ---------------------------------------------------------------------
--  Prêts : UNE LIGNE PAR TRANSACTION -> historique complet conservé
--  statut : actif (en cours) | retourne | annule
-- ---------------------------------------------------------------------
CREATE TABLE pret (
  id_pret               INT AUTO_INCREMENT PRIMARY KEY,
  code_membre           INT  NOT NULL,
  code_document         INT  NOT NULL,
  code_employe          INT  NULL,                  -- NULL = prêt fait en ligne par le membre
  date_pret             DATE NOT NULL,
  date_retour_prevue    DATE NOT NULL,
  date_retour_effective DATE NULL,
  statut                ENUM('actif','retourne','annule') NOT NULL DEFAULT 'actif',
  code_employe_cloture  INT  NULL,                  -- employé qui a fait le retour / l'annulation
  CONSTRAINT fk_pret_membre   FOREIGN KEY (code_membre)   REFERENCES membre(code_membre),
  CONSTRAINT fk_pret_document FOREIGN KEY (code_document) REFERENCES document(code_document),
  CONSTRAINT fk_pret_employe  FOREIGN KEY (code_employe)  REFERENCES employe(code_employe),
  CONSTRAINT fk_pret_employe_cl FOREIGN KEY (code_employe_cloture) REFERENCES employe(code_employe),
  INDEX idx_pret_doc_statut (code_document, statut),
  INDEX idx_pret_membre_statut (code_membre, statut)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
--  Réservations : une ligne par transaction
--  statut : active | completee (transformée en prêt) | annulee
-- ---------------------------------------------------------------------
CREATE TABLE reservation (
  id_reservation   INT AUTO_INCREMENT PRIMARY KEY,
  code_membre      INT      NOT NULL,
  code_document    INT      NOT NULL,
  code_employe     INT      NULL,                   -- NULL = réservée en ligne par le membre
  date_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  statut           ENUM('active','completee','annulee') NOT NULL DEFAULT 'active',
  date_fin         DATETIME NULL,
  code_employe_cloture INT  NULL,
  CONSTRAINT fk_res_membre   FOREIGN KEY (code_membre)   REFERENCES membre(code_membre),
  CONSTRAINT fk_res_document FOREIGN KEY (code_document) REFERENCES document(code_document),
  CONSTRAINT fk_res_employe  FOREIGN KEY (code_employe)  REFERENCES employe(code_employe),
  CONSTRAINT fk_res_employe_cl FOREIGN KEY (code_employe_cloture) REFERENCES employe(code_employe),
  INDEX idx_res_doc_statut (code_document, statut),
  INDEX idx_res_membre_statut (code_membre, statut)
) ENGINE=InnoDB;

-- =====================================================================
--  DONNÉES DE DÉMONSTRATION
-- =====================================================================
INSERT INTO categorie (nom) VALUES
  ('Roman'), ('Bande dessinée'), ('Documentaire'), ('Jeu vidéo'),
  ('Console'), ('DVD'), ('Blu-ray'), ('CD');

INSERT INTO genre (nom) VALUES
  ('Comédie'), ('Drame'), ('Horreur'), ('Science-fiction'), ('Documentaire'),
  ('Aventure'), ('Policier'), ('Fantastique'), ('Jeunesse'), ('Musique');

-- Hachage de « Biblio123! »
SET @mdp = '$2y$10$ClrNTk.fMFW3D8p8mMz44OwOyxSYFHU/IJ8Q6CfbCwYNYQ8wjriWS';

INSERT INTO employe (nom, prenom, courriel, mot_de_passe, role) VALUES
  ('Tremblay', 'Sophie', 'admin@biblio-portcartier.ca',   @mdp, 'admin'),    -- 101
  ('Gagnon',   'Marc',   'employe@biblio-portcartier.ca', @mdp, 'employe');  -- 102

INSERT INTO membre (nom, prenom, adresse_numero, adresse_rue, ville, province, telephone, courriel, mot_de_passe) VALUES
  ('Bouchard', 'Julie',   '12',  'rue Élie-Rochefort',  'Port-Cartier', 'QC', '418-766-1234', 'julie@exemple.ca',   @mdp), -- 10001
  ('Côté',     'Nicolas', '245', 'boulevard des Îles',  'Port-Cartier', 'QC', '418-766-5678', 'nicolas@exemple.ca', @mdp), -- 10002
  ('Diallo',   'Awa',     '8',   'rue Shelter-Bay',     'Port-Cartier', 'QC', '418-766-9012', 'awa@exemple.ca',     @mdp), -- 10003
  ('Lavoie',   'Émile',   '77',  'rue des Pins',        'Sept-Îles',    'QC', '418-962-3456', 'emile@exemple.ca',   @mdp); -- 10004

INSERT INTO document (titre, auteur, annee_publication, id_categorie, type_public, id_genre, description, isbn) VALUES
  ('Les Misérables',                 'Victor Hugo',          1862, 1, 'adulte', 2, 'Le destin de Jean Valjean dans la France du XIXe siècle.', '978-2253096344'),     -- 50001
  ('Vingt mille lieues sous les mers','Jules Verne',         1870, 1, 'ado',    6, 'Le capitaine Nemo et le Nautilus explorent les océans.',    '978-2253006329'),     -- 50002
  ('Le Comte de Monte-Cristo',       'Alexandre Dumas',      1844, 1, 'adulte', 6, 'Vengeance et trahison : l\'histoire d\'Edmond Dantès.',     '978-2253098058'),     -- 50003
  ('Frankenstein',                   'Mary Shelley',         1818, 1, 'adulte', 3, 'Un savant donne vie à une créature qu\'il ne peut contrôler.', '978-2290349106'),  -- 50004
  ('Le Petit Prince',                'Antoine de Saint-Exupéry', 1943, 1, 'enfant', 9, 'Un aviateur rencontre un petit prince venu d\'une autre planète.', '978-2070612758'), -- 50005
  ('Les aventures du chat Pompon',   'Studio Nordet',        2021, 2, 'enfant', 1, 'Bande dessinée humoristique pour les 6-9 ans.',             '978-2900000011'),     -- 50006
  ('Les phares de la Côte-Nord',     'Collectif régional',   2019, 3, 'adulte', 5, 'Histoire et photographies des phares du golfe du Saint-Laurent.', '978-2900000028'), -- 50007
  ('Mission Galaxie',                'Pixel Boréal',         2023, 4, 'ado',    4, 'Jeu vidéo d\'exploration spatiale (Switch).',               NULL),                 -- 50008
  ('Console de jeu portable',        'Fabricant générique',  2022, 5, 'ado',    6, 'Console portable avec deux manettes, prêt de 7 jours.',      NULL),                 -- 50009
  ('Nosferatu',                      'F. W. Murnau',         1922, 6, 'adulte', 3, 'Classique du cinéma muet d\'épouvante.',                     NULL),                 -- 50010
  ('Le voyage dans la Lune',         'Georges Méliès',       1902, 7, 'enfant', 4, 'Film restauré, l\'un des premiers films de science-fiction.', NULL),               -- 50011
  ('Musique traditionnelle du Québec','Ensemble Les Gigueux',2018, 8, 'adulte', 10,'Compilation de reels et chansons à répondre.',              NULL),                 -- 50012
  ('Enquête à Port-Cartier',         'Louise Bérubé',        2020, 1, 'adulte', 7, 'Polar se déroulant sur la Côte-Nord.',                       '978-2900000035'),     -- 50013
  ('Robots et intelligence',         'Collectif Sciences',   2024, 3, 'ado',    5, 'Introduction à la robotique et à l\'IA pour adolescents.', '978-2900000042');     -- 50014

-- Prêts : un en retard, un en cours, un retourné (historique), un annulé
INSERT INTO pret (code_membre, code_document, code_employe, date_pret, date_retour_prevue, date_retour_effective, statut, code_employe_cloture) VALUES
  (10001, 50001, 102, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 9 DAY), NULL, 'actif', NULL),     -- EN RETARD
  (10002, 50008, 102, DATE_SUB(CURDATE(), INTERVAL 3 DAY),  DATE_ADD(CURDATE(), INTERVAL 18 DAY), NULL, 'actif', NULL),    -- en cours
  (10003, 50002, 101, DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 39 DAY), DATE_SUB(CURDATE(), INTERVAL 41 DAY), 'retourne', 101),
  (10001, 50002, NULL, DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'retourne', 102),
  (10004, 50004, 102, DATE_SUB(CURDATE(), INTERVAL 5 DAY),  DATE_ADD(CURDATE(), INTERVAL 16 DAY), NULL, 'annule', 101);

-- Réservations : un document prêté ET réservé (message au retour), un document disponible réservé
INSERT INTO reservation (code_membre, code_document, code_employe, date_reservation, statut) VALUES
  (10003, 50001, NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), 'active'),   -- 50001 prêté à Julie, réservé par Awa
  (10002, 50005, 102,  DATE_SUB(NOW(), INTERVAL 1 DAY), 'active');   -- 50005 disponible mais réservé par Nicolas
