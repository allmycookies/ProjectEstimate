<?php
// includes/db.php
// Nutzt die Konstanten aus config/config.php

if (!defined('DB_HOST')) {
    die("Konfiguration fehlt.");
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Verbindungsfehler zur Datenbank: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>