<?php

namespace MVC;

class Router
{
    public array $getRoutes = [];
    public array $postRoutes = [];

    public function get($url, $fn)
    {
        $this->getRoutes[rtrim($url, '/')] = $fn;
    }

    public function post($url, $fn)
    {
        $this->postRoutes[rtrim($url, '/')] = $fn;
    }

    public function comprobarRutas()
    {
        // Obtener la URL
        $url_actual = '/';

        if (!empty($_SERVER['PATH_INFO'])) {
            // PHP embebido
            $url_actual = $_SERVER['PATH_INFO'];
        } else {
            // Apache/Nginx
            $url_actual = $_SERVER['REQUEST_URI'];
            $url_actual = parse_url($url_actual, PHP_URL_PATH);

            // Si tu proyecto está en subcarpeta, p. ej: '/miApp'
            $base_path = ''; // Cambiar si aplica
            if ($base_path && str_starts_with($url_actual, $base_path)) {
                $url_actual = substr($url_actual, strlen($base_path));
            }
        }

        // Normalizar: quitar barra final
        $url_actual = rtrim($url_actual, '/');
        if ($url_actual === '') $url_actual = '/';

        $method = $_SERVER['REQUEST_METHOD'];
        $fn = ($method === 'GET') 
            ? ($this->getRoutes[$url_actual] ?? null) 
            : ($this->postRoutes[$url_actual] ?? null);

        if ($fn) {
            call_user_func($fn, $this);
        } else {
            http_response_code(404);
            echo "Página No Encontrada o Ruta no válida: $url_actual";
        }
    }

    public function vista($view, $datos = [])
    {
        foreach ($datos as $key => $value) {
            $$key = $value; 
        }

        ob_start();
        include_once __DIR__ . "/views/$view.php";
        $contenido = ob_get_clean();
        include_once __DIR__ . '/views/layout.php';
    }
}
