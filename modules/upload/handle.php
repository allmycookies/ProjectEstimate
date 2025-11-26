<?php
// modules/upload/handle.php
session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';

// Nur eingeloggte User dürfen hochladen
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Nicht autorisiert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['error' => 'Keine Datei empfangen']);
    exit;
}

$file = $_FILES['file'];
$uploadDir = '../../uploads/';

// Sicherstellen, dass Verzeichnis existiert
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// 1. Prüfung: Fehlercode
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload Fehler Code: ' . $file['error']]);
    exit;
}

// 2. Prüfung: Dateigröße (Max 10MB)
if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['error' => 'Datei zu groß (Max 10MB)']);
    exit;
}

// 3. Prüfung: MIME-Type (Whitelist)
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 
    'application/pdf', 
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
    'text/plain'
];

if (!in_array($mime, $allowedMimes)) {
    echo json_encode(['error' => 'Dateityp nicht erlaubt: ' . $mime]);
    exit;
}

// 4. Umbenennen & Verschieben
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$randomName = uniqid('file_', true) . '.' . $ext;
$targetPath = $uploadDir . $randomName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Erfolgreich
    echo json_encode([
        'success' => true,
        'original_name' => basename($file['name']),
        'stored_name' => $randomName, // Das speichern wir in der DB
        'path' => 'uploads/' . $randomName
    ]);
} else {
    echo json_encode(['error' => 'Konnte Datei nicht verschieben']);
}
?>