<?php
require_once __DIR__ . '/auth.php';

function requireProfessor() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    if (!$auth->isProfessor()) {
        header('Location: ../index.php');
        exit;
    }
    return $auth;
}

function requireStudent() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    if (!$auth->isStudent()) {
        header('Location: ../index.php');
        exit;
    }
    return $auth;
}

function requireLogin() {
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
    return $auth;
}
?>
