<?php
// modules/client/action.php
header('Content-Type: application/json');
require_once '../../config/config.php';
require_once '../../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$status = $input['status'] ?? '';

if (!$token || !in_array($status, ['approved', 'changes_requested'])) {
    echo json_encode(['success' => false, 'message' => 'Ungültige Daten']);
    exit;
}

// Update Status
$stmt = $conn->prepare("UPDATE projects SET status = ? WHERE public_token = ?");
$stmt->bind_param("ss", $status, $token);

if ($stmt->execute()) {
    
    // START UPDATE: PDF Generierung bei Genehmigung
    if ($status === 'approved') {
        // Projekt ID, Titel und E-Mails von Kunde und Admin holen (via Token)
        // Das SELECT-Query wurde erweitert, um c.email als client_email und u.email als admin_email zu holen.
        $pQuery = $conn->query("SELECT p.id, p.title, c.email as client_email, u.email as admin_email FROM projects p JOIN clients c ON p.client_id=c.id JOIN users u ON p.user_id=u.id WHERE p.public_token = '$token'");
        $pData = $pQuery->fetch_assoc();
        
        if ($pData) {
            require_once '../../includes/pdf_generator.php';
            
            // PDF generieren und speichern
            $pdfPath = generateProjectPDF($pData['id'], true); // true = speichern
            
            // START UPDATE: Mail an Kunde UND Admin
            require_once '../../includes/mailer.php';
            
            // 1. Mail an Kunde (mit PDF)
            $clientSubject = "Genehmigung bestätigt: " . $pData['title'];
            $clientBody = "<h3>Vielen Dank!</h3><p>Wir haben Ihre Genehmigung erhalten. Anbei finden Sie den fixierten Plan als PDF.</p>";
            sendMail($pData['client_email'], $clientSubject, $clientBody, $pdfPath);
            
            // 2. Mail an Admin (Info)
            $adminSubject = "✅ Kunde hat genehmigt: " . $pData['title'];
            $adminBody = "<p>Der Kunde hat den Plan soeben genehmigt.</p>";
            sendMail($pData['admin_email'], $adminSubject, $adminBody);
            // END UPDATE
        }
    }
    // ENDE UPDATE

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB Fehler']);
}
?>