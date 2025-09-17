<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables del .env
$dotenv = Dotenv::createImmutable(__DIR__ . "/../includes/");
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? null;
$db   = $_ENV['DB_NAME'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$pass = $_ENV['DB_PASS'] ?? null;

if (!$host || !$db || !$user) {
    die("❌ Error: Variables DB_HOST, DB_NAME o DB_USER no definidas.\n");
}

$backupDir = __DIR__ . '/migrations/';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $target = $argv[1] ?? null;

    // Listar backups disponibles y ordenarlos por versión
    $backups = glob($backupDir . 'modelV-*.sql');
    natsort($backups);

    if (!$backups) die("❌ No hay backups disponibles en $backupDir\n");

    if (!$target) {
        // Tomar la última migración
        $targetFile = end($backups);
        echo "🔙 Revirtiendo a la última migración: " . basename($targetFile) . "\n";
        $filesToApply = [$targetFile];
    } else {
        $targetFile = $backupDir . $target;
        if (!file_exists($targetFile)) die("❌ Backup especificado no existe: $target\n");
        echo "🔙 Revirtiendo secuencialmente hasta: $target\n";

        // Seleccionar todas las migraciones >= target
        $filesToApply = [];
        foreach ($backups as $file) {
            $filesToApply[] = $file;
            if (basename($file) === $target) break;
        }
    }

    // Aplicar rollback secuencial
    foreach ($filesToApply as $file) {
        $sql = file_get_contents($file);
        if (!$sql) continue;

        // ⚠️ Aquí asumimos que los backups incluyen ALTER TABLE inversos (ideal)
        // Aplicar SQL de rollback incremental
        $pdo->exec($sql);
        echo "↩️ Migración revertida: " . basename($file) . "\n";
    }

    // Generar nueva migración como punto de partida
    $version = str_pad(count($backups) + 1, 4, '0', STR_PAD_LEFT);
    $schemaDump = shell_exec("mysqldump -h $host -u $user -p$pass $db --no-data");
    file_put_contents($backupDir . "modelV-$version.sql", $schemaDump);
    echo "📝 Nueva migración creada: modelV-$version.sql\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
