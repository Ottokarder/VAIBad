<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Statistiken abrufen
try {
    // Anzahl Schwimmer
    $stmt = $pdo->query("SELECT COUNT(*) FROM schwimmer");
    $schwimmer_count = $stmt->fetchColumn();
    
    // Anzahl Sponsoren
    $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren");
    $sponsoren_count = $stmt->fetchColumn();
    
    // Anzahl Hauptsponsoren
    $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren WHERE ist_hauptsponsor = 1");
    $hauptsponsoren_count = $stmt->fetchColumn();
    
    // Gesamtzahl Bahnen
    $stmt = $pdo->query("SELECT COALESCE(SUM(anzahl_bahnen_vormittag), 0) + COALESCE(SUM(anzahl_bahnen_nachmittag), 0) FROM schwimmleistungen");
    $gesamt_bahnen = $stmt->fetchColumn();
    
    // Gesamtspenden (aus View)
    $stmt = $pdo->query("SELECT COALESCE(SUM(spendenbetrag), 0) FROM v_schwimmer_sponsor_spenden");
    $gesamt_spenden = $stmt->fetchColumn();
    
    // Gesamtstrecke
    $stmt = $pdo->query("SELECT COALESCE(SUM(gesamtstrecke_m), 0) FROM v_schwimmer_gesamtstrecke");
    $gesamt_strecke = $stmt->fetchColumn();
    
    // Letzte Aktivitäten
    $stmt = $pdo->query("SELECT s.vorname, s.nachname, sl.anzahl_bahnen_vormittag, sl.anzahl_bahnen_nachmittag, sl.datum 
                         FROM schwimmleistungen sl 
                         JOIN schwimmer s ON sl.schwimmer_id = s.id 
                         ORDER BY sl.datum DESC, sl.id DESC LIMIT 5");
    $letzte_aktivitaeten = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Fehler beim Abrufen der Statistiken: " . $e->getMessage();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <h1 class="page-header">
            <i class="fas fa-swimming-pool"></i> VAIBad - Datenverwaltung
        </h1>
        <p class="lead">Willkommen im Datenverwaltungssystem für das VAIBad. Hier können Sie Schwimmer, Sponsoren und Leistungen verwalten.</p>
    </div>
</div>

<!-- Statistik-Karten -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="stat-value"><?php echo htmlspecialchars($schwimmer_count ?? '0'); ?></div>
            <div class="stat-label">Schwimmer</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-icon">
                <i class="fas fa-hand-holding-heart"></i>
            </div>
            <div class="stat-value"><?php echo htmlspecialchars($sponsoren_count ?? '0'); ?></div>
            <div class="stat-label">Sponsoren</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value"><?php echo htmlspecialchars($hauptsponsoren_count ?? '0'); ?></div>
            <div class="stat-label">Hauptsponsoren</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="stat-icon">
                <i class="fas fa-swimming-pool"></i>
            </div>
            <div class="stat-value"><?php echo htmlspecialchars($gesamt_bahnen ?? '0'); ?></div>
            <div class="stat-label">Gesamtbahnen</div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="stat-icon">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_spenden ?? 0, 2, ',', '.'); ?> €</div>
            <div class="stat-label">Gesamtspenden</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
            <div class="stat-icon">
                <i class="fas fa-route"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_strecke ?? 0, 0, ',', '.'); ?> m</div>
            <div class="stat-label">Gesamtstrecke</div>
        </div>
    </div>
    
    <div class="col-md-12 col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="fas fa-clock"></i> Letzte Aktivitäten
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Schwimmer</th>
                                <th>Bahnen</th>
                                <th>Datum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($letzte_aktivitaeten)): ?>
                                <?php foreach ($letzte_aktivitaeten as $aktivitaet): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($aktivitaet['vorname'] . ' ' . $aktivitaet['nachname']); ?></td>
                                        <td><?php echo ((int)$aktivitaet['anzahl_bahnen_vormittag'] + (int)$aktivitaet['anzahl_bahnen_nachmittag']); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($aktivitaet['datum'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-muted">Keine Aktivitäten gefunden</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Schnellzugriff -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bolt"></i> Schnellzugriff
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="schwimmer.php?action=add" class="btn btn-primary btn-lg w-100 py-3">
                            <i class="fas fa-plus-circle"></i><br>
                            <small>Neuer Schwimmer</small>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="sponsoren.php?action=add" class="btn btn-success btn-lg w-100 py-3">
                            <i class="fas fa-plus-square"></i><br>
                            <small>Neuer Sponsor</small>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="schwimmleistungen.php?action=add" class="btn btn-info btn-lg w-100 py-3">
                            <i class="fas fa-plus"></i><br>
                            <small>Neue Leistung</small>
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3 mb-3">
                        <a href="auswertung_gesamt.php" class="btn btn-warning btn-lg w-100 py-3">
                            <i class="fas fa-chart-bar"></i><br>
                            <small>Statistiken</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Datenbank-Übersicht -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-database"></i> Datenbank-Übersicht
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-table"></i> Tabellen</h5>
                        <ul class="list-group mb-3">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="schwimmer.php">Schwimmer</a>
                                <span class="badge bg-primary rounded-pill"><?php echo $schwimmer_count ?? '0'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="sponsoren.php">Sponsoren</a>
                                <span class="badge bg-success rounded-pill"><?php echo $sponsoren_count ?? '0'; ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="schwimmleistungen.php">Schwimmleistungen</a>
                                <span class="badge bg-info rounded-pill">
                                    <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM schwimmleistungen");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="schwimmer_sponsoren.php">Schwimmer-Sponsoren</a>
                                <span class="badge bg-warning rounded-pill">
                                    <?php 
                                    $stmt = $pdo->query("SELECT COUNT(*) FROM schwimmer_sponsoren");
                                    echo $stmt->fetchColumn();
                                    ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <a href="hauptsponsoren.php">Hauptsponsoren</a>
                                <span class="badge bg-danger rounded-pill"><?php echo $hauptsponsoren_count ?? '0'; ?></span>
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5><i class="fas fa-chart-pie"></i> Auswertungen</h5>
                        <ul class="list-group">
                            <li class="list-group-item">
                                <a href="auswertung_schwimmer.php">
                                    <i class="fas fa-user-chart"></i> Schwimmer-Statistiken
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="auswertung_sponsoren.php">
                                    <i class="fas fa-chart-line"></i> Sponsoren-Statistiken
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="auswertung_gesamt.php">
                                    <i class="fas fa-chart-pie"></i> Gesamtübersicht
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
