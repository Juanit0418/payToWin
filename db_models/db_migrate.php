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
    $handle = fopen ("php://stdin","r");
    $line = fgets($handle);
    return trim(strtolower($line)) === 's';
}

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

    // ----------------------------
    // Detectar cambios destructivos
    // ----------------------------
    $riskyChanges = [];

    // Tablas a eliminar
    foreach ($existingTables as $table) {
        if (!in_array($table, $schemaTables)) {
            $riskyChanges[] = "Tabla '$table' existe en la DB pero no en schema. Se eliminaría y perderías todos los registros.";
        }
    }

    // Columnas a eliminar o cambios de tipo
    foreach ($schemaTables as $table) {
        $stmtCols = $pdo->query("SHOW COLUMNS FROM `$table`");
        $dbColumns = array_column($stmtCols->fetchAll(PDO::FETCH_ASSOC), null, 'Field');

        preg_match('/CREATE TABLE `'.$table.'`(.*?)\)\s*ENGINE=/is', $expectedSchema, $tableSql);
        if (isset($tableSql[1])) {
            $tableDef = trim($tableSql[1]);
            preg_match_all('/^`([^`]*)`\s+(.*)$/m', $tableDef, $matches, PREG_SET_ORDER);
            $schemaCols = array_column($matches, 2, 1);

            foreach ($dbColumns as $colName => $colInfo) {
                if (!isset($schemaCols[$colName])) {
                    $riskyChanges[] = "Columna '$colName' en tabla '$table' existe en la DB pero no en schema. Se eliminarían sus datos.";
                } else {
                    $schemaType = strtolower($schemaCols[$colName]);
                    $dbType     = strtolower($colInfo['Type']);
                    if ($schemaType !== $dbType) {
                        $riskyChanges[] = "Columna '$colName' en tabla '$table' cambiará de tipo '$dbType' a '$schemaType'. Posible pérdida de datos.";
                    }
                }
            }
        }
    }

    // Si hay riesgos, pedir confirmación
    if (!empty($riskyChanges)) {
        echo "⚠️ Riesgos detectados antes de aplicar cambios:\n";
        foreach ($riskyChanges as $r) {
            echo "  - $r\n";
        }
        if (!confirmRisk("Hay cambios que podrían afectar datos existentes")) {
            die("❌ Migración cancelada por el usuario.\n");
        }
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // ----------------------------
        // 1️⃣ Crear tablas faltantes
        // ----------------------------
        foreach ($schemaTables as $table) {
            if (!in_array($table, $existingTables)) {
                echo "🆕 Tabla faltante: $table. Creando...\n";
                preg_match('/CREATE TABLE `'.$table.'`(.*?)ENGINE=/is', $expectedSchema, $tableSql);
                if (isset($tableSql[0])) {
                    $createSql = "CREATE TABLE `$table`" . $tableSql[1] . " ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
                    $pdo->exec($createSql);
                    echo "✅ Tabla $table creada.\n";
                }
            }
        }

        // ----------------------------
        // 2️⃣ Crear columnas faltantes
        // ----------------------------
        foreach ($schemaTables as $table) {
            preg_match('/CREATE TABLE `'.$table.'`(.*?)\)\s*ENGINE=/is', $expectedSchema, $tableSql);
            if (isset($tableSql[1])) {
                $tableDef = trim($tableSql[1]);
                $lines = explode("\n", $tableDef);

                foreach ($lines as $line) {
                    $line = trim($line, " ,\r\n");
                    if (preg_match('/^`([^`]*)`\s+(.*)$/', $line, $colMatch)) {
                        $colName = $colMatch[1];
                        $colDef  = $colMatch[2];

                        $stmtCols = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$colName'");
                        if ($stmtCols->rowCount() === 0) {
                            echo "🆕 Columna faltante en $table: $colName. Agregando...\n";
                            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$colName` $colDef");
                        }
                    }
                }
            }
        }

        // ----------------------------
        // 3️⃣ Crear índices y foreign keys
        // ----------------------------
        foreach ($schemaTables as $table) {
            preg_match('/CREATE TABLE `'.$table.'`(.*?)\)\s*ENGINE=/is', $expectedSchema, $tableSql);
            if (isset($tableSql[1])) {
                $tableDef = trim($tableSql[1]);

                // Índices
                preg_match_all('/(UNIQUE KEY .*?\)|KEY .*?\))/is', $tableDef, $indexMatches);
                foreach ($indexMatches[0] as $indexSql) {
                    try { $pdo->exec("ALTER TABLE `$table` ADD $indexSql"); } catch (\PDOException $e) {}
                }

                // Foreign keys
                preg_match_all('/CONSTRAINT .*?FOREIGN KEY .*?\)/is', $tableDef, $fkMatches);
                foreach ($fkMatches[0] as $fkSql) {
                    try { $pdo->exec("ALTER TABLE `$table` ADD $fkSql"); } catch (\PDOException $e) {}
                }
            }
        }
        
        $pdo->commit();
        echo "✅ DB sincronizada con schemaSQL.sql\n";

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "❌ Error en la migración. Rollback ejecutado: " . $e->getMessage() . "\n";
        exit;
    }

    // ----------------------------
    // 4️⃣ Aplicar update.sql
    // ----------------------------
    $updateSql = trim(file_get_contents($updateFile));
    if ($updateSql !== '') {
        if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
        $version = str_pad(count(glob($backupDir . 'modelV-*.sql')) + 1, 4, '0', STR_PAD_LEFT);
        copy($schemaFile, $backupDir . "modelV-$version.sql");
        echo "📦 Backup creado: modelV-$version.sql\n";

        // Iniciar transacción para el update.sql
        $pdo->beginTransaction();
        try {
            $pdo->exec($updateSql);
            $pdo->commit();
            echo "🚀 Cambios aplicados en DB\n";
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "❌ Error al aplicar update.sql. Rollback ejecutado: " . $e->getMessage() . "\n";
            exit;
        }

        file_put_contents($updateFile, '');
        echo "✅ update.sql limpiado\n";
    } else {
        echo "✅ No hay cambios en update.sql\n";
    }

    // ----------------------------
    // 5️⃣ Regenerar schemaSQL.sql desde la DB (Saneado)
    // ----------------------------
    // Saneamiento de variables para shell_exec
    $escapedHost = escapeshellarg($host);
    $escapedUser = escapeshellarg($user);
    $escapedPass = escapeshellarg($pass);
    $escapedDb   = escapeshellarg($db);
    
    // Nota: El uso de -p sin espacio y con la variable escapada es una práctica común para mysqldump
    $command = "mysqldump -h $escapedHost -u $escapedUser -p$escapedPass $escapedDb --no-data";
    $schemaDump = shell_exec($command);
    
    file_put_contents($schemaFile, $schemaDump);
    echo "📝 schemaSQL.sql actualizado\n";

} catch (PDOException $e) {
    echo "❌ Error fatal: " . $e->getMessage() . "\n";
}