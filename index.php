<?php
// On appelle d'abord la config (qui contient BASE_URL)
require_once __DIR__ . '/config/database.php';
// Ensuite l'auth
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/pages/login.php');
}
exit;