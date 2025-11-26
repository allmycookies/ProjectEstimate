<?php
// modules/projects/list.php
require_once 'includes/db.php';

// Filter Logik
$statusFilter = $_GET['status'] ?? 'all';
$whereClause = "1=1";

if ($statusFilter !== 'all') {
    // Sicherheit: Nur erlaubte Werte
    $allowed = ['draft', 'sent', 'approved', 'rejected', 'changes_requested'];
    if (in_array($statusFilter, $allowed)) {
        $whereClause .= " AND p.status = '" . $conn->real_escape_string($statusFilter) . "'";
    }
}

// SQL: Projekte laden inkl. Kundenname und Berechnung der geschätzten Stunden (Summe der Items)
// Hinweis: IFNULL fängt Projekte ohne Items ab
$sql = "SELECT p.*, c.company_name, u.full_name as owner,
        (SELECT SUM(hours_estimated) FROM project_items WHERE project_id = p.id) as total_hours
        FROM projects p 
        LEFT JOIN clients c ON p.client_id = c.id
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE $whereClause
        ORDER BY p.created_at DESC";

$result = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-folder2-open"></i> Projekte</h2>
            <p class="text-muted small mb-0">Verwaltung aller Planungen und Schätzungen</p>
        </div>
        <a href="index.php?module=projects&page=editor" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Neues Projekt
        </a>
    </div>

    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $statusFilter === 'all' ? 'active' : '' ?>" href="index.php?module=projects&page=list">Alle</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $statusFilter === 'draft' ? 'active' : '' ?>" href="index.php?module=projects&page=list&status=draft">Entwürfe</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $statusFilter === 'sent' ? 'active' : '' ?>" href="index.php?module=projects&page=list&status=sent">Versendet</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $statusFilter === 'approved' ? 'active' : '' ?>" href="index.php?module=projects&page=list&status=approved">Genehmigt</a>
        </li>
    </ul>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="bg-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Projekt / Titel</th>
                            <th>Kunde</th>
                            <th>Aufwand</th>
                            <th>Status</th>
                            <th>Erstellt</th>
                            <th class="text-end pe-4">Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php 
                                    // Puffer berechnen für Anzeige
                                    $baseHours = (float)$row['total_hours'];
                                    $riskFactor = (float)$row['risk_factor'];
                                    $totalWithBuffer = $baseHours * $riskFactor;
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted">#<?= $row['id'] ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></div>
                                        <?php if($row['owner']): ?>
                                            <div class="small text-muted"><i class="bi bi-person"></i> <?= htmlspecialchars($row['owner']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($row['company_name']): ?>
                                            <a href="#" class="text-decoration-none text-dark fw-medium"><?= htmlspecialchars($row['company_name']) ?></a>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Kein Kunde</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($baseHours > 0): ?>
                                            <div><?= number_format($totalWithBuffer, 1, ',', '.') ?> h</div>
                                            <small class="text-muted" title="Risikofaktor">x<?= $row['risk_factor'] ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
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
                                        $statusLabel = match($row['status']) {
                                            'draft' => 'Entwurf',
                                            'sent' => 'Versendet',
                                            'approved' => 'Genehmigt',
                                            'rejected' => 'Abgelehnt',
                                            'changes_requested' => 'Änderung angefr.',
                                            default => $row['status']
                                        };
                                        ?>
                                        <span class="badge <?= $badge ?> rounded-pill fw-normal">
                                            <?= $statusLabel ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small"><?= date('d.m.Y', strtotime($row['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <div class="btn-group">
                                            <a href="index.php?module=projects&page=editor&id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Bearbeiten">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if($row['public_token']): ?>
                                                <a href="index.php?module=client&page=view&token=<?= $row['public_token'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Kunden-Vorschau">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if($row['status'] === 'approved'): ?>
                                                <a href="modules/projects/download_pdf.php?id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger" title="PDF">
                                                    <i class="bi bi-file-pdf"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted mb-2"><i class="bi bi-clipboard-x display-4"></i></div>
                                    <p class="mb-0">Keine Projekte in dieser Ansicht gefunden.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white small text-muted">
            Zeigt alle Projekte im System.
        </div>
    </div>
</div>