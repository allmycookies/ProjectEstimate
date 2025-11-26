<?php
// modules/projects/save.php
session_start();
header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../includes/db.php'; // Angenommen du hast eine simple DB connection $conn

// Auth Check (simpel)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nicht eingeloggt']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Keine Daten empfangen']);
    exit;
}

$proj = $input['project'];
$items = $input['items'];
$userId = $_SESSION['user_id'];

// DB Verbindung aufbauen (Beispiel mysqli)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'DB Fehler']));
}

// Transaktion starten (Wichtig: Alles oder Nichts)
$conn->begin_transaction();

try {
    $projectId = $proj['id'] ?? null;
    $token = null; // Token wird hier initialisiert und je nach Fall korrekt zugewiesen

    if ($projectId) {
        // UPDATE
        $stmt = $conn->prepare("UPDATE projects SET client_id=?, title=?, risk_factor=?, status=? WHERE id=? AND user_id=?");
        $stmt->bind_param("isdsii", $proj['client_id'], $proj['title'], $proj['risk_factor'], $proj['status'], $projectId, $userId);
        $stmt->execute();
        
        // Bei einem Update den existierenden Token aus der Datenbank laden
        $tRes = $conn->query("SELECT public_token FROM projects WHERE id = $projectId");
        $token = $tRes->fetch_assoc()['public_token'] ?? null;

        // Items: Einfachheitshalber löschen wir alle alten und schreiben neu (bei komplexen Systemen macht man Diffing)
        $conn->query("DELETE FROM project_items WHERE project_id = $projectId");
    } else {
        // INSERT
        // Bei einem neuen Projekt einen neuen Token generieren
        $token = bin2hex(random_bytes(32));
        $stmt = $conn->prepare("INSERT INTO projects (user_id, client_id, title, risk_factor, status, public_token) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iissss", $userId, $proj['client_id'], $proj['title'], $proj['risk_factor'], $proj['status'], $token);
        $stmt->execute();
        $projectId = $conn->insert_id;
    }

    // START UPDATE: Mail Versand wenn Status "sent"
    if ($proj['status'] === 'sent') {
        // Kundendaten holen
        $cRes = $conn->query("SELECT * FROM clients WHERE id = " . intval($proj['client_id']));
        $client = $cRes->fetch_assoc();
        
        if ($client && $client['email']) {
            require_once '../../includes/mailer.php';
            
            $link = BASE_URL . "index.php?module=client&page=view&token=" . $token; // $token muss oben definiert sein (bei UPDATE evtl. neu laden)
            // Hinweis: Bei UPDATE muss der Token aus der DB geholt werden, wenn er nicht neu erstellt wurde:
            if(!isset($token)) { // Diese Bedingung ist nach der vorgenommenen Anpassung der Token-Logik i.d.R. nicht mehr notwendig
                $tRes = $conn->query("SELECT public_token FROM projects WHERE id = $projectId");
                $token = $tRes->fetch_assoc()['public_token'];
                $link = BASE_URL . "index.php?module=client&page=view&token=" . $token;
            }

            $subject = "Projektplanung zur Prüfung: " . $proj['title'];
            $body = "
                <h3>Hallo {$client['contact_person']},</h3>
                <p>Eine neue Projektplanung <strong>{$proj['title']}</strong> liegt zur Prüfung bereit.</p>
                <p>Bitte klicken Sie auf folgenden Link, um den Plan einzusehen und freizugeben:</p>
                <p><a href='$link' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Planung öffnen</a></p>
                <p>Oder Link kopieren: $link</p>
            ";
            
            sendMail($client['email'], $subject, $body);
        }
    }
    // END UPDATE

    // ITEMS INSERT
    $stmtItem = $conn->prepare("INSERT INTO project_items (project_id, position_order, title, description, hours_estimated) VALUES (?, ?, ?, ?, ?)");
    $stmtFile = $conn->prepare("INSERT INTO item_uploads (item_id, uploader_type, file_path, original_name) VALUES (?, 'admin', ?, ?)");
    
    foreach ($items as $index => $item) {
        $pos = $index + 1;
        $stmtItem->bind_param("iisss", $projectId, $pos, $item['title'], $item['description'], $item['hours']);
        $stmtItem->execute();
        $itemId = $conn->insert_id; // Die ID der gerade erstellten Zeile

        // Dateien speichern, falls vorhanden
        if (!empty($item['files'])) {
            foreach ($item['files'] as $file) {
                // Wir speichern den Dateinamen (stored_name) als file_path
                $stmtFile->bind_param("iss", $itemId, $file['stored_name'], $file['original_name']);
                $stmtFile->execute();
            }
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'project_id' => $projectId]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>