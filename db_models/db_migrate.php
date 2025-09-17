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
$updateFile = __DIR__ . '/updates/update.sql';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Verificando consistencia de schema...\n";

    // Leer schema esperado
    if (!file_exists($schemaFile)) die("❌ schemaSQL.sql no encontrado\n");
    $expectedSchema = file_get_contents($schemaFile);

    // Obtener tablas actuales en DB
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Extraer nombres de tablas del schema
    preg_match_all('/CREATE TABLE `([^`]*)`/i', $expectedSchema, $matches);
    $schemaTables = $matches[1];

    // Crear tablas faltantes
    foreach ($schemaTables as $table) {
        if (!in_array($table, $existingTables)) {
            echo "🆕 Tabla faltante: $table. Creando...\n";
            preg_match('/CREATE TABLE `'.$table.'`(.*?);/is', $expectedSchema, $tableSql);
            if (isset($tableSql[0])) {
                $pdo->exec($tableSql[0]);
                echo "✅ Tabla $table creada.\n";
            }
        }
    }

    // Eliminar tablas que sobran (opcional, si quieres forzar sincronización)
    foreach ($existingTables as $table) {
        if (!in_array($table, $schemaTables)) {
            echo "🗑️ Tabla extra: $table. Eliminando...\n";
            $pdo->exec("DROP TABLE `$table`");
            echo "✅ Tabla $table eliminada.\n";
        }
    }

    // Crear tablas faltantes
foreach ($schemaTables as $table) {
    if (!in_array($table, $existingTables)) {
        echo "🆕 Tabla faltante: $table. Creando...\n";

        // Captura toda la definición hasta ENGINE=
        preg_match('/CREATE TABLE `'.$table.'`(.*?)ENGINE=/is', $expectedSchema, $tableSql);
        if (isset($tableSql[0])) {
            $createSql = "CREATE TABLE `$table`" . $tableSql[1] . " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            $pdo->exec($createSql);
            echo "✅ Tabla $table creada.\n";
        }
    }
}


     // Extraer la definición completa de la tabla del schema
preg_match('/CREATE TABLE `'.$table.'`(.*?)\)\s*ENGINE=/is', $expectedSchema, $tableSql);
if (isset($tableSql[1])) {
    $tableDef = trim($tableSql[1]);

    // Separar líneas de columnas (ignorar índices al inicio)
    $lines = explode("\n", $tableDef);
    foreach ($lines as $line) {
        $line = trim($line, " ,\r\n");
        if (preg_match('/^`([^`]*)`\s+(.*)$/', $line, $colMatch)) {
            $colName = $colMatch[1];
            $colDef  = $colMatch[2];

            // Si la columna no existe → ADD
            $stmtCols = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$colName'");
            if ($stmtCols->rowCount() === 0) {
                echo "🆕 Columna faltante en $table: $colName. Agregando...\n";
                $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$colName` $colDef");
            }
        }
    }
}


    echo "✅ DB sincronizada con schemaSQL.sql\n";

    // Leer update.sql
    $updateSql = trim(file_get_contents($updateFile));
    if ($updateSql !== '') {
        // Crear backup versionado antes de aplicar updates
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $version = str_pad(count(glob($backupDir . 'modelV-*.sql')) + 1, 4, '0', STR_PAD_LEFT);
        copy($schemaFile, $backupDir . "modelV-$version.sql");
        echo "📦 Backup creado: modelV-$version.sql\n";

        // Aplicar cambios del update
        $pdo->exec($updateSql);
        echo "🚀 Cambios aplicados en DB\n";

        // Vaciar update.sql
        file_put_contents($updateFile, '');
        echo "✅ update.sql limpiado\n";
    } else {
        echo "✅ No hay cambios en update.sql\n";
    }

    // Regenerar schemaSQL.sql desde la DB
    $schemaDump = shell_exec("mysqldump -h $host -u $user -p$pass $db --no-data");
    file_put_contents($schemaFile, $schemaDump);
    echo "📝 schemaSQL.sql actualizado\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
