<?php
/**
 * TOOFLE - Contrôleur frontal (front controller).
 * Toutes les pages passent par ici : index.php?p=nom_de_page
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Pages autorisées (liste blanche = pas d'inclusion arbitraire de fichiers).
$routes = [
    'feed', 'login', 'register', 'logout',
    'profile', 'friends', 'search', 'notifications', 'settings',
    'post', 'messages', 'marketplace', 'listing', 'groups', 'group',
    'events', 'event', 'admin', 'error',
];

$page = $_GET['p'] ?? 'feed';
if (!in_array($page, $routes, true)) {
    error_response(404);
}

// Pages accessibles sans être connecté.
$public = ['login', 'register', 'logout', 'error'];

if (!in_array($page, $public, true) && !is_logged_in()) {
    $target = $_SERVER['REQUEST_URI'] ?? url('feed');
    $_SESSION['intended'] = $target;
    flash('info', 'Connectez-vous pour accéder à TOOFLE.');
    redirect(url('login'));
}

$file = __DIR__ . '/pages/' . $page . '.php';

if (!is_file($file)) {
    error_response(404);
}

require $file;
