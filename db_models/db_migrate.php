<?php
require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Cargar variables del .env
$dotenv = Dotenv::createImmutable(__DIR__ . "/../includes/");
$dotenv->safeLoad();

// Validar que las variables existan
$host = $_ENV['DB_HOST'] ?? null;
$db   = $_ENV['DB_NAME'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$pass = $_ENV['DB_PASS'] ?? null;

if (!$host || !$db || !$user) {
    die("❌ Error: Variables de entorno DB_HOST, DB_NAME o DB_USER no están definidas.\n");
}

// Rutas
$backupDir   = __DIR__ . '/migrations/';
$schemaFile  = __DIR__ . '/schema/schemaSQL.sql';
$updateFile  = __DIR__ . '/updates/update.sql';

try {
    // Conexión PDO
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Verificando consistencia de schema...\n";

    // Exportar schema actual desde la DB
    $currentSchema = shell_exec("mysqldump -h $host -u $user -p$pass $db --no-data");

    // Leer schema esperado desde archivo
    $expectedSchema = file_exists($schemaFile) ? file_get_contents($schemaFile) : '';

    // Comparar DB con schema
    if (trim($currentSchema) !== trim($expectedSchema)) {
        echo "⚠️ Diferencias detectadas: la DB y schema no coinciden. Se actualizará schemaSQL.sql al final.\n";
    } else {
        echo "✅ Schema actual coincide con schemaSQL.sql\n";
    }

    // Leer update.sql
    $updateSql = trim(file_get_contents($updateFile));
    if ($updateSql === '') {
        echo "✅ No hay cambios en update.sql\n";
        exit;
    }

    // Crear backup versionado ANTES de aplicar updates
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    $version = str_pad(count(glob($backupDir . 'modelV-*.sql')) + 1, 4, '0', STR_PAD_LEFT);
    copy($schemaFile, $backupDir . "modelV-$version.sql");
    echo "📦 Backup creado: modelV-$version.sql\n";

    // Ejecutar cambios de update.sql
    $pdo->exec($updateSql);
    echo "🚀 Cambios aplicados en DB\n";

    // Regenerar schemaSQL.sql desde la DB
    $schemaDump = shell_exec("mysqldump -h $host -u $user -p$pass $db --no-data");
    file_put_contents($schemaFile, $schemaDump);
    echo "📝 schemaSQL.sql actualizado\n";

    // Vaciar update.sql
    file_put_contents($updateFile, '');
    echo "✅ update.sql limpiado\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
