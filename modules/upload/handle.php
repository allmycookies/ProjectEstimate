<?php
// modules/upload/handle.php
session_start();
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/db.php';

$uploader_type = 'admin'; // Standardwert

// Auth: Entweder User-Session ODER valider Client-Token
$is_admin_upload = isset($_SESSION['user_id']);
$is_client_upload = isset($_POST['token']) && isset($_POST['item_id']);

if (!$is_admin_upload && !$is_client_upload) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Nicht autorisiert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['success' => false, 'error' => 'Keine Datei empfangen']);
    exit;
}

$itemId = (int)$_POST['item_id'];

// Wenn Client-Upload, Token verifizieren
if ($is_client_upload) {
    $token = $_POST['token'];
    $uploader_type = 'client';

    // Finde das Projekt via Item ID und Token
    $stmt = $conn->prepare("
        SELECT p.id, p.status
        FROM projects p
        JOIN project_items pi ON p.id = pi.project_id
        WHERE pi.id = ? AND p.public_token = ?
    ");
    $stmt->bind_param("is", $itemId, $token);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();

    if (!$project) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Ungültige Kombination aus Token und Item']);
        exit;
    }

    if ($project['status'] === 'approved') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Projekt ist bereits genehmigt, keine Uploads mehr möglich.']);
        exit;
    }
}


$file = $_FILES['file'];
$uploadDir = '../../uploads/';

if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload Fehler Code: ' . $file['error']]);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['error' => 'Datei zu groß (Max 10MB)']);
    exit;
}

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

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$randomName = uniqid('file_', true) . '.' . $ext;
$targetPath = $uploadDir . $randomName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {

    // Speichere den Upload in der Datenbank
    $stmt = $conn->prepare("INSERT INTO item_uploads (item_id, uploader_type, file_path, original_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $itemId, $uploader_type, $randomName, $file['name']);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'original_name' => basename($file['name']),
        'stored_name' => $randomName,
        'path' => 'uploads/' . $randomName
    ]);
} else {
    echo json_encode(['error' => 'Konnte Datei nicht verschieben']);
}
?>