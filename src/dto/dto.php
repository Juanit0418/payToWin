<?php

/**
 * 📌 Opciones disponibles para las reglas de validación
 * 
 *  tipo       => ['string', 'int', 'float', 'bool']  
 *      Valida que el campo coincida con el tipo de dato.
 * 
 *  requerido  => [true, false]  
 *      Define si el campo es obligatorio o no.
 * 
 *  min        => [int]  
 *      Para números: valor mínimo.  
 *      Para strings: cantidad mínima de caracteres.
 * 
 *  max        => [int]  
 *      Para números: valor máximo.  
 *      Para strings: cantidad máxima de caracteres.
 * 
 *  file       => [array]  
 *      Reglas especiales para validar archivos (cuando el campo proviene de $_FILES):  
 *         - maxSize => [int] tamaño máximo en bytes  
 *         - ext     => [array<string>] extensiones permitidas  
 *         - mime    => [array<string>] tipos MIME permitidos
 * 
 *  custom     => [callable]  
 *      Función personalizada para validación específica.  
 *      Debe devolver `null` si es válido, o un string con el error en caso contrario.
 * 
 * 🚀 Ejemplo rápido:
 * 'nombre'  => [ 'requerido' => true, 'tipo' => 'string', 'min' => 3, 'max' => 50 ]
 * 'edad'    => [ 'tipo' => 'int', 'min' => 18, 'max' => 99 ]
 * 'archivo' => [ 'file' => ['maxSize' => 5242880, 'ext' => ['pdf','jpg']] ]
 *
 * 
 * 📌 Ejemplo de implementación:
 * $errores = validarDTO($_REQUEST, UserDTO::schema());
 * $errores = validarDTO($_FILES, UploadDTO::schema()); // si es archivo
 * if (!empty($errores)) { print_r($errores); }
 */

// ------------------------
// Registry de validadores
// ------------------------
class ValidatorRegistry {
    private static array $rules = [];

    public static function register(string $name, callable $callback): void {
        self::$rules[$name] = $callback;
    }

    public static function get(string $name): ?callable {
        return self::$rules[$name] ?? null;
    }
}

// ------------------------
// Reglas de validación base
// ------------------------
ValidatorRegistry::register('requerido', function($campo, $valor, $parametro) {
    if ($parametro && (is_null($valor) || $valor === '')) {
        return "El campo '{$campo}' es requerido.";
    }
    return null;
});

ValidatorRegistry::register('tipo', function($campo, $valor, $parametro) {
    if (is_null($valor)) return null;
    switch ($parametro) {
        case 'int': if (!filter_var($valor, FILTER_VALIDATE_INT)) return "El campo '{$campo}' debe ser entero."; break;
        case 'float': if (!filter_var($valor, FILTER_VALIDATE_FLOAT)) return "El campo '{$campo}' debe ser decimal."; break;
        case 'string': if (!is_string($valor)) return "El campo '{$campo}' debe ser string."; break;
        case 'bool': if (!is_bool(filter_var($valor, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE))) return "El campo '{$campo}' debe ser booleano."; break;
    }
    return null;
});

ValidatorRegistry::register('min', function($campo, $valor, $parametro) {
    if (is_null($valor)) return null;
    if (is_numeric($valor) && $valor < $parametro) {
        return "El campo '{$campo}' debe ser >= {$parametro}.";
    }
    if (is_string($valor) && strlen($valor) < $parametro) {
        return "El campo '{$campo}' debe tener al menos {$parametro} caracteres.";
    }
    return null;
});

ValidatorRegistry::register('max', function($campo, $valor, $parametro) {
    if (is_null($valor)) return null;
    if (is_numeric($valor) && $valor > $parametro) {
        return "El campo '{$campo}' debe ser <= {$parametro}.";
    }
    if (is_string($valor) && strlen($valor) > $parametro) {
        return "El campo '{$campo}' no debe superar {$parametro} caracteres.";
    }
    return null;
});

// ------------------------
// Nueva regla: file
// ------------------------
ValidatorRegistry::register('file', function($campo, $valor, $parametro) {
    if (!is_array($valor) || !isset($valor['name'], $valor['size'], $valor['tmp_name'])) {
        return "El campo '{$campo}' no contiene un archivo válido.";
    }

    if ($valor['error'] !== UPLOAD_ERR_OK) {
        return "Hubo un error al subir el archivo '{$campo}'.";
    }

    if (isset($parametro['maxSize']) && $valor['size'] > $parametro['maxSize']) {
        $maxMB = round($parametro['maxSize'] / (1024 * 1024), 2);
        return "El archivo '{$campo}' no puede superar los {$maxMB} MB.";
    }

    if (isset($parametro['ext'])) {
        $ext = strtolower(pathinfo($valor['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $parametro['ext'])) {
            $permitidas = implode(', ', $parametro['ext']);
            return "El archivo '{$campo}' solo permite extensiones: {$permitidas}.";
        }
    }

    if (isset($parametro['mime'])) {
        $mime = mime_content_type($valor['tmp_name']);
        if (!in_array($mime, $parametro['mime'])) {
            $permitidos = implode(', ', $parametro['mime']);
            return "El archivo '{$campo}' debe ser de tipo MIME: {$permitidos}.";
        }
    }

    return null;
});

// ------------------------
// Validador principal
// ------------------------
function validarDTO(array $data, array $schema): array {
    $errores = [];

    foreach ($schema as $campo => $reglas) {
        $valor = $data[$campo] ?? null;

        foreach ($reglas as $regla => $parametro) {
            $callback = ValidatorRegistry::get($regla);

            if ($callback) {
                $error = $callback($campo, $valor, $parametro);
                if ($error) {
                    $errores[$campo][] = $error;
                }
            }
        }
    }

    return $errores;
}
