<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Daten abrufen
try {
    // Hauptsponsoren-Spenden
    $stmt = $pdo->query("SELECT * FROM v_hauptsponsor_spenden ORDER BY spendenbetrag DESC");
    $hauptsponsor_spenden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Schwimmer-Sponsor-Spenden
    $stmt = $pdo->query("SELECT * FROM v_schwimmer_sponsor_spenden ORDER BY spendenbetrag DESC");
    $schwimmer_sponsor_spenden = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gesamtspenden der Hauptsponsoren
    $stmt = $pdo->query("SELECT * FROM v_gesamtspenden_hauptsponsoren");
    $gesamtspenden_hs = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Sponsoren-Statistiken
    $stmt = $pdo->query("SELECT 
                         sp.id, sp.name, sp.ist_hauptsponsor,
                         COUNT(DISTINCT ss.schwimmer_id) as schwimmer_count,
                         COALESCE(SUM(ss.betrag_pro_bahn), 0) as total_betrag_pro_bahn,
                         COALESCE(AVG(ss.betrag_pro_bahn), 0) as avg_betrag_pro_bahn,
                         COALESCE(SUM(LEAST((sl.anzahl_bahnen_vormittag + sl.anzahl_bahnen_nachmittag) * ss.betrag_pro_bahn, IFNULL(ss.limit_betrag, (sl.anzahl_bahnen_vormittag + sl.anzahl_bahnen_nachmittag) * ss.betrag_pro_bahn))), 0) as total_spenden
                         FROM sponsoren sp
                         LEFT JOIN schwimmer_sponsoren ss ON sp.id = ss.sponsor_id
                         LEFT JOIN schwimmleistungen sl ON ss.schwimmer_id = sl.schwimmer_id
                         GROUP BY sp.id, sp.name, sp.ist_hauptsponsor
                         ORDER BY total_spenden DESC");
    $sponsoren_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Gesamtbahnen
    $stmt = $pdo->query("SELECT COALESCE(SUM(anzahl_bahnen_vormittag), 0) + COALESCE(SUM(anzahl_bahnen_nachmittag), 0) FROM schwimmleistungen");
    $gesamt_bahnen = $stmt->fetchColumn();
    
    // Hauptsponsoren vs. normale Sponsoren
    $stmt = $pdo->query("SELECT 
                         ist_hauptsponsor,
                         COUNT(*) as count,
                         COALESCE(SUM(total_spenden), 0) as total_spenden
                         FROM (
                             SELECT sp.ist_hauptsponsor, 
                                    COALESCE(SUM(LEAST((sl.anzahl_bahnen_vormittag + sl.anzahl_bahnen_nachmittag) * ss.betrag_pro_bahn, IFNULL(ss.limit_betrag, (sl.anzahl_bahnen_vormittag + sl.anzahl_bahnen_nachmittag) * ss.betrag_pro_bahn))), 0) as total_spenden
                             FROM sponsoren sp
                             LEFT JOIN schwimmer_sponsoren ss ON sp.id = ss.sponsor_id
                             LEFT JOIN schwimmleistungen sl ON ss.schwimmer_id = sl.schwimmer_id
                             GROUP BY sp.id, sp.ist_hauptsponsor
                         ) as subquery
                         GROUP BY ist_hauptsponsor");
    $sponsor_typen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Fehler beim Abrufen der Daten: " . $e->getMessage();
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item"><a href="#">Auswertungen</a></li>
                <li class="breadcrumb-item active"><i class="fas fa-chart-line"></i> Sponsoren-Statistiken</li>
            </ol>
        </nav>
        
        <h1 class="page-header">
            <i class="fas fa-chart-line"></i> Sponsoren-Statistiken
        </h1>
        <p class="text-muted">Detaillierte Auswertungen der Sponsoren-Daten.</p>
    </div>
</div>

<!-- Gesamtstatistiken -->
<div class="row mb-4">
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-hand-holding-heart"></i>
            </div>
            <div class="stat-value">
                <?php 
                $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren");
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Sponsoren</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
            <div class="stat-icon">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-value">
                <?php 
                $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren WHERE ist_hauptsponsor = 1");
                echo $stmt->fetchColumn();
                ?>
            </div>
            <div class="stat-label">Hauptsponsoren</div>
        </div>
    </div>
    
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
            <div class="stat-icon">
                <i class="fas fa-euro-sign"></i>
            </div>
            <div class="stat-value">
                <?php echo number_format($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0, 2, ',', '.'); ?> €
            </div>
            <div class="stat-label">Gesamtspenden Hauptsponsoren</div>
        </div>
    </div>
</div>

<!-- Tabs für verschiedene Auswertungen -->
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="sponsorenTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="uebersicht-tab" data-bs-toggle="tab" data-bs-target="#uebersicht" type="button" role="tab">
                    <i class="fas fa-chart-pie"></i> Übersicht
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="hauptsponsoren-tab" data-bs-toggle="tab" data-bs-target="#hauptsponsoren" type="button" role="tab">
                    <i class="fas fa-star"></i> Hauptsponsoren
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="normale-tab" data-bs-toggle="tab" data-bs-target="#normale" type="button" role="tab">
                    <i class="fas fa-hand-holding-heart"></i> Normale Sponsoren
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="vergleich-tab" data-bs-toggle="tab" data-bs-target="#vergleich" type="button" role="tab">
                    <i class="fas fa-balance-scale"></i> Vergleich
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
        <div class="tab-content" id="sponsorenTabsContent">
            <!-- Übersicht-Tab -->
            <div class="tab-pane fade show active" id="uebersicht" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-chart-pie"></i> Sponsoren-Übersicht</h4>
                        <p class="text-muted">Gesamtstatistiken aller Sponsoren.</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-bar"></i> Sponsoren nach Spenden
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Sponsor</th>
                                                <th>Typ</th>
                                                <th>Schwimmer</th>
                                                <th>Gesamtspenden</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($sponsoren_stats)): ?>
                                                <?php foreach ($sponsoren_stats as $s): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($s['name']); ?></td>
                                                        <td>
                                                            <?php if ($s['ist_hauptsponsor']): ?>
                                                                <span class="badge bg-success">Hauptsponsor</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Normal</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo $s['schwimmer_count']; ?></td>
                                                        <td><?php echo number_format($s['total_spenden'], 2, ',', '.'); ?> €</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">Keine Daten verfügbar</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3">Gesamt</th>
                                                <th>
                                                    <?php 
                                                    $total = array_sum(array_column($sponsoren_stats, 'total_spenden'));
                                                    echo number_format($total, 2, ',', '.'); ?> €
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-chart-pie"></i> Sponsoren-Typen
                            </div>
                            <div class="card-body">
                                <?php if (!empty($sponsor_typen)): ?>
                                    <div class="row">
                                        <?php foreach ($sponsor_typen as $typ): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h5 class="card-title">
                                                            <?php echo $typ['ist_hauptsponsor'] ? 'Hauptsponsoren' : 'Normale Sponsoren'; ?>
                                                        </h5>
                                                        <p class="card-text">
                                                            <strong>Anzahl:</strong> <?php echo $typ['count']; ?><br>
                                                            <strong>Gesamtspenden:</strong> <?php echo number_format($typ['total_spenden'], 2, ',', '.'); ?> €
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">Keine Daten verfügbar</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hauptsponsoren-Tab -->
            <div class="tab-pane fade" id="hauptsponsoren" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-star"></i> Hauptsponsoren-Details</h4>
                        <p class="text-muted">Detaillierte Informationen zu den Hauptsponsoren.</p>
                    </div>
                </div>
                
                <div class="alert alert-info mb-4">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Gesamtspenden aller Hauptsponsoren:</strong> 
                    <?php echo number_format($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0, 2, ',', '.'); ?> €
                    <br>
                    <small>Basierend auf <?php echo $gesamt_bahnen; ?> Gesamtbahnen aller Schwimmer</small>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sponsor</th>
                                <th>Betrag/Bahn</th>
                                <th>Limit</th>
                                <th>Gesamtbahnen</th>
                                <th>Spendenbetrag (roh)</th>
                                <th>Spendenbetrag (effektiv)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($hauptsponsor_spenden)): ?>
                                <?php foreach ($hauptsponsor_spenden as $h): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($h['id']); ?></td>
                                        <td><?php echo htmlspecialchars($h['sponsor_name']); ?></td>
                                        <td><?php echo number_format($h['betrag_pro_bahn'], 2, ',', '.'); ?> €</td>
                                        <td><?php echo $h['limit_betrag'] ? number_format($h['limit_betrag'], 2, ',', '.') . ' €' : '-'; ?></td>
                                        <td><?php echo number_format($h['gesamt_bahnen'], 0, ',', '.'); ?></td>
                                        <td><?php echo number_format($h['spendenbetrag_roh'], 2, ',', '.'); ?> €</td>
                                        <td><?php echo number_format($h['spendenbetrag'], 2, ',', '.'); ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">Keine Hauptsponsoren-Daten verfügbar</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="5">Gesamt</th>
                                <th><?php echo number_format($h['spendenbetrag_roh'] ?? 0, 2, ',', '.'); ?> €</th>
                                <th><?php echo number_format($gesamtspenden_hs['gesamtspenden_hauptsponsoren'] ?? 0, 2, ',', '.'); ?> €</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Normale Sponsoren-Tab -->
            <div class="tab-pane fade" id="normale" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-hand-holding-heart"></i> Normale Sponsoren-Details</h4>
                        <p class="text-muted">Detaillierte Informationen zu den normalen Sponsoren.</p>
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
                            <?php 
                            $normale_spenden = array_filter($schwimmer_sponsor_spenden, function($s) {
                                $stmt = $pdo->prepare("SELECT ist_hauptsponsor FROM sponsoren WHERE id = (SELECT id FROM sponsoren WHERE name = ?)");
                                $stmt->execute([$s['sponsor_name']]);
                                $is_hs = $stmt->fetchColumn();
                                return !$is_hs;
                            });
                            
                            if (!empty($normale_spenden)): 
                                foreach ($normale_spenden as $s): 
                            ?>
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
                            <?php 
                                endforeach;
                            else:
                            ?>
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
                                    $total_roh = array_sum(array_column($normale_spenden, 'spendenbetrag_roh'));
                                    echo number_format($total_roh, 2, ',', '.'); ?> €
                                </th>
                                <th>
                                    <?php 
                                    $total_effektiv = array_sum(array_column($normale_spenden, 'spendenbetrag'));
                                    echo number_format($total_effektiv, 2, ',', '.'); ?> €
                                </th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <!-- Vergleich-Tab -->
            <div class="tab-pane fade" id="vergleich" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-balance-scale"></i> Vergleich: Hauptsponsoren vs. Normale Sponsoren</h4>
                        <p class="text-muted">Vergleich der Spendenleistungen.</p>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header bg-success text-white">
                                <i class="fas fa-star"></i> Hauptsponsoren
                            </div>
                            <div class="card-body">
                                <?php 
                                $hs_count = 0;
                                $hs_total = 0;
                                foreach ($sponsor_typen as $typ) {
                                    if ($typ['ist_hauptsponsor']) {
                                        $hs_count = $typ['count'];
                                        $hs_total = $typ['total_spenden'];
                                    }
                                }
                                ?>
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="stat-card" style="background: rgba(13, 110, 253, 0.1);">
                                            <div class="stat-value" style="color: #0d6efd;"><?php echo $hs_count; ?></div>
                                            <div class="stat-label">Anzahl Sponsoren</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="stat-card" style="background: rgba(13, 110, 253, 0.1);">
                                            <div class="stat-value" style="color: #0d6efd;"><?php echo number_format($hs_total, 2, ',', '.'); ?> €</div>
                                            <div class="stat-label">Gesamtspenden</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> 
                                    Hauptsponsoren spenden pro Bahn aller Schwimmer.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-6">
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-hand-holding-heart"></i> Normale Sponsoren
                            </div>
                            <div class="card-body">
                                <?php 
                                $normale_count = 0;
                                $normale_total = 0;
                                foreach ($sponsor_typen as $typ) {
                                    if (!$typ['ist_hauptsponsor']) {
                                        $normale_count = $typ['count'];
                                        $normale_total = $typ['total_spenden'];
                                    }
                                }
                                ?>
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="stat-card" style="background: rgba(25, 135, 84, 0.1);">
                                            <div class="stat-value" style="color: #198754;"><?php echo $normale_count; ?></div>
                                            <div class="stat-label">Anzahl Sponsoren</div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="stat-card" style="background: rgba(25, 135, 84, 0.1);">
                                            <div class="stat-value" style="color: #198754;"><?php echo number_format($normale_total, 2, ',', '.'); ?> €</div>
                                            <div class="stat-label">Gesamtspenden</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-primary">
                                    <i class="fas fa-info-circle"></i> 
                                    Normale Sponsoren spenden pro Bahn der verknüpften Schwimmer.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar"></i> Vergleichsdiagramm
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Anzahl Sponsoren</h5>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $hs_count > 0 ? min(100, ($hs_count / max($hs_count + $normale_count, 1)) * 100) : 0; ?>%" 
                                         aria-valuenow="<?php echo $hs_count; ?>" aria-valuemin="0" aria-valuemax="<?php echo $hs_count + $normale_count; ?>">
                                        Hauptsponsoren: <?php echo $hs_count; ?>
                                    </div>
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $normale_count > 0 ? min(100, ($normale_count / max($hs_count + $normale_count, 1)) * 100) : 0; ?>%" 
                                         aria-valuenow="<?php echo $normale_count; ?>" aria-valuemin="0" aria-valuemax="<?php echo $hs_count + $normale_count; ?>">
                                        Normale: <?php echo $normale_count; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Gesamtspenden</h5>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $hs_total > 0 ? min(100, ($hs_total / max($hs_total + $normale_total, 1)) * 100) : 0; ?>%" 
                                         aria-valuenow="<?php echo $hs_total; ?>" aria-valuemin="0" aria-valuemax="<?php echo $hs_total + $normale_total; ?>">
                                        Hauptsponsoren: <?php echo number_format($hs_total, 2, ',', '.'); ?> €
                                    </div>
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $normale_total > 0 ? min(100, ($normale_total / max($hs_total + $normale_total, 1)) * 100) : 0; ?>%" 
                                         aria-valuenow="<?php echo $normale_total; ?>" aria-valuemin="0" aria-valuemax="<?php echo $hs_total + $normale_total; ?>">
                                        Normale: <?php echo number_format($normale_total, 2, ',', '.'); ?> €
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Detaillierte Liste-Tab -->
            <div class="tab-pane fade" id="details" role="tabpanel">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4><i class="fas fa-list"></i> Alle Sponsoren-Spenden im Detail</h4>
                        <p class="text-muted">Komplette Übersicht aller Spendenbeziehungen.</p>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover data-table">
                        <thead>
                            <tr>
                                <th>Schwimmer</th>
                                <th>Sponsor</th>
                                <th>Typ</th>
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
                                    <?php 
                                    $stmt = $pdo->prepare("SELECT ist_hauptsponsor FROM sponsoren WHERE name = ?");
                                    $stmt->execute([$s['sponsor_name']]);
                                    $is_hs = $stmt->fetchColumn();
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['schwimmer_vorname'] . ' ' . $s['schwimmer_nachname']); ?></td>
                                        <td><?php echo htmlspecialchars($s['sponsor_name']); ?></td>
                                        <td>
                                            <?php if ($is_hs): ?>
                                                <span class="badge bg-success">Hauptsponsor</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Normal</span>
                                            <?php endif; ?>
                                        </td>
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
                                    <td colspan="9" class="text-center text-muted">Keine Daten verfügbar</td>
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
