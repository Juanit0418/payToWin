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

// Función para pedir confirmación en terminal
function confirmRisk($message) {
    fwrite(STDOUT, $message . " ¿Desea continuar? (s/n): ");
    $line = fgets(STDIN);
    return trim(strtolower($line)) === 's';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "🔍 Verificando consistencia de schema...\n";

    // ---------------------------------
    // 📂 Verificar si schemaSQL.sql está vacío
    // ---------------------------------
    if (!file_exists($schemaFile) || trim(file_get_contents($schemaFile)) === '') {
        echo "⚠️ El archivo schemaSQL.sql está vacío.\n";
        if (confirmRisk("¿Desea exportar el esquema actual de la base de datos a schemaSQL.sql?")) {
            $dumpCommand = sprintf(
                'mysqldump -h%s -u%s -p%s --no-data %s > %s',
                escapeshellarg($host),
                escapeshellarg($user),
                $pass ? escapeshellarg($pass) : '',
                escapeshellarg($db),
                escapeshellarg($schemaFile)
            );
            system($dumpCommand, $retval);
            if ($retval === 0) {
                echo "✅ Esquema exportado correctamente a $schemaFile\n";
            } else {
                die("❌ Error al exportar el esquema. Código: $retval\n");
            }
        } else {
            die("⏩ Exportación de esquema cancelada.\n");
        }
    }

    // Leer schema esperado
    $expectedSchema = file_get_contents($schemaFile);

    // Obtener tablas actuales en DB
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Extraer nombres de tablas del schema
    preg_match_all('/CREATE TABLE `([^`]*)`/i', $expectedSchema, $matches);
    $schemaTables = $matches[1];

    // ----------------------------
    // Detectar cambios destructivos
    // ----------------------------
    $riskyChanges = [];

    foreach ($existingTables as $table) {
        if (!in_array($table, $schemaTables)) {
            $riskyChanges[] = "Tabla '$table' existe en la DB pero no en schema. Se eliminaría y perderías todos los registros.";
        }
    }

    foreach ($schemaTables as $table) {
        if (!in_array($table, $existingTables)) continue;

        $stmtCols = $pdo->query("SHOW COLUMNS FROM `$table`");
        $dbColumns = array_column($stmtCols->fetchAll(PDO::FETCH_ASSOC), null, 'Field');

        preg_match('/CREATE TABLE `'.$table.'`(.*?)\)\s*ENGINE=/is', $expectedSchema, $tableSql);
        if (!isset($tableSql[1])) continue;

        $tableDef = trim($tableSql[1]);
        preg_match_all('/`([^`]*)`\s+([^,]+)/', $tableDef, $colMatches, PREG_SET_ORDER);
        $schemaCols = [];
        foreach ($colMatches as $m) $schemaCols[$m[1]] = strtolower(trim($m[2]));

        foreach ($dbColumns as $colName => $colInfo) {
            if (!isset($schemaCols[$colName])) {
                $riskyChanges[] = "Columna '$colName' en tabla '$table' existe en DB pero no en schema.";
            } else {
                $dbType     = strtolower($colInfo['Type']);
                $schemaType = strtolower($schemaCols[$colName]);
                if (strpos($schemaType, $dbType) === false) {
                    $riskyChanges[] = "Columna '$colName' en tabla '$table' difiere: DB='$dbType', schema='$schemaType'";
                }
            }
        }
    }

    if (!empty($riskyChanges)) {
        echo "⚠️ Riesgos detectados antes de aplicar cambios:\n";
        foreach ($riskyChanges as $r) echo "  - $r\n";
        if (!confirmRisk("¿Desea continuar con la migración a pesar de los riesgos?")) {
            die("❌ Migración cancelada por el usuario.\n");
        }
    }

    // ----------------------------
    // 1️⃣ Aplicar update.sql
    // ----------------------------
    $updateSql = trim(file_get_contents($updateFile));
    if ($updateSql !== '') {
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $version = str_pad(count(glob($backupDir . 'modelV-*.sql')) + 1, 4, '0', STR_PAD_LEFT);
        copy($schemaFile, $backupDir . "modelV-$version.sql");
        echo "📦 Backup creado: modelV-$version.sql\n";

        $pdo->beginTransaction();
        try {
            $pdo->exec($updateSql);
            $pdo->commit();
            echo "🚀 Cambios aplicados en DB\n";
        } catch (PDOException $e) {
            $pdo->rollBack();
            die("❌ Error al aplicar update.sql. Rollback ejecutado: " . $e->getMessage() . "\n");
        }

        file_put_contents($updateFile, '');
        echo "✅ update.sql limpiado\n";
    } else {
        echo "✅ No hay cambios en update.sql\n";
    }

    // ----------------------------
    // 2️⃣ Aplicar índices y claves foráneas solo si no existen
    // ----------------------------
    foreach ($schemaTables as $table) {
        preg_match('/CREATE TABLE `'.$table.'`(.*?)\)\s*ENGINE=/is', $expectedSchema, $tableSql);
        if (!isset($tableSql[1])) continue;

        $tableDef = trim($tableSql[1]);

        // ----------------
        // Índices
        // ----------------
        preg_match_all('/(UNIQUE KEY `([^`]*)` .*?|KEY `([^`]*)` .*?)/is', $tableDef, $indexMatches, PREG_SET_ORDER);
        foreach ($indexMatches as $idx) {
            $indexName = $idx[2] ?: $idx[3];
            $stmtCheck = $pdo->query("SHOW INDEX FROM `$table` WHERE Key_name = '$indexName'");
            if ($stmtCheck->rowCount() === 0) {
                try { $pdo->exec("ALTER TABLE `$table` ADD {$idx[1]}"); } catch (\PDOException $e) { echo "⚠️ No se pudo agregar índice $indexName: ".$e->getMessage()."\n"; }
            }
        }

        // ----------------
        // Foreign Keys
        // ----------------
        preg_match_all('/CONSTRAINT `([^`]*)` FOREIGN KEY .*?\)/is', $tableDef, $fkMatches, PREG_SET_ORDER);
        foreach ($fkMatches as $fk) {
            $fkName = $fk[1];
            $stmtCheck = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND CONSTRAINT_NAME='$fkName'");
            if ($stmtCheck->rowCount() === 0) {
                try { $pdo->exec("ALTER TABLE `$table` ADD {$fk[0]}"); } catch (\PDOException $e) { echo "⚠️ No se pudo agregar FK $fkName: ".$e->getMessage()."\n"; }
            }
        }
    }

    // ----------------------------
    // 3️⃣ Regenerar schemaSQL.sql desde la DB
    // ----------------------------
    $command = sprintf(
        'mysqldump -h%s -u%s -p%s --no-data %s',
        escapeshellarg($host),
        escapeshellarg($user),
        $pass ? escapeshellarg($pass) : '',
        escapeshellarg($db)
    );
    $schemaDump = shell_exec($command);
    file_put_contents($schemaFile, $schemaDump);
    echo "📝 schemaSQL.sql actualizado\n";

} catch (PDOException $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
}
