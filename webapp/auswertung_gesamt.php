<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Daten abrufen
try {
    // Gesamtspenden der Hauptsponsoren
    $stmt = $pdo->query("SELECT * FROM v_gesamtspenden_hauptsponsoren");
    $gesamtspenden_hs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Hauptsponsoren-Spenden
    $stmt = $pdo->query("SELECT * FROM v_hauptsponsor_spenden ORDER BY spendenbetrag DESC");
    $hauptsponsor_spenden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Schwimmer mit Gesamtstrecke
    $stmt = $pdo->query("SELECT * FROM v_schwimmer_gesamtstrecke ORDER BY gesamtstrecke_m DESC");
    $schwimmer_strecke = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Schwimmer mit Gesamtspenden
    $stmt = $pdo->query("SELECT * FROM v_schwimmer_gesamtspenden ORDER BY gesamtspenden DESC");
    $schwimmer_spenden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Schwimmer-Sponsor-Spenden
    $stmt = $pdo->query("SELECT * FROM v_schwimmer_sponsor_spenden ORDER BY spendenbetrag DESC");
    $schwimmer_sponsor_spenden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gesamtstatistiken
    $stmt = $pdo->query("SELECT 
                         COUNT(*) as schwimmer_count,
                         COALESCE(SUM(anzahl_bahnen), 0) as total_bahnen,
                         COALESCE(SUM(gesamtstrecke_m), 0) as total_strecke,
                         COALESCE(AVG(alter), 0) as avg_alter
                         FROM v_schwimmer_gesamtstrecke");
    $gesamt_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Sponsoren-Statistiken
    $stmt = $pdo->query("SELECT 
                         COUNT(*) as sponsoren_count,
                         SUM(ist_hauptsponsor) as hauptsponsoren_count
                         FROM sponsoren");
    $sponsoren_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Gesamtspenden aller Schwimmer-Sponsoren
    $stmt = $pdo->query("SELECT COALESCE(SUM(spendenbetrag), 0) FROM v_schwimmer_sponsor_spenden");
    $gesamtspenden_ss = $stmt->fetchColumn();
    
    // Gesamtspenden aller Schwimmer
    $stmt = $pdo->query("SELECT COALESCE(SUM(gesamtspenden), 0) FROM v_schwimmer_gesamtspenden");
    $gesamtspenden_schwimmer = $stmt->fetchColumn();
    
    // Teams
    $stmt = $pdo->query("SELECT team, 
                         COUNT(*) as schwimmer_count,
                         COALESCE(SUM(anzahl_bahnen), 0) as total_bahnen,
                         COALESCE(SUM(gesamtstrecke_m), 0) as total_strecke
                         FROM v_schwimmer_gesamtstrecke 
                         WHERE team IS NOT NULL AND team != ''
                         GROUP BY team");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Fehler beim Abrufen der Daten: " . $e->getMessage();
}

// Berechnungen
$gesamtspenden_gesamt = ($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0) + $gesamtspenden_ss;
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Auswertungen</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-chart-pie"></i> Gesamtübersicht</li>
            </ol>
        </nav>
        
        <h1 class="page-header">
            <i class="fas fa-chart-pie"></i> Gesamtübersicht
        </h1>
        <p class="text-muted">Komplette Übersicht aller Daten und Statistiken.</p>
    </div>
</div>

<!-- Hauptstatistiken -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-value"><?php echo $gesamt_stats['schwimmer_count']; ?></div>
            <div class="stat-label">Schwimmer</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
            <div class="stat-icon">
                <i class="fas fa-hand-holding-heart"></i>
            </div>
            <div class="stat-value"><?php echo $sponsoren_stats['sponsoren_count']; ?></div>
            <div class="stat-label">Sponsoren</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value"><?php echo $sponsoren_stats['hauptsponsoren_count']; ?></div>
            <div class="stat-label">Hauptsponsoren</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="stat-icon">
                <i class="fas fa-swimming-pool"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_stats['total_bahnen'], 0, ',', '.'); ?></div>
            <div class="stat-label">Gesamtbahnen</div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="stat-icon">
                <i class="fas fa-route"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_stats['total_strecke'], 0, ',', '.'); ?> m</div>
            <div class="stat-label">Gesamtstrecke</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
            <div class="stat-icon">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamtspenden_gesamt, 2, ',', '.'); ?> €</div>
            <div class="stat-label">Gesamtspenden</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="stat-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_stats['avg_alter'], 1, ',', '.'); ?> J.</div>
            <div class="stat-label">Durchschnittsalter</div>
        </div>
    </div>
</div>

