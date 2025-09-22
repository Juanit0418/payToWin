<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/../includes/");
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? null;
$db   = $_ENV['DB_NAME'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$pass = $_ENV['DB_PASS'] ?? null;

if (!$host || !$db || !$user) {
    die("❌ Variables DB_HOST, DB_NAME o DB_USER no definidas.\n");
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Buscando índices duplicados...\n";

    // Obtener todas las tablas
    $stmtTables = $pdo->query("SHOW TABLES");
    $tables = $stmtTables->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        // Obtener todos los índices de la tabla
        $stmt = $pdo->query("SHOW INDEX FROM `$table`");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $uniqueIndexes = [];
        foreach ($indexes as $idx) {
            $key = strtolower($idx['Key_name']);
            $columns = $idx['Column_name'];

            // Solo considerar índices no primarios
            if ($key === 'primary') continue;

            // Crear un hash único por combinación de columnas
            $hash = $columns; // Podés cambiar a implode(',', ...) si hay multi-column index
            if (isset($uniqueIndexes[$hash])) {
                // Índice duplicado encontrado, eliminarlo
                $dupIndex = $idx['Key_name'];
                echo "🗑 Eliminando índice duplicado '$dupIndex' en tabla '$table'\n";
                $pdo->exec("ALTER TABLE `$table` DROP INDEX `$dupIndex`");
            } else {
                $uniqueIndexes[$hash] = $idx['Key_name'];
            }
        }
    }

    echo "✅ Proceso de limpieza de índices duplicados finalizado.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
