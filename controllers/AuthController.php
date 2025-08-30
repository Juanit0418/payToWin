<?php

namespace Controllers;

use Classes\Email;
use Model\Usuario;
use MVC\Router;

class AuthController {
    public static function login(Router $router) {

        $alertas = [];
        $usuario = new Usuario();

        if($_SERVER['REQUEST_METHOD'] === 'POST') {
    
            $usuario = new Usuario($_POST);

            $alertas = $usuario->validar_login();
            
            if(empty($alertas)) {
                // Verificar quel el usuario exista
                $usuario = Usuario::where('email', $usuario->email);
                if(!$usuario || !$usuario->confirmado ) {
                    Usuario::setAlerta('error', 'El Usuario No Existe o no esta confirmado');
                } else {
                    // El Usuario existe
                    if( password_verify($_POST['password'], $usuario->password) ) {
                        
                        // Iniciar la sesión
                        session_start();    
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['apellido'] = $usuario->apellido;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['rol'] = $usuario->rol ?? null;
                        
                    } else {
                        Usuario::setAlerta('error', 'Password Incorrecto');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();
        
        // Render a la vista 
        $router->vista('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas,
            "usuario" => $usuario
        ]);
    }

    public static function logout() {
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            session_start();
            $_SESSION = [];
            header('Location: /login');
        }
    
    }

    public static function registro(Router $router){
        $usuario = new Usuario();
        $alertas = [];

        if($_SERVER["REQUEST_METHOD"] === "POST"){
            $post = [];
            foreach ($_POST as $key => $value) {
                $post[$key] = s($value);
            }

            $usuario = new Usuario($post);
            $alertas = $usuario->validar_cuenta();
        };

        $router->vista("auth/registro", [
            "titulo" => "Registrate",
            "alertas" => $alertas,
            "usuario" => $usuario
        ]);
    }
}