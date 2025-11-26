<?php
// includes/pdf_generator.php

// Pfad zu DomPDF anpassen, je nachdem wo du es installiert hast
require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

function generateProjectPDF($projectId, $saveToDisk = false) {
    global $conn; // DB Verbindung nutzen

    // 1. Daten laden (exakt wie im View)
    // Projekt
    $stmt = $conn->prepare("SELECT p.*, c.company_name, c.contact_person, c.address, u.full_name as manager_name 
                            FROM projects p 
                            JOIN clients c ON p.client_id = c.id
                            LEFT JOIN users u ON p.user_id = u.id
                            WHERE p.id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $client = $project; // Aliasing für Template

    // Items
    $stmtItems = $conn->prepare("SELECT * FROM project_items WHERE project_id = ? ORDER BY position_order ASC");
    $stmtItems->bind_param("i", $projectId);
    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

    // Berechnungen
    $totalHours = 0;
    foreach($items as $i) $totalHours += $i['hours_estimated'];
    $bufferHours = $totalHours * ($project['risk_factor'] - 1);
    $grandTotal = $totalHours * $project['risk_factor'];

    // 2. HTML Output buffern
    ob_start();
    include __DIR__ . '/pdf_template.php';
    $html = ob_get_clean();

    // 3. DomPDF Setup
    $options = new Options();
    $options->set('isRemoteEnabled', true); // Für Bilder wichtig
    $dompdf = new Dompdf($options);
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Output
    if ($saveToDisk) {
        $filename = 'Planung_' . $projectId . '_' . date('Ymd') . '.pdf';
        $path = __DIR__ . '/../uploads/final/' . $filename;
        
        // Ordner erstellen falls nicht da
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        
        file_put_contents($path, $dompdf->output());
        return $path;
    } else {
        // Direkt im Browser anzeigen (Preview)
        $dompdf->stream("Vorschau.pdf", ["Attachment" => false]);
        exit;
    }
}
?>