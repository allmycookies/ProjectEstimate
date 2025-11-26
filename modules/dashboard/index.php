<?php
// modules/dashboard/index.php
require_once 'includes/db.php';

// Projekte laden (mit Kundenname)
$sql = "SELECT p.*, c.company_name, u.full_name as owner 
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id
        LEFT JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
        <a href="index.php?module=projects&page=editor" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Neues Projekt
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Projekt / Titel</th>
                        <th>Kunde</th>
                        <th>Status</th>
                        <th>Erstellt am</th>
                        <th class="text-end pe-4">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold"><?= htmlspecialchars($row['title']) ?></div>
                                    <small class="text-muted">ID: #<?= $row['id'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($row['company_name']) ?></td>
                                <td>
                                    <?php 
                                    $badge = match($row['status']) {
                                        'draft' => 'bg-secondary',
                                        'sent' => 'bg-info text-dark',
                                        'approved' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        'changes_requested' => 'bg-warning text-dark',
                                        default => 'bg-light text-dark'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?> rounded-pill text-uppercase">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td><?= date('d.m.Y', strtotime($row['created_at'])) ?></td>
                                <td class="text-end pe-4">
                                    <a href="index.php?module=projects&page=editor&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Bearbeiten">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="index.php?module=client&page=view&token=<?= $row['public_token'] ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="Kunden-Ansicht öffnen">
                                        <i class="bi bi-eye"></i>
                                    </a>
<?php if($row['status'] === 'approved'): ?>
    <a href="modules/projects/download_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="PDF herunterladen">
        <i class="bi bi-file-earmark-pdf"></i>
    </a>
<?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">Keine Projekte gefunden. Starten Sie eine neue Planung!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// modules/projects/download_pdf.php
session_start();
require_once '../../config/config.php';
require_once '../../includes/db.php';
require_once '../../includes/pdf_generator.php';

if (!isset($_SESSION['user_id'])) die("Access denied");

$id = $_GET['id'] ?? 0;
// Generiert PDF on-the-fly und zeigt es im Browser an
generateProjectPDF($id, false); 
?>