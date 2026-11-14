<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=require',
            DB_CONFIG['host'],
            DB_CONFIG['port'],
            DB_CONFIG['name']
        ),
        DB_CONFIG['user'],
        DB_CONFIG['pass'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    die('Database connection failed.');
}

function get_pdo(): PDO
{
    global $pdo;
    return $pdo;
}