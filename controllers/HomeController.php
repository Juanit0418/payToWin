<?php

namespace Controllers;

use MVC\Router;

class HomeController {
    public static function Home(Router $router) {
        // Iniciar la sesión si no está iniciada para verificar el usuario
        session_start();
        
        // Verifica si el usuario está autenticado
        if(!isset($_SESSION['id'])) {
            // Si no está autenticado, lo redirige al login
            header('Location: /login');
            exit;
        }

        // Si está autenticado, renderiza la vista 'home/index'
        $router->vista('home/home', [
            'titulo' => 'Página Principal',
            'nombre' => $_SESSION['nombre'] ?? '' // Pasa el nombre para mostrarlo en la vista
        ]);
    }
}