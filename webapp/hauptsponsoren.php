<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Aktionen verarbeiten
$action = $_GET['action'] ?? 'list';
$message = '';
$message_type = 'info';

// Löschen
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Überprüfen, ob der Hauptsponsor existiert
        $stmt = $pdo->prepare("SELECT * FROM hauptsponsoren WHERE id = ?");
        $stmt->execute([$id]);
        $hs = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($hs) {
            // Hauptsponsor löschen
            $stmt = $pdo->prepare("DELETE FROM hauptsponsoren WHERE id = ?");
            $stmt->execute([$id]);
            
            // Sponsor zurücksetzen
            $stmt = $pdo->prepare("UPDATE sponsoren SET ist_hauptsponsor = 0 WHERE id = ?");
            $stmt->execute([$hs['sponsor_id']]);
            
            $message = "Hauptsponsor erfolgreich gelöscht.";
            $message_type = "success";
        } else {
            $message = "Hauptsponsor nicht gefunden.";
            $message_type = "warning";
        }
    } catch (PDOException $e) {
        $message = "Fehler beim Löschen: " . $e->getMessage();
        $message_type = "danger";
    }
    
    $action = 'list';
}

// Speichern (Hinzufügen oder Bearbeiten)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : null;
        $sponsor_id = (int)$_POST['sponsor_id'];
        $betrag_pro_bahn = (float)str_replace(',', '.', $_POST['betrag_pro_bahn']);
        $limit_betrag = $_POST['limit_betrag'] !== '' ? (float)str_replace(',', '.', $_POST['limit_betrag']) : null;
        
        // Überprüfen, ob der Sponsor existiert
        $stmt = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
        $stmt->execute([$sponsor_id]);
        $sponsor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sponsor) {
            throw new Exception("Sponsor nicht gefunden");
        }
        
        if ($id) {
            // Aktualisieren
            $stmt = $pdo->prepare("UPDATE hauptsponsoren SET sponsor_id = ?, betrag_pro_bahn = ?, limit_betrag = ? WHERE id = ?");
            $stmt->execute([$sponsor_id, $betrag_pro_bahn, $limit_betrag, $id]);
            $message = "Hauptsponsor erfolgreich aktualisiert.";
            $message_type = "success";
        } else {
            // Neu erstellen
            $stmt = $pdo->prepare("INSERT INTO hauptsponsoren (sponsor_id, betrag_pro_bahn, limit_betrag) VALUES (?, ?, ?)");
            $stmt->execute([$sponsor_id, $betrag_pro_bahn, $limit_betrag]);
            $id = $pdo->lastInsertId();
            
            // Sponsor als Hauptsponsor markieren
            $stmt = $pdo->prepare("UPDATE sponsoren SET ist_hauptsponsor = 1 WHERE id = ?");
            $stmt->execute([$sponsor_id]);
            
            $message = "Hauptsponsor erfolgreich hinzugefügt.";
            $message_type = "success";
        }
        
        // Weiterleitung
        if (isset($_POST['save_and_continue'])) {
            header("Location: hauptsponsoren.php?action=edit&id=$id");
            exit();
        } else {
            header("Location: hauptsponsoren.php");
            exit();
        }
    } catch (PDOException $e) {
        $message = "Fehler beim Speichern: " . $e->getMessage();
        $message_type = "danger";
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = "danger";
    }
}

// Daten für Bearbeitung abrufen
$hs = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM hauptsponsoren WHERE id = ?");
        $stmt->execute([$id]);
        $hs = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Daten: " . $e->getMessage();
        $message_type = "danger";
        $action = 'list';
    }
}

// Alle Hauptsponsoren für die Liste abrufen
$alle_hs = [];
if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT hs.*, sp.name as sponsor_name, sp.ist_hauptsponsor
                             FROM hauptsponsoren hs 
                             JOIN sponsoren sp ON hs.sponsor_id = sp.id 
                             ORDER BY sp.name");
        $alle_hs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Hauptsponsoren: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Sponsoren für Dropdown abrufen (nur Hauptsponsoren oder alle)
$sponsoren_list = [];
try {
    $stmt = $pdo->query("SELECT id, name, ist_hauptsponsor FROM sponsoren ORDER BY ist_hauptsponsor DESC, name");
    $sponsoren_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Fehler beim Laden der Sponsoren: " . $e->getMessage();
    $message_type = "danger";
}

