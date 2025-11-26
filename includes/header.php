<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= defined('APP_NAME') ? APP_NAME : 'Tool' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* Kleiner Fix für aktive Menüpunkte */
        .navbar-nav .nav-link.active { font-weight: bold; color: #fff !important; }
    </style>
</head>
<body>
    <?php
        // Aktives Modul ermitteln für Highlighting im Menü
        $currentModule = $_GET['module'] ?? 'dashboard';
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-rocket-takeoff"></i> <?= defined('APP_NAME') ? APP_NAME : 'Estimation Tool' ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentModule == 'dashboard' ? 'active' : '' ?>" href="index.php?module=dashboard&page=index">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentModule == 'projects' ? 'active' : '' ?>" href="index.php?module=projects&page=list">
                            <i class="bi bi-folder2-open"></i> Projekte
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentModule == 'clients' ? 'active' : '' ?>" href="index.php?module=clients&page=index">
                            <i class="bi bi-people"></i> Kunden
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link <?= $currentModule == 'settings' ? 'active' : '' ?>" href="index.php?module=settings&page=index">
                            <i class="bi bi-gear"></i> Einstellungen
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="index.php?module=auth&page=logout">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container-fluid">