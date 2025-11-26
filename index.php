<?php
// index.php
session_start();

if (!file_exists('config/config.php')) {
    header("Location: install.php");
    exit;
}

require_once 'config/config.php';
// require_once 'includes/db.php'; // DB Verbindung aufbauen

// Routing Logik
$module = $_GET['module'] ?? 'dashboard';
$page   = $_GET['page'] ?? 'index';

// Sicherheit: Erlaube nur Buchstaben/Zahlen um Pfad-Traversal zu verhindern
$module = preg_replace('/[^a-zA-Z0-9_]/', '', $module);
$page   = preg_replace('/[^a-zA-Z0-9_]/', '', $page);

// Auth Check (Ausnahme: Login Seite und Client View mit Token)
$is_logged_in = isset($_SESSION['user_id']);
$is_client_view = ($module === 'client');

if (!$is_logged_in && $module !== 'auth' && !$is_client_view) {
    header("Location: index.php?module=auth&page=login");
    exit;
}

// Layout laden
// Client View bekommt ein anderes, reduziertes Layout ohne Sidebar
if ($is_client_view) {
    include "modules/client/view.php"; // Direkt laden, da komplett eigenes Design
    exit;
}

// Admin Layout
include 'includes/header.php';

$file_path = "modules/$module/$page.php";
if (file_exists($file_path)) {
    include $file_path;
} else {
    echo "<div class='container mt-5'><h3>404 - Modul nicht gefunden</h3></div>";
}

include 'includes/footer.php';
?>