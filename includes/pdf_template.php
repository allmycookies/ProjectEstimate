<?php
// includes/pdf_template.php
// Variablen $project, $client, $items, $totalHours, $bufferHours, $grandTotal müssen vorher definiert sein.

// Logo Pfad für DomPDF (braucht absoluten Server-Pfad)
$logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/img/logo.png'; 
// Falls kein Logo da ist, Dummy nutzen oder ausblenden
$logoTag = file_exists($logoPath) ? '<img src="'.$logoPath.'" style="height: 50px;">' : '<h2>'.APP_NAME.'</h2>';
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 30px; }
        .meta-table { width: 100%; margin-bottom: 30px; }
        .meta-table td { vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #f8f9fa; border-bottom: 2px solid #ddd; padding: 10px; text-align: left; }
        .items-table td { border-bottom: 1px solid #eee; padding: 10px; vertical-align: top; }
        .totals-table { width: 40%; margin-left: auto; }
        .totals-table td { padding: 5px; text-align: right; }
        .total-row { font-weight: bold; font-size: 12pt; color: #000; border-top: 2px solid #333; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 8pt; text-align: center; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        .approval-box { background-color: #f0fff4; border: 1px solid #c3e6cb; padding: 15px; margin-top: 40px; border-radius: 5px; page-break-inside: avoid; }
    </style>
</head>
<body>
    <div class="header">
        <table width="100%">
            <tr>
                <td align="left"><?= $logoTag ?></td>
                <td align="right">
                    <strong>Projekt-Planung & Aufwandsschätzung</strong><br>
                    ID: #<?= $project['id'] ?><br>
                    Datum: <?= date('d.m.Y') ?>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td width="50%">
                <strong>Kunde:</strong><br>
                <?= htmlspecialchars($client['company_name']) ?><br>
                z.Hd. <?= htmlspecialchars($client['contact_person']) ?><br>
                <?= nl2br(htmlspecialchars($client['address'])) ?>
            </td>
            <td width="50%">
                <strong>Projekt:</strong><br>
                <?= htmlspecialchars($project['title']) ?><br><br>
                <strong>Projektleitung:</strong><br>
                <?= htmlspecialchars($project['manager_name'] ?? 'Admin') ?>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">#</th>
                <th width="30%">Aufgabe</th>
                <th width="50%">Beschreibung / Begründung</th>
                <th width="15%" align="right">Zeit (h)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($items as $idx => $item): ?>
            <tr>
                <td><?= $idx + 1 ?></td>
                <td><strong><?= htmlspecialchars($item['title']) ?></strong></td>
                <td style="font-size: 9pt; color: #555;">
                    <?= nl2br(htmlspecialchars($item['description'])) ?>
                </td>
                <td align="right"><?= number_format($item['hours_estimated'], 1, ',', '.') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Zwischensumme:</td>
            <td><?= number_format($totalHours, 1, ',', '.') ?> h</td>
        </tr>
        <tr>
            <td>Puffer & Risikozuschlag (<?= ($project['risk_factor']-1)*100 ?>%):</td>
            <td>+ <?= number_format($bufferHours, 1, ',', '.') ?> h</td>
        </tr>
        <tr class="total-row">
            <td style="padding-top: 10px;">Gesamtaufwand:</td>
            <td style="padding-top: 10px;"><?= number_format($grandTotal, 1, ',', '.') ?> h</td>
        </tr>
    </table>

    <?php if($project['status'] === 'approved'): ?>
    <div class="approval-box">
        <strong>Digital genehmigt</strong><br>
        Durch IP-Adresse: <?= $_SERVER['REMOTE_ADDR'] ?><br>
        Zeitstempel: <?= date('d.m.Y H:i:s') ?><br>
        <br>
        Hiermit wurde der Projektplan und der geschätzte Aufwand akzeptiert.
    </div>
    <?php endif; ?>

    <div class="footer">
        Seite <span class="pageNumber"></span> | Generiert mit <?= APP_NAME ?>
    </div>
</body>
</html>