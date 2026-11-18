<?php
declare(strict_types=1);

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // ------------------------------------------------------------------
    // Database credentials
    // ------------------------------------------------------------------
    // Local XAMPP defaults are used when no environment variables exist.
    // On InfinityFree (or any shared host), replace the defaults below
    // with the credentials from your hosting control panel:
    //   - $host: the MySQL hostname shown by your host
    //     (e.g. sql123.infinityfree.com)
    //   - $name: your database name (e.g. if0_12345678_wastewise_db)
    //   - $user: your database username (e.g. if0_12345678)
    //   - $pass: your database password
    //
    // If your host supports environment variables, you can set DB_HOST,
    // DB_NAME, DB_USER and DB_PASS instead of editing this file.
    // ------------------------------------------------------------------
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'wastewise_db';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

    $pdo = new PDO(
        "mysql:host={$host};dbname={$name};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    return $pdo;
}
