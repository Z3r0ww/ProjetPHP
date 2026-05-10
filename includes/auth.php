<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Note : BASE_URL est désormais géré exclusivement 
 * par config/database.php pour éviter les erreurs de redéfinition.
 */

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        header('Location: ' . BASE_URL . '/pages/dashboard.php?error=unauthorized');
        exit;
    }
}

function require_class_chosen() {
    require_login();
    if (empty($_SESSION['class'])) {
        header('Location: ' . BASE_URL . '/pages/choose_class.php');
        exit;
    }
}

function login_user($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['class'] = $user['class'];
    $_SESSION['role'] = $user['role'] ?? 'user';
}

function logout_user() {
    session_unset();
    session_destroy();
}