// Aktueller Sponsor für Filter
$current_sponsor = null;
if (isset($_GET['sponsor_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
        $stmt->execute([$_GET['sponsor_id']]);
        $current_sponsor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorieren
    }
}

// Gesamtspenden der Hauptsponsoren berechnen
$gesamtspenden_hs = 0;
try {
    $stmt = $pdo->query("SELECT COALESCE(SUM(spendenbetrag), 0) FROM v_hauptsponsor_spenden");
    $gesamtspenden_hs = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Ignorieren
}

// Gesamtbahnen aller Schwimmer
$gesamt_bahnen = 0;
try {
    $stmt = $pdo->query("SELECT COALESCE(SUM(anzahl_bahnen), 0) FROM schwimmleistungen");
    $gesamt_bahnen = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Ignorieren
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><i class="fas fa-star"></i> Hauptsponsoren</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-header m-0">
                <i class="fas fa-star"></i> Hauptsponsoren
            </h1>
            <a href="hauptsponsoren.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neuer Hauptsponsor
            </a>
        </div>
        
        <p class="text-muted">Verwalten Sie hier die Hauptsponsoren und deren Spendenregelungen.</p>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Liste aller Hauptsponsoren -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Hauptsponsoren-Liste
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Gesamtspenden aller Hauptsponsoren:</strong> 
                        <?php echo number_format($gesamtspenden_hs, 2, ',', '.'); ?> €
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
                                    <th>Gesamtspende</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($alle_hs)): ?>
                                    <?php foreach ($alle_hs as $h): ?>
                                        <?php 
                                        // Gesamtspende für diesen Hauptsponsor berechnen
                                        $spendenbetrag_roh = $gesamt_bahnen * $h['betrag_pro_bahn'];
                                        $spendenbetrag = $h['limit_betrag'] ? min($spendenbetrag_roh, $h['limit_betrag']) : $spendenbetrag_roh;
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($h['id']); ?></td>
                                            <td><?php echo htmlspecialchars($h['sponsor_name']); ?></td>
                                            <td><?php echo number_format($h['betrag_pro_bahn'], 2, ',', '.'); ?> €</td>
                                            <td><?php echo $h['limit_betrag'] ? number_format($h['limit_betrag'], 2, ',', '.') . ' €' : '-'; ?></td>
                                            <td><?php echo number_format($spendenbetrag, 2, ',', '.'); ?> €</td>
                                            <td>
                                                <a href="hauptsponsoren.php?action=edit&id=<?php echo $h['id']; ?>" 
                                                   class="btn btn-sm btn-warning action-btn" 
                                                   data-bs-toggle="tooltip" title="Bearbeiten">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="sponsoren.php?action=edit&id=<?php echo $h['sponsor_id']; ?>" 
                                                   class="btn btn-sm btn-info action-btn" 
                                                   data-bs-toggle="tooltip" title="Sponsor bearbeiten">
                                                    <i class="fas fa-user-edit"></i>
                                                </a>
                                                <a href="hauptsponsoren.php?action=delete&id=<?php echo $h['id']; ?>" 
                                                   class="btn btn-sm btn-danger action-btn confirm-delete" 
                                                   data-confirm-message="Möchten Sie diesen Hauptsponsor wirklich löschen? Der Sponsor bleibt als normaler Sponsor erhalten."
                                                   data-bs-toggle="tooltip" title="Löschen">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Keine Hauptsponsoren gefunden</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4">Gesamt</th>
                                    <th><?php echo number_format($gesamtspenden_hs, 2, ',', '.'); ?> €</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Statistik
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Hauptsponsoren</h5>
                        <p class="text-muted">
                            <?php echo count($alle_hs); ?> Hauptsponsoren
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5>Gesamtbahnen</h5>
                        <p class="text-muted">
                            <?php echo $gesamt_bahnen; ?> Bahnen
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5>Durchschnitt pro Bahn</h5>
                        <p class="text-muted">
                            <?php 
                            $avg_betrag = count($alle_hs) > 0 ? array_sum(array_column($alle_hs, 'betrag_pro_bahn')) / count($alle_hs) : 0;
                            echo number_format($avg_betrag, 2, ',', '.'); ?> €
                        </p>
                    </div>
                    <div>
                        <h5>Gesamtspenden</h5>
                        <p class="text-muted">
                            <?php echo number_format($gesamtspenden_hs, 2, ',', '.'); ?> €
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php elseif ($action === 'add' || $action === 'edit'): ?>
    <!-- Formular zum Bearbeiten/Hinzufügen -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?>"></i> 
                    <?php echo $action === 'add' ? 'Neuer Hauptsponsor' : 'Hauptsponsor bearbeiten'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <?php if ($hs): ?>
                            <input type="hidden" name="id" value="<?php echo $hs['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="sponsor_id" class="form-label">Sponsor *</label>
                            <select class="form-select" id="sponsor_id" name="sponsor_id" required>
                                <option value="">-- Sponsor auswählen --</option>
                                <?php foreach ($sponsoren_list as $sp): ?>
                                    <option value="<?php echo $sp['id']; ?>" 
                                        <?php echo ($hs['sponsor_id'] ?? '') == $sp['id'] ? 'selected' : ''; ?>
                                        <?php echo $sp['ist_hauptsponsor'] ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($sp['name']); ?>
                                        <?php if ($sp['ist_hauptsponsor']): ?>
                                            <small class="text-muted">(bereits Hauptsponsor)</small>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Bitte wählen Sie einen Sponsor aus.</div>
                            <small class="form-text text-muted">
                                Nur Sponsoren, die noch nicht als Hauptsponsor markiert sind, können ausgewählt werden.
                            </small>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="betrag_pro_bahn" class="form-label">Betrag pro Bahn (€) *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control validate-decimal" id="betrag_pro_bahn" name="betrag_pro_bahn" 
                                           value="<?php echo htmlspecialchars(str_replace('.', ',', $hs['betrag_pro_bahn'] ?? '0.00')); ?>" 
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="invalid-feedback">Bitte geben Sie einen gültigen Betrag ein.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="limit_betrag" class="form-label">Limit-Betrag (€)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control validate-decimal" id="limit_betrag" name="limit_betrag" 
                                           value="<?php echo htmlspecialchars($hs['limit_betrag'] !== null ? str_replace('.', ',', $hs['limit_betrag']) : ''); ?>" 
                                           placeholder="Kein Limit">
                                    <span class="input-group-text">€</span>
                                </div>
                                <small class="form-text text-muted">Optional - Maximale Spende für diesen Hauptsponsor</small>
                            </div>
                        </div>
                        
                        <?php if ($hs): ?>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Informationen</label>
                                    <?php 
                                    $stmt_sponsor = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
                                    $stmt_sponsor->execute([$hs['sponsor_id']]);
                                    $sponsor = $stmt_sponsor->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($sponsor):
                                        echo "<div class='alert alert-info'>";
                                        echo "<p><strong>Sponsor:</strong> " . htmlspecialchars($sponsor['name']) . "</p>";
                                        echo "<p><strong>Betrag pro Bahn:</strong> " . number_format($hs['betrag_pro_bahn'], 2, ',', '.') . " €</p>";
                                        echo "<p><strong>Limit:</strong> " . ($hs['limit_betrag'] ? number_format($hs['limit_betrag'], 2, ',', '.') . " €" : "Kein Limit") . "</p>";
                                        
                                        // Berechnete Spende anzeigen
                                        $spendenbetrag_roh = $gesamt_bahnen * $hs['betrag_pro_bahn'];
                                        $spendenbetrag = $hs['limit_betrag'] ? min($spendenbetrag_roh, $hs['limit_betrag']) : $spendenbetrag_roh;
                                        echo "<p><strong>Gesamtspende (basierend auf $gesamt_bahnen Bahnen):</strong> " . number_format($spendenbetrag, 2, ',', '.') . " €</p>";
                                        echo "</div>";
                                    endif;
                                    ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> Speichern
                            </button>
                            <button type="submit" name="save_and_continue" class="btn btn-secondary">
                                <i class="fas fa-save"></i> Speichern & Weiter bearbeiten
                            </button>
                            <a href="hauptsponsoren.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Abbrechen
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-info-circle"></i> Informationen
                </div>
                <div class="card-body">
                    <p><strong>Hinweise:</strong></p>
                    <ul>
                        <li>Felder mit * sind Pflichtfelder</li>
                        <li>Der Betrag pro Bahn wird für die Spendenberechnung verwendet</li>
                        <li>Das Limit ist optional und begrenzt die maximale Spende für diesen Hauptsponsor</li>
                        <li>Wenn kein Limit angegeben ist, gibt es keine Obergrenze</li>
                        <li>Ein Sponsor kann nur einmal als Hauptsponsor markiert werden</li>
                    </ul>
                    
                    <hr>
                    <p><strong>Berechnung:</strong></p>
                    <p>Gesamtspende = Gesamtbahnen aller Schwimmer × Betrag pro Bahn</p>
                    <p>Wenn Limit gesetzt: Gesamtspende = min(Gesamtbahnen × Betrag pro Bahn, Limit)</p>
                    
                    <hr>
                    <p><strong>Aktuelle Statistik:</strong></p>
                    <p>Gesamtbahnen: <?php echo $gesamt_bahnen; ?></p>
                    <p>Gesamtspenden aller Hauptsponsoren: <?php echo number_format($gesamtspenden_hs, 2, ',', '.'); ?> €</p>
                </div>
            </div>
        </div>
    </div>

<?php endif; ?>

<?php
$additional_scripts = [
    $base_url . 'js/main.js'
];
require_once __DIR__ . '/includes/footer.php';
