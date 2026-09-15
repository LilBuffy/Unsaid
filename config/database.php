<?php
    
date_default_timezone_set('Asia/Manila');

$DB_HOST = "localhost";
$DB_NAME = "unsaid";
$DB_USER = "root";
$DB_PASS = "";
$DB_CHARSET = "utf8mb4";

try {
    $dsn = "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=$DB_CHARSET";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+08:00'");
} catch (PDOException $e) {
    http_response_code(500);
    die("Database connection failed. Please try again later.");
}
