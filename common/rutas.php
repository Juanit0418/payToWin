<?php
/*
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}*/

function rutasPublicas() {
    return [
        'HOME' => '/',
        'LOGIN' => '/common/session.php',
        'MODAL' => '/components/modal.php',
        'CONTACT' => '/contact.php',
    ];
}

function rutasUsuario() {
    return [
        'DASHBOARD' => '/user/dashboard.php',
        'PROFILE' => '/user/profile.php',
        'LOGOUT' => '/common/logout.php',
    ];
}

function rutasModerador() {
    return [
        'MOD_PANEL' => '/moderator/panel.php',
        'MOD_REPORTS' => '/moderator/reports.php',
    ];
}

function rutasAdmin() {
    return [
        'ADMIN_PANEL' => '/admin/panel.php',
        'ADMIN_USERS' => '/admin/users.php',
    ];
}

function obtenerRutas() {
    $rutas = rutasPublicas();

    // Leer rol desde cookie
    $rol = strtolower($_COOKIE['role'] ?? '');

    if (in_array($rol, ['user', 'moderator', 'admin'])) {
        $rutas = array_merge($rutas, rutasUsuario());
    }

    if (in_array($rol, ['moderator', 'admin'])) {
        $rutas = array_merge($rutas, rutasModerador());
    }

    if ($rol === 'admin') {
        $rutas = array_merge($rutas, rutasAdmin());
    }

    return $rutas;
}

