<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables del .env
$dotenv = Dotenv::createImmutable(__DIR__ . "/../includes/");
$dotenv->safeLoad();

// Datos de conexión
$host = $_ENV['DB_HOST'] ?? null;
$db   = $_ENV['DB_NAME'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$pass = $_ENV['DB_PASS'] ?? null;

if (!$host || !$db || !$user) {
    die("❌ Error: Variables DB_HOST, DB_NAME o DB_USER no definidas.\n");
}

// Rutas
$backupDir  = __DIR__ . '/migrations/';
$schemaFile = __DIR__ . '/schema/schemaSQL.sql';
$migDir     = __DIR__ . '/backmigration/';

// Función para pedir confirmación en terminal
function confirmRisk($message) {
    fwrite(STDOUT, $message . " ¿Desea continuar? (s/n): ");
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    return trim(strtolower($line)) === 's';
}

// ----------------------------
// Recibir argumento opcional
// ----------------------------
$targetMigration = $argv[1] ?? 'latest';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (!is_dir($migDir)) mkdir($migDir, 0755, true);

    // Obtener la lista de migraciones
    $migrations = glob($backupDir . 'modelV-*.sql');
    sort($migrations);

    if (empty($migrations)) {
        die("❌ No hay migraciones para revertir.\n");
    }

    // Determinar migraciones a revertir
    if ($targetMigration === 'latest') {
        $toRevert = [end($migrations)];
        $targetFile = end($migrations);
    } else {
        $found = false;
        foreach ($migrations as $migFile) {
            if (basename($migFile) === $targetMigration) {
                $targetFile = $migFile;
                $found = true;
                break;
            }
        }
        if (!$found) die("❌ Migración $targetMigration no encontrada.\n");
        $toRevert = [$targetFile]; // Solo revierte la migración especificada
    }

    $reversedSql = "";
    foreach ($toRevert as $migFile) {
        $migName = basename($migFile);
        echo "🔙 Analizando migración para reversión: $migName\n";

        $sql = file_get_contents($migFile);
        // Usa una expresión regular más robusta para dividir las sentencias
        $lines = preg_split('/;(?=[\r\n])/', $sql, -1, PREG_SPLIT_NO_EMPTY);
        $reverseSql = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line) continue;

            // Detectar CREATE TABLE → generar DROP TABLE
            if (preg_match('/^CREATE TABLE `([^`]*)`/i', $line, $m)) {
                $table = $m[1];
                $reverseSql[] = "DROP TABLE IF EXISTS `$table`;";
            }
            
            // Detectar ALTER TABLE ADD COLUMN → generar DROP COLUMN
            elseif (preg_match('/^ALTER TABLE `([^`]*)` ADD COLUMN `([^`]*)`/i', $line, $m)) {
                $table = $m[1];
                $col   = $m[2];
                $reverseSql[] = "ALTER TABLE `$table` DROP COLUMN `$col`;";
            }
            // Los otros casos de tu script original...
        }
        $reversedSql .= implode("\n", array_reverse($reverseSql)) . "\n";
    }

    // ---------------------------------
    // ⚠️ Advertencia y confirmación
    // ---------------------------------
    echo "\n⚠️ Se detectaron cambios destructivos para el rollback:\n";
    echo $reversedSql . "\n";
    if (!confirmRisk("Desea aplicar estos cambios de reversión? Esta acción podría causar una pérdida de datos irreversible.")) {
        die("❌ Reversión cancelada por el usuario.\n");
    }

    // ---------------------------------
    // 🚀 Aplicar el rollback
    // ---------------------------------
    // Iniciar transacción de la base de datos
    $pdo->beginTransaction();
    try {
        $pdo->exec($reversedSql);
        $pdo->commit();
        echo "\n✅ Rollback aplicado exitosamente.\n";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "❌ Error al aplicar la reversión. Se ha realizado un rollback: " . $e->getMessage() . "\n";
        exit(1);
    }

    // ---------------------------------
    // 📝 Sincronizar schemaSQL.sql
    // ---------------------------------
    // Opcional: Si revirtió, actualiza el schemaSQL.sql al estado anterior
    $previousMigrationIndex = array_search($targetFile, $migrations) - 1;
    if ($previousMigrationIndex >= 0) {
        $previousSchemaFile = $migrations[$previousMigrationIndex];
        copy($previousSchemaFile, $schemaFile);
        echo "📝 El archivo schemaSQL.sql ha sido actualizado a la versión anterior.\n";
    } else {
        // En caso de revertir la primera migración, se asume que el esquema debe estar vacío.
        file_put_contents($schemaFile, "");
        echo "📝 El archivo schemaSQL.sql ha sido vaciado (revertida la primera migración).\n";
    }

} catch (PDOException $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
    exit(1);
}