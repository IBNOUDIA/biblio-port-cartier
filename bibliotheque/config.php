<?php
/*
 * Configuration du site — à adapter à votre environnement (XAMPP, WAMP, Laragon, hébergeur).
 */
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'bibliotheque_port_cartier');
define('DB_USER', 'root');
define('DB_PASS', '');          // XAMPP : vide par défaut. MAMP : 'root'.

define('NOM_SITE', 'Bibliothèque municipale de Port-Cartier');
define('DUREE_PRET_JOURS', 21); // durée d'un prêt

date_default_timezone_set('America/Toronto');
