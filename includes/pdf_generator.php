<?php
// includes/pdf_generator.php

require_once __DIR__ . '/../vendor/dompdf/autoload.inc.php';

use Dompdf\Dompdf;
use Dompdf\Options;

function generateProjectPDF($projectId, $saveToDisk = false) {
    global $conn;

    // 1. Daten laden
    $stmt = $conn->prepare("SELECT p.*, c.company_name, c.contact_person, c.address, u.full_name as manager_name
                            FROM projects p
                            JOIN clients c ON p.client_id = c.id
                            LEFT JOIN users u ON p.user_id = u.id
                            WHERE p.id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $project = $stmt->get_result()->fetch_assoc();
    $client = $project;

    $stmtItems = $conn->prepare("SELECT * FROM project_items WHERE project_id = ? ORDER BY position_order ASC");
    $stmtItems->bind_param("i", $projectId);
    $stmtItems->execute();
    $items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

    // Lade Logo-Pfad aus Settings
    $logo_res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'company_logo'");
    $logo_path_relative = $logo_res->fetch_assoc()['setting_value'] ?? 'assets/img/logo.png';

    // Wichtig: Absoluten Pfad für DomPDF erstellen
    $logo_path_absolute = realpath(__DIR__ . '/../' . $logo_path_relative);

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
    $options->set('isRemoteEnabled', true);
    $options->set('chroot', realpath(__DIR__ . '/../')); // Basis-Verzeichnis für Assets
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 4. Output
    if ($saveToDisk) {
        $filename = 'Planung_' . $projectId . '_' . date('Ymd') . '.pdf';
        $path = __DIR__ . '/../uploads/final/' . $filename;
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, $dompdf->output());
        return $path;
    } else {
        $dompdf->stream("Vorschau.pdf", ["Attachment" => false]);
        exit;
    }
}
?>