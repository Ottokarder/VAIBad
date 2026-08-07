<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo $base_url; ?>">
                <i class="fas fa-swimmer"></i> VAIBad
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>"><i class="fas fa-home"></i> Startseite</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="dataDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-database"></i> Daten eingeben
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>schwimmer.php"><i class="fas fa-user-friends"></i> Schwimmer</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>sponsoren.php"><i class="fas fa-hand-holding-heart"></i> Sponsoren</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>schwimmleistungen.php"><i class="fas fa-swimming-pool"></i> Schwimmleistungen</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>schwimmer_sponsoren.php"><i class="fas fa-link"></i> Schwimmer-Sponsoren</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>hauptsponsoren.php"><i class="fas fa-star"></i> Hauptsponsoren</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="viewsDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-chart-bar"></i> Auswertungen
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>auswertung_schwimmer.php"><i class="fas fa-user-chart"></i> Schwimmer-Statistiken</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>auswertung_sponsoren.php"><i class="fas fa-chart-line"></i> Sponsoren-Statistiken</a></li>
                            <li><a class="dropdown-item" href="<?php echo $base_url; ?>auswertung_gesamt.php"><i class="fas fa-chart-pie"></i> Gesamtübersicht</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_url; ?>database_import.php"><i class="fas fa-file-import"></i> Datenbank-Import</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid mt-4">
