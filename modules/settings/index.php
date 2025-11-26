<?php
// modules/settings/index.php
require_once 'includes/db.php';

// Speichern Logik
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
    }
    echo "<div class='alert alert-success'>Einstellungen gespeichert!</div>";
}

// Laden
$settings = [];
$res = $conn->query("SELECT * FROM settings");
while($row = $res->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
?>

<div class="container-fluid" style="max-width: 800px;">
    <h2 class="mb-4"><i class="bi bi-gear"></i> Einstellungen</h2>

    <form method="POST" class="card shadow-sm p-4">
        
        <h5 class="text-muted border-bottom pb-2">🤖 KI & System</h5>
        <div class="mb-3">
            <label class="form-label">Google Gemini API Key</label>
            <input type="text" name="gemini_key" class="form-control" value="<?= htmlspecialchars($settings['gemini_key'] ?? '') ?>">
            <div class="form-text">Wird für die Zeitschätzung benötigt.</div>
        </div>

        <h5 class="text-muted border-bottom pb-2 mt-4">📧 E-Mail Versand (SMTP)</h5>
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label">SMTP Host</label>
                <input type="text" name="smtp_host" class="form-control" placeholder="smtp.provider.de" value="<?= htmlspecialchars($settings['smtp_host'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Port</label>
                <input type="text" name="smtp_port" class="form-control" placeholder="587" value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">SMTP Benutzer</label>
                <input type="text" name="smtp_user" class="form-control" value="<?= htmlspecialchars($settings['smtp_user'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">SMTP Passwort</label>
                <input type="password" name="smtp_pass" class="form-control" value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>">
            </div>
        </div>
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <label class="form-label">Absender E-Mail</label>
                <input type="email" name="sender_email" class="form-control" value="<?= htmlspecialchars($settings['sender_email'] ?? '') ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Absender Name</label>
                <input type="text" name="sender_name" class="form-control" value="<?= htmlspecialchars($settings['sender_name'] ?? '') ?>">
            </div>
        </div>

        <div class="mt-4 text-end">
            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Einstellungen speichern</button>
        </div>
    </form>
</div>