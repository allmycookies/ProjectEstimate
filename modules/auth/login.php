<?php
// modules/auth/login.php
// Wird via index.php?module=auth&page=login geladen

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'includes/db.php';
    
    $email = $_POST['email'];
    $pass  = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT id, password, full_name, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($pass, $row['password'])) {
            // Login erfolgreich
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['name'] = $row['full_name'];
            
            header("Location: index.php?module=dashboard&page=index");
            exit;
        }
    }
    
    $error = "Zugangsdaten ungültig.";
}
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="card shadow p-4" style="max-width: 400px; width: 100%;">
        <div class="text-center mb-4">
            <h3 class="fw-bold"><?= defined('APP_NAME') ? APP_NAME : 'Login' ?></h3>
            <p class="text-muted">Bitte melden Sie sich an</p>
        </div>
        
        <?php if($error): ?>
            <div class="alert alert-danger btn-sm"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">E-Mail</label>
                <input type="email" name="email" class="form-control" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label">Passwort</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Anmelden</button>
        </form>
    </div>
</div>