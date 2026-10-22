<?php
const DB_HOST = 'localhost';
const DB_NAME = 'techno24_db';
const DB_USER = 'root';
const DB_PASS = '';

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET time_zone = '+08:00'");
        } catch (PDOException $e) {
            error_log('DB error: ' . $e->getMessage());
            http_response_code(500);
            exit('Tidak dapat terhubung ke database. Periksa config/database.php dan pastikan MySQL berjalan.');
        }
    }
    return $pdo;
}
