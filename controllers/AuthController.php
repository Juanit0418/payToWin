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
                    Usuario::setAlerta('error', 'Credenciales Incorrectas 1');
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
                        Usuario::setAlerta('error', 'Credenciales Incorrectas 2');
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

            if(empty($alertas)){
                //Verificar que el usuario no esté registrado
                $existeUsuario = Usuario::where('email', $usuario->email);
                if($existeUsuario){
                    Usuario::setAlerta("error", "El usuario ya está registrado");
                } else {
                    $usuario->crearToken();
                    $usuario->hashPassword();
                    $usuario->confirmado = 0;

                    //Enviar el email de confirmación
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviar_confirmacion();

                    //Crear el usuario
                    $resultado = $usuario->guardar();
                    if($resultado){
                        header('Location: /mensaje?tipo=confirmacion');
                    }
                }
            }
            $alertas = Usuario::getAlertas();
        };

        $router->vista("auth/registro", [
            "titulo" => "Registrate",
            "alertas" => $alertas,
            "usuario" => $usuario
        ]);
    }

    public static function olvide(Router $router){
        $alertas = [];

        if($_SERVER["REQUEST_METHOD"] === "POST"){
            $correo = s($_POST['email']);
            $correo = trim($correo);
            $correo = filter_var($correo, FILTER_VALIDATE_EMAIL);

            if(!$correo){
                Usuario::setAlerta('error', 'El email es obligatorio o no es válido');
            } else {
                $usuario = Usuario::where('email', $correo);

                if($usuario && $usuario->confirmado){
                    // Generar un token
                    $usuario->crearToken();
                    $usuario->guardar();
                    // Enviar el email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviar_instrucciones();

                    header('Location: /mensaje?tipo=reestablecer');
                    exit;
                } else {
                    Usuario::setAlerta('error', 'El usuario no existe o no está confirmado');
                }
            }
            $alertas = Usuario::getAlertas();
        }

        $router->vista("auth/olvide", [
            "titulo" => "Olvidé mi contraseña",
            "alertas" => $alertas
        ]);
    }

    public static function reestablecer(Router $router){
        $token = s($_GET['token']);
        $token_valido = true;

        if(!$token) header('Location: /');

        // Identificar el usuario con este token
        $usuario = Usuario::where('token', $token);

        if(empty($usuario)) {
            Usuario::setAlerta('error', 'Token No Válido, intenta de nuevo');
            $token_valido = false;
        }


        if($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Añadir el nuevo password
            $usuario->sincronizar($_POST);

            // Validar el password
            $alertas = $usuario->validar_password();

            if(empty($alertas)) {
                // Hashear el nuevo password
                $usuario->hashPassword();

                // Eliminar el Token
                $usuario->token = "";

                // Guardar el usuario en la BD
                $resultado = $usuario->guardar();

                // Redireccionar
                if($resultado) {
                    Usuario::setAlerta('exito', 'Contraseña Actualizada Correctamente');
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->vista("auth/reestablecer", [
            "titulo" => "Reestablecer mi contraseña",
            "alertas" => $alertas,
            "token_valido" => $token_valido
        ]);
    }

    public static function mensaje(Router $router){
        $tipo = s($_GET['tipo']);
        $tipo = trim($tipo);

        $router->vista("auth/mensaje", [
            "titulo" => "Revisa tu correo electrónico",
            "tipo" => $tipo
        ]);
    }

    public static function confirmar(Router $router){
        $token = s($_GET['token']);
        $token = trim($token);
        if(!$token){
            header('Location: /login');
            exit;
        }

        $usuario = Usuario::where('token', $token);
        if(!$usuario){
            Usuario::setAlerta('error', 'Token no válido');
        } else {
            // Confirmar la cuenta
            $usuario->confirmado = 1;
            $usuario->token = "";
            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta confirmada correctamente');
        }
        $alertas = Usuario::getAlertas();

        $router->vista("auth/confirmar", [
            "titulo" => "Confirmar cuenta",
            "alertas" => $alertas
        ]);
    }
}