<?php

// Configura i cookie di sessione
session_set_cookie_params([
    'lifetime' => 2592000,
    'path' => '/',
    'domain' => 'localhost',
    'secure' => false, // True, Solo HTTPS
    'httponly' => true, // Non accessibile da JavaScript
    'samesite' => 'Lax' // Protezione CSRF
]);

const DEBUG = true;

$host = 'db';
$dbname = 'test_db';
$username = 'test_db';
$password = 'devpass';

define('BASE_PATH', '/');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione: " . $e->getMessage());
}
?>