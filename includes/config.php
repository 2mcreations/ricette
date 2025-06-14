<?php

// Configura i cookie di sessione
session_set_cookie_params([
    'lifetime' => 0, // Sessione valida fino alla chiusura del browser
    'path' => '/',
    'domain' => null, // Usa il dominio corrente
    'secure' => true, // Solo HTTPS
    'httponly' => true, // Non accessibile da JavaScript
    'samesite' => 'Lax' // Protezione CSRF
]);

$host = 'localhost';
$dbname = 'xh2mcrea_ricette';
$username = 'xh2mcrea_ricette';
$password = ',v#$g{rmEwNe';

define('BASE_PATH', '/');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione: " . $e->getMessage());
}
?>