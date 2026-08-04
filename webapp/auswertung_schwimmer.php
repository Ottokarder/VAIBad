<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Daten abrufen
try {
    // Schwimmer mit Gesamtstrecke
    $stmt = $pdo->query("SELECT * FROM v_schwimmer_gesamtstrecke ORDER BY gesamtstrecke_m DESC");
    $schwimmer_strecke = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Schwimmer mit Gesamtspenden
    $stmt = $pdo->query("SELECT * FROM v_schwimmer_gesamtspenden ORDER BY gesamtspenden DESC");
    $schwimmer_spenden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Schwimmer mit Sponsoren-Details
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
    
    // Teams
    $stmt = $pdo->query("SELECT team, 
                         COUNT(*) as schwimmer_count,
                         COALESCE(SUM(anzahl_bahnen), 0) as total_bahnen,
                         COALESCE(SUM(gesamtstrecke_m), 0) as total_strecke
                         FROM v_schwimmer_gesamtstrecke 
                         WHERE team IS NOT NULL AND team != ''
                         GROUP BY team 
                         ORDER BY total_strecke DESC");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Altersgruppen
    $stmt = $pdo->query("SELECT 
                         CASE 
                             WHEN alter < 10 THEN 'Unter 10'
                             WHEN alter BETWEEN 10 AND 13 THEN '10-13 Jahre'
                             WHEN alter BETWEEN 14 AND 17 THEN '14-17 Jahre'
                             WHEN alter >= 18 THEN '18+ Jahre'
                         END as altergruppe,
                         COUNT(*) as schwimmer_count,
                         COALESCE(SUM(anzahl_bahnen), 0) as total_bahnen,
                         COALESCE(SUM(gesamtstrecke_m), 0) as total_strecke
                         FROM v_schwimmer_gesamtstrecke 
                         GROUP BY altergruppe 
                         ORDER BY MIN(alter)");
    $altersgruppen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Fehler beim Abrufen der Daten: " . $e->getMessage();
}

// Aktuelles Jahr
$current_year = date('Y');
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Auswertungen</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-user-chart"></i> Schwimmer-Statistiken</li>
            </ol>
        </nav>
        
        <h1 class="page-header">
            <i class="fas fa-user-chart"></i> Schwimmer-Statistiken
        </h1>
        <p class="text-muted">Detaillierte Auswertungen der Schwimmer-Daten.</p>
    </div>
</div>

<!-- Gesamtstatistiken -->
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
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-icon">
                <i class="fas fa-swimming-pool"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_stats['total_bahnen'], 0, ',', '.'); ?></div>
            <div class="stat-label">Gesamtbahnen</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="stat-icon">
                <i class="fas fa-route"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_stats['total_strecke'], 0, ',', '.'); ?> m</div>
            <div class="stat-label">Gesamtstrecke</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-3 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
            <div class="stat-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-value"><?php echo number_format($gesamt_stats['avg_alter'], 1, ',', '.'); ?> J.</div>
            <div class="stat-label">Durchschnittsalter</div>
        </div>
    </div>
</div>

<!-- Tabs für verschiedene Auswertungen -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="schwimmerTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="strecke-tab" data-bs-toggle="tab" data-bs-target="#strecke" type="button" role="tab">
                    <i class="fas fa-route"></i> Strecken
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="spenden-tab" data-bs-toggle="tab" data-bs-target="#spenden" type="button" role="tab">
                    <i class="fas fa-euro-sign"></i> Spenden
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="teams-tab" data-bs-toggle="tab" data-bs-target="#teams" type="button" role="tab">
                    <i class="fas fa-users"></i> Teams
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="altersgruppen-tab" data-bs-toggle="tab" data-bs-target="#altersgruppen" type="button" role="tab">
                    <i class="fas fa-user-clock"></i> Altersgruppen
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">
                    <i class="fas fa-list"></i> Detaillierte Liste
                </button>
            </li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content" id="schwimmerTabsContent">
            <!-- Strecken-Tab -->
            <div class="tab-pane fade show active" id="strecke" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-route"></i> Schwimmer nach Strecke</h4>
                        <p class="text-muted">Sortiert nach der insgesamt geschwommenen Strecke.</p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Schwimmer</th>
                                <th>Alter</th>
                                <th>Bahnen</th>
                                <th>Bahnlänge</th>
                                <th>Gesamtstrecke</th>
                                <th>Team</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($schwimmer_strecke)): ?>
                                <?php $rank = 1; foreach ($schwimmer_strecke as $s): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?></td>
                                        <td><?php echo $s['alter']; ?></td>
                                        <td><?php echo number_format($s['anzahl_bahnen'], 0, ',', '.'); ?></td>
                                        <td><?php echo $s['bahnenlaenge_m']; ?> m</td>
                                        <td><?php echo number_format($s['gesamtstrecke_m'], 0, ',', '.'); ?> m</td>
                                        <td><?php echo htmlspecialchars($s['team'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Keine Daten verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Spenden-Tab -->
            <div class="tab-pane fade" id="spenden" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-euro-sign"></i> Schwimmer nach Spenden</h4>
                        <p class="text-muted">Sortiert nach den insgesamt gesammelten Spenden.</p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Rang</th>
                                <th>Schwimmer</th>
                                <th>Gesamtspenden</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($schwimmer_spenden)): ?>
                                <?php $rank = 1; foreach ($schwimmer_spenden as $s): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?></td>
                                        <td><?php echo number_format($s['gesamtspenden'], 2, ',', '.'); ?> €</td>
                                        <td>
                                            <a href="schwimmer_sponsoren.php?schwimmer_id=<?php echo $s['id']; ?>" 
                                               class="btn btn-sm btn-info action-btn" 
                                               data-bs-toggle="tooltip" title="Sponsoren anzeigen">
                                                <i class="fas fa-link"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">Keine Daten verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Teams-Tab -->
            <div class="tab-pane fade" id="teams" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-users"></i> Teams</h4>
                        <p class="text-muted">Statistiken nach Teams.</p>
                    </div>
                </div>
                
                <?php if (!empty($teams)): ?>
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
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Keine Team-Daten verfügbar</div>
                <?php endif; ?>
            </div>
            
            <!-- Altersgruppen-Tab -->
            <div class="tab-pane fade" id="altersgruppen" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-user-clock"></i> Altersgruppen</h4>
                        <p class="text-muted">Statistiken nach Altersgruppen.</p>
                    </div>
                </div>
                
                <?php if (!empty($altersgruppen)): ?>
                    <div class="row">
                        <?php foreach ($altersgruppen as $gruppe): ?>
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($gruppe['altergruppe']); ?></h5>
                                        <p class="card-text">
                                            <strong>Schwimmer:</strong> <?php echo $gruppe['schwimmer_count']; ?><br>
                                            <strong>Gesamtbahnen:</strong> <?php echo number_format($gruppe['total_bahnen'], 0, ',', '.'); ?><br>
                                            <strong>Gesamtstrecke:</strong> <?php echo number_format($gruppe['total_strecke'], 0, ',', '.'); ?> m
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">Keine Altersgruppen-Daten verfügbar</div>
                <?php endif; ?>
            </div>
            
            <!-- Detaillierte Liste-Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-list"></i> Detaillierte Spenden pro Schwimmer und Sponsor</h4>
                        <p class="text-muted">Alle Spendenbeziehungen im Detail.</p>
                    </div>
                </div>
                
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
