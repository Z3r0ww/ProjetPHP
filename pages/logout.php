<?php
// On charge d'abord la base de données qui contient la définition de BASE_URL
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Sécurité : on s'assure que la session est bien démarrée avant de la détruire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On vide et détruit la session
$_SESSION = array();
session_destroy();

// Redirection vers l'accueil (index.php à la racine)
header("Location: " . BASE_URL . "/index.php");
exit;