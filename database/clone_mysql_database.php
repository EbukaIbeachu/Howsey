<?php

$withData = in_array('--with-data', $argv, true);
$arguments = array_values(array_filter(
    $argv,
    static fn (string $argument): bool => $argument !== '--with-data'
));

if (count($arguments) < 6) {
    fwrite(STDERR, "Usage: php database/clone_mysql_database.php <host> <port> <username> [password] <source_db> <target_db> [--with-data]\n");
    exit(1);
}

if (count($arguments) === 6) {
    [$script, $host, $port, $username, $sourceDatabase, $targetDatabase] = $arguments;
    $password = '';
} else {
    [$script, $host, $port, $username, $password, $sourceDatabase, $targetDatabase] = array_pad($arguments, 7, null);
}

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$targetDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $tables = $pdo->query("
        SELECT TABLE_NAME
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = ".$pdo->quote($sourceDatabase)."
          AND TABLE_TYPE = 'BASE TABLE'
        ORDER BY TABLE_NAME
    ")->fetchAll(PDO::FETCH_COLUMN);

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');

    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `{$targetDatabase}`.`{$table}`");
        $pdo->exec("CREATE TABLE `{$targetDatabase}`.`{$table}` LIKE `{$sourceDatabase}`.`{$table}`");

        if ($withData) {
            $pdo->exec("INSERT INTO `{$targetDatabase}`.`{$table}` SELECT * FROM `{$sourceDatabase}`.`{$table}`");
        }
    }

    $views = $pdo->query("
        SELECT TABLE_NAME, VIEW_DEFINITION
        FROM information_schema.VIEWS
        WHERE TABLE_SCHEMA = ".$pdo->quote($sourceDatabase)."
    ")->fetchAll();

    foreach ($views as $view) {
        $pdo->exec("DROP VIEW IF EXISTS `{$targetDatabase}`.`{$view['TABLE_NAME']}`");
        $pdo->exec("CREATE VIEW `{$targetDatabase}`.`{$view['TABLE_NAME']}` AS {$view['VIEW_DEFINITION']}");
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');

    fwrite(STDOUT, "Database clone completed: {$sourceDatabase} -> {$targetDatabase}".($withData ? " (with data)\n" : " (schema only)\n"));
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage().PHP_EOL);
    exit(1);
}
