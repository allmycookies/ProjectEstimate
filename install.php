<?php
// install.php
session_start();

// 1. Check ob bereits installiert
if (file_exists('config/config.php')) {
    die("Das System ist bereits installiert. Bitte löschen Sie 'config/config.php' für eine Neuinstallation.");
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_name = $_POST['db_name'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    
    $app_name = $_POST['app_name'];
    $admin_email = $_POST['admin_email'];
    $admin_pass = $_POST['admin_pass'];

    // Verbindung testen
    mysqli_report(MYSQLI_REPORT_STRICT);
    try {
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        
        // SQL Schema importieren (siehe oben, hier verkürzt dargestellt)
        $sql_schema = "
            CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                role ENUM('admin', 'manager') DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(255) PRIMARY KEY,
                setting_value TEXT
            );

            CREATE TABLE IF NOT EXISTS clients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                company_name VARCHAR(255) NOT NULL,
                contact_person VARCHAR(255),
                email VARCHAR(255),
                address TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS projects (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                client_id INT,
                title VARCHAR(255) NOT NULL,
                risk_factor DECIMAL(5,2) DEFAULT 1.0,
                status ENUM('draft', 'sent', 'approved', 'rejected', 'changes_requested') DEFAULT 'draft',
                public_token VARCHAR(64) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
                FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS project_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id INT,
                position_order INT,
                title VARCHAR(255) NOT NULL,
                description TEXT,
                hours_estimated DECIMAL(8,2),
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            );

            CREATE TABLE IF NOT EXISTS item_uploads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                item_id INT,
                uploader_type ENUM('admin', 'client') DEFAULT 'admin',
                file_path VARCHAR(255) NOT NULL,
                original_name VARCHAR(255),
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (item_id) REFERENCES project_items(id) ON DELETE CASCADE
            );
            
            INSERT IGNORE INTO settings (setting_key, setting_value) VALUES 
            ('smtp_host', ''), ('smtp_user', ''), ('smtp_pass', ''), ('smtp_port', '587'), 
            ('sender_email', ''), ('sender_name', 'Mein Agentur Tool'), 
            ('gemini_key', ''), ('company_logo', '');
        ";
        
        // Multi-Query ausführen
        $conn->multi_query($sql_schema);
        while ($conn->next_result()) {;} // Ergebnisse leeren

        // Admin User anlegen
        $hash = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, full_name, role) VALUES (?, ?, 'Super Admin', 'admin')");
        $stmt->bind_param("ss", $admin_email, $hash);
        $stmt->execute();

        // Config Datei schreiben
        if (!is_dir('config')) {
            mkdir('config', 0755, true);
        }
        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_NAME', '$db_name');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('APP_NAME', '$app_name');
define('BASE_URL', 'http://" . $_SERVER['HTTP_HOST'] . str_replace('install.php', '', $_SERVER['REQUEST_URI']) . "');
";
        file_put_contents('config/config.php', $config_content);

        // Weiterleitung
        header("Location: index.php?installed=true");
        exit;

    } catch (Exception $e) {
        $message = "Datenbank-Fehler: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Installer - Estimation Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body { background-color: #f4f6f8; } .card { border:none; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }</style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100">
    <div class="container" style="max-width: 500px;">
        <div class="card p-4">
            <h3 class="text-center mb-4">Installation</h3>
            <?php if($message): ?><div class="alert alert-danger"><?= $message ?></div><?php endif; ?>
            
            <form method="POST">
                <h6 class="text-uppercase text-muted fs-7">Datenbank</h6>
                <div class="mb-2"><input type="text" name="db_host" class="form-control" placeholder="Host (z.B. localhost)" value="localhost" required></div>
                <div class="mb-2"><input type="text" name="db_name" class="form-control" placeholder="Datenbank Name" required></div>
                <div class="mb-2"><input type="text" name="db_user" class="form-control" placeholder="DB Benutzer" required></div>
                <div class="mb-3"><input type="password" name="db_pass" class="form-control" placeholder="DB Passwort"></div>

                <hr>
                <h6 class="text-uppercase text-muted fs-7">System & Admin</h6>
                <div class="mb-2"><input type="text" name="app_name" class="form-control" placeholder="App Name (z.B. Meine Agentur)" required></div>
                <div class="mb-2"><input type="email" name="admin_email" class="form-control" placeholder="Admin E-Mail" required></div>
                <div class="mb-3"><input type="password" name="admin_pass" class="form-control" placeholder="Admin Passwort" required></div>

                <button type="submit" class="btn btn-primary w-100">Installieren & Starten</button>
            </form>
        </div>
    </div>
</body>
</html>