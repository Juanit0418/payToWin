<?php 

require_once __DIR__ . '/../includes/app.php';

use MVC\Router;
use Controllers\AuthController;
use Controllers\HomeController;

$router = new Router();
$router->get('/', [HomeController::class, 'Home']);
// Login
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Crear Cuenta
$router->get('/registro', [AuthController::class, 'registro']);
$router->post('/registro', [AuthController::class, 'registro']);
// Olvide Password
$router->get('/olvide', [AuthController::class, 'olvide']);
$router->post('/olvide', [AuthController::class, 'olvide']);
// Reestablecer Password
$router->get('/reestablecer', [AuthController::class, 'reestablecer']);
$router->post('/reestablecer', [AuthController::class, 'reestablecer']);
// Mensaje
$router->get('/mensaje', [AuthController::class, 'mensaje']);
// Confirmar Cuenta
$router->get('/confirmar', [AuthController::class, 'confirmar']);


$router->comprobarRutas();