<!-- Spendenaufschlüsselung -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-chart-bar"></i> Spendenaufschlüsselung
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h5><i class="fas fa-star text-success"></i> Hauptsponsoren</h5>
                        <div class="alert alert-success">
                            <strong>Gesamtspenden:</strong> <?php echo number_format($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0, 2, ',', '.'); ?> €
                        </div>
                        
                        <?php if (!empty($hauptsponsor_spenden)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Sponsor</th>
                                            <th>Betrag/Bahn</th>
                                            <th>Limit</th>
                                            <th>Spendenbetrag</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($hauptsponsor_spenden as $h): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($h['sponsor_name']); ?></td>
                                                <td><?php echo number_format($h['betrag_pro_bahn'], 2, ',', '.'); ?> €</td>
                                                <td><?php echo $h['limit_betrag'] ? number_format($h['limit_betrag'], 2, ',', '.') . ' €' : '-'; ?></td>
                                                <td><?php echo number_format($h['spendenbetrag'], 2, ',', '.'); ?> €</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3">Gesamt</th>
                                            <th><?php echo number_format($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0, 2, ',', '.'); ?> €</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-6">
                        <h5><i class="fas fa-hand-holding-heart text-primary"></i> Schwimmer-Sponsoren</h5>
                        <div class="alert alert-primary">
                            <strong>Gesamtspenden:</strong> <?php echo number_format($gesamtspenden_ss, 2, ',', '.'); ?> €
                        </div>
                        
                        <div class="alert alert-info">
                            <strong>Gesamtspenden (Schwimmer):</strong> <?php echo number_format($gesamtspenden_schwimmer, 2, ',', '.'); ?> €
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h5><i class="fas fa-chart-pie"></i> Spendenverteilung</h5>
                        <div class="progress mb-3">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $gesamtspenden_hs['gesamtspenden_hauptsponsoren'] > 0 ? min(100, (($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0) / max($gesamtspenden_gesamt, 1)) * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo $gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $gesamtspenden_gesamt; ?>">
                                Hauptsponsoren: <?php echo number_format($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0, 2, ',', '.'); ?> €
                            </div>
                            <div class="progress-bar bg-primary" role="progressbar" 
                                 style="width: <?php echo $gesamtspenden_ss > 0 ? min(100, (($gesamtspenden_ss ?? 0) / max($gesamtspenden_gesamt, 1)) * 100) : 0; ?>%" 
                                 aria-valuenow="<?php echo $gesamtspenden_ss; ?>" 
                                 aria-valuemin="0" aria-valuemax="<?php echo $gesamtspenden_gesamt; ?>">
                                Schwimmer-Sponsoren: <?php echo number_format($gesamtspenden_ss, 2, ',', '.'); ?> €
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top-Listen -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy"></i> Top Schwimmer nach Strecke
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Schwimmer</th>
                                <th>Alter</th>
                                <th>Gesamtstrecke</th>
                                <th>Team</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($schwimmer_strecke)): ?>
                                <?php $rank = 1; foreach (array_slice($schwimmer_strecke, 0, 10) as $s): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?></td>
                                        <td><?php echo $s['alter']; ?></td>
                                        <td><?php echo number_format($s['gesamtstrecke_m'], 0, ',', '.'); ?> m</td>
                                        <td><?php echo htmlspecialchars($s['team'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">Keine Daten verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-trophy"></i> Top Schwimmer nach Spenden
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Schwimmer</th>
                                <th>Gesamtspenden</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($schwimmer_spenden)): ?>
                                <?php $rank = 1; foreach (array_slice($schwimmer_spenden, 0, 10) as $s): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?></td>
                                        <td><?php echo number_format($s['gesamtspenden'], 2, ',', '.'); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Keine Daten verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Teams -->
<?php if (!empty($teams)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-users"></i> Teams
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($teams as $team): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($team['team']); ?></h5>
                                        <p class="card-text">
                                            <strong>Schwimmer:</strong> <?php echo $team['schwimmer_count']; ?><br>
                                            <strong>Gesamtbahnen:</strong> <?php echo number_format($team['total_bahnen'], 0, ',', '.'); ?><br>
                                            <strong>Gesamtstrecke:</strong> <?php echo number_format($team['total_strecke'], 0, ',', '.'); ?> m
                                        </p>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?php echo min(100, ($team['total_strecke'] / max($gesamt_stats['total_strecke'], 1)) * 100); ?>%" 
                                                 aria-valuenow="<?php echo $team['total_strecke']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="<?php echo $gesamt_stats['total_strecke']; ?>">
                                                <?php echo number_format(min(100, ($team['total_strecke'] / max($gesamt_stats['total_strecke'], 1)) * 100), 1, ',', '.'); ?>%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Detaillierte Spendenliste -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Detaillierte Spendenliste
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Schwimmer</th>
                                <th>Sponsor</th>
                                <th>Betrag/Bahn</th>
                                <th>Limit</th>
                                <th>Bahnen</th>
                                <th>Bahnlänge</th>
                                <th>Spendenbetrag (roh)</th>
                                <th>Spendenbetrag (effektiv)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($schwimmer_sponsor_spenden)): ?>
                                <?php foreach ($schwimmer_sponsor_spenden as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['schwimmer_vorname'] . ' ' . $s['schwimmer_nachname']); ?></td>
                                        <td><?php echo htmlspecialchars($s['sponsor_name']); ?></td>
                                        <td><?php echo number_format($s['betrag_pro_bahn'], 2, ',', '.'); ?> €</td>
                                        <td><?php echo $s['limit_betrag'] ? number_format($s['limit_betrag'], 2, ',', '.') . ' €' : '-'; ?></td>
                                        <td><?php echo number_format($s['anzahl_bahnen'], 0, ',', '.'); ?></td>
                                        <td><?php echo $s['bahnenlaenge_m']; ?> m</td>
                                        <td><?php echo number_format($s['spendenbetrag_roh'], 2, ',', '.'); ?> €</td>
                                        <td><?php echo number_format($s['spendenbetrag'], 2, ',', '.'); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Keine Daten verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="6">Gesamt</th>
                                <th>
                                    <?php 
                                    $total_roh = array_sum(array_column($schwimmer_sponsor_spenden, 'spendenbetrag_roh'));
                                    echo number_format($total_roh, 2, ',', '.'); ?> €
                                </th>
                                <th>
                                    <?php 
                                    $total_effektiv = array_sum(array_column($schwimmer_sponsor_spenden, 'spendenbetrag'));
                                    echo number_format($total_effektiv, 2, ',', '.'); ?> €
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$additional_scripts = [
    $base_url . 'js/main.js'
];
require_once __DIR__ . '/includes/footer.php';
