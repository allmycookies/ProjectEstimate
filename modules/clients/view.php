<?php
// modules/client/view.php
// ACHTUNG: Hier keine Session-Prüfung auf User-ID! Nur Token Check.
require_once 'config/config.php';
require_once 'includes/db.php';

$token = $_GET['token'] ?? '';

// Projekt laden via Token
$stmt = $conn->prepare("
    SELECT p.*, c.company_name, u.full_name as manager_name 
    FROM projects p 
    JOIN clients c ON p.client_id = c.id
    JOIN users u ON p.user_id = u.id
    WHERE p.public_token = ? LIMIT 1
");
$stmt->bind_param("s", $token);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    die("<div class='container mt-5'><h1>404</h1><p>Projekt nicht gefunden oder Link ungültig.</p></div>");
}

// Items laden
$stmtItems = $conn->prepare("SELECT * FROM project_items WHERE project_id = ? ORDER BY position_order ASC");
$stmtItems->bind_param("i", $project['id']);
$stmtItems->execute();
$items = $stmtItems->get_result()->fetch_all(MYSQLI_ASSOC);

// Berechnungen
$totalHours = 0;
foreach($items as $i) $totalHours += $i['hours_estimated'];
$bufferHours = $totalHours * ($project['risk_factor'] - 1);
$grandTotal = $totalHours * $project['risk_factor'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planung: <?= htmlspecialchars($project['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .project-sheet { background: white; max-width: 900px; margin: 30px auto; padding: 40px; box-shadow: 0 0 20px rgba(0,0,0,0.05); }
        .action-bar { position: fixed; bottom: 0; left: 0; right: 0; background: white; border-top: 1px solid #ddd; padding: 15px; text-align: center; box-shadow: 0 -5px 10px rgba(0,0,0,0.05); z-index: 1000; }
        .status-badge { font-size: 0.9rem; padding: 5px 10px; border-radius: 4px; }
    </style>
</head>
<body class="pb-5">

    <div class="project-sheet">
        <div class="row mb-5 border-bottom pb-4">
            <div class="col-6">
                <h5 class="text-muted text-uppercase small">Projektplanung für</h5>
                <h3><?= htmlspecialchars($project['company_name']) ?></h3>
            </div>
            <div class="col-6 text-end">
                <h5 class="text-muted text-uppercase small">Erstellt von</h5>
                <p class="mb-0 fw-bold"><?= htmlspecialchars($project['manager_name']) ?></p>
                <p class="text-muted small"><?= date('d.m.Y', strtotime($project['created_at'])) ?></p>
                
                <?php if($project['status'] == 'approved'): ?>
                    <span class="badge bg-success mt-2">GENEHMIGT</span>
                <?php endif; ?>
            </div>
        </div>

        <h1 class="mb-4"><?= htmlspecialchars($project['title']) ?></h1>

        <table class="table table-striped table-hover mt-4">
            <thead class="table-dark">
                <tr>
                    <th style="width: 5%">#</th>
                    <th style="width: 35%">Aufgabe</th>
                    <th style="width: 45%">Details & Begründung</th>
                    <th style="width: 15%" class="text-end">Zeit (h)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $idx => $item): ?>
                <tr>
                    <td><?= $idx + 1 ?></td>
                    <td class="fw-bold">
                        <?= htmlspecialchars($item['title']) ?>
                        <div class="mt-2">
                             <button class="btn btn-link btn-sm p-0 text-decoration-none text-muted small" onclick="alert('Upload Funktion folgt in Kürze')">
                                <i class="bi bi-paperclip"></i> Datei anhängen
                             </button>
                        </div>
                    </td>
                    <td class="text-muted small"><?= nl2br(htmlspecialchars($item['description'])) ?></td>
                    <td class="text-end fw-bold"><?= number_format($item['hours_estimated'], 1, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="border-top-2">
                <tr>
                    <td colspan="3" class="text-end">Basis Aufwand:</td>
                    <td class="text-end"><?= number_format($totalHours, 1, ',', '.') ?> h</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end text-muted">Risiko-Puffer & Kommunikation:</td>
                    <td class="text-end text-muted">+ <?= number_format($bufferHours, 1, ',', '.') ?> h</td>
                </tr>
                <tr class="fs-5">
                    <td colspan="3" class="text-end fw-bold">Gesamt Projektzeit:</td>
                    <td class="text-end fw-bold text-primary"><?= number_format($grandTotal, 1, ',', '.') ?> h</td>
                </tr>
            </tfoot>
        </table>
        
        <div class="alert alert-info mt-5 small">
            <i class="bi bi-info-circle"></i> Diese Aufwandschätzung basiert auf den aktuellen Anforderungen. 
            Durch Klicken auf "Genehmigen" akzeptieren Sie den Ablaufplan.
        </div>
    </div>

    <?php if($project['status'] !== 'approved'): ?>
    <div class="action-bar" id="actionBar">
        <div class="container d-flex justify-content-center gap-3">
            <button class="btn btn-outline-danger" onclick="updateStatus('changes_requested')">
                <i class="bi bi-chat-text"></i> Änderungen anfragen
            </button>
            <button class="btn btn-success px-5" onclick="updateStatus('approved')">
                <i class="bi bi-check-circle-fill"></i> PLANUNG GENEHMIGEN
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Kleines Inline JS für die Buttons
        function updateStatus(newStatus) {
            const token = "<?= $token ?>";
            if(!confirm("Sind Sie sicher?")) return;

            fetch('modules/client/action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token: token, status: newStatus })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    alert("Status aktualisiert!");
                    location.reload();
                } else {
                    alert("Fehler: " + data.message);
                }
            });
        }
    </script>
</body>
</html>