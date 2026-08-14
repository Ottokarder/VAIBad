<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Seite Titel definieren
$page_title = "Schwimmleistungen verwalten";

// Aktionen verarbeiten
$action = $_GET['action'] ?? 'list';
$message = '';
$message_type = 'info';

// Löschen
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Überprüfen, ob die Leistung existiert
        $stmt = $pdo->prepare("SELECT * FROM schwimmleistungen WHERE id = ?");
        $stmt->execute([$id]);
        $leistung = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($leistung) {
            // Leistung löschen
            $stmt = $pdo->prepare("DELETE FROM schwimmleistungen WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Leistung erfolgreich gelöscht.";
            $message_type = "success";
        } else {
            $message = "Leistung nicht gefunden.";
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
        $schwimmer_id = (int)$_POST['schwimmer_id'];
        $anzahl_bahnen_vormittag = (int)($_POST['anzahl_bahnen_vormittag'] ?? 0);
        $anzahl_bahnen_nachmittag = (int)($_POST['anzahl_bahnen_nachmittag'] ?? 0);
        $datum = $_POST['datum'] ? date('Y-m-d', strtotime($_POST['datum'])) : date('Y-m-d');
        
        if ($anzahl_bahnen_vormittag < 0 || $anzahl_bahnen_nachmittag < 0) {
            throw new Exception("Die Anzahl der Bahnen darf nicht negativ sein.");
        }
        
        if ($id) {
            // Aktualisieren
            $stmt = $pdo->prepare("UPDATE schwimmleistungen SET schwimmer_id = ?, anzahl_bahnen_vormittag = ?, anzahl_bahnen_nachmittag = ?, datum = ? WHERE id = ?");
            $stmt->execute([$schwimmer_id, $anzahl_bahnen_vormittag, $anzahl_bahnen_nachmittag, $datum, $id]);
            $message = "Leistung erfolgreich aktualisiert.";
            $message_type = "success";
        } else {
            // Neu erstellen
            $stmt = $pdo->prepare("INSERT INTO schwimmleistungen (schwimmer_id, anzahl_bahnen_vormittag, anzahl_bahnen_nachmittag, datum) VALUES (?, ?, ?, ?)");
            $stmt->execute([$schwimmer_id, $anzahl_bahnen_vormittag, $anzahl_bahnen_nachmittag, $datum]);
            $id = $pdo->lastInsertId();
            $message = "Leistung erfolgreich hinzugefügt.";
            $message_type = "success";
        }
        
        // Weiterleitung mit korrekter URL (? statt & am Anfang)
        if (isset($_POST['save_and_continue'])) {
            // Für "Speichern & Weiter bearbeiten": action=edit + id + schwimmer_id
            $params = '?action=edit&id=' . $id;
            if (isset($_POST['schwimmer_id']) && !empty($_POST['schwimmer_id'])) {
                $params .= '&schwimmer_id=' . (int)$_POST['schwimmer_id'];
            }
            header("Location: schwimmleistungen.php" . $params);
            exit();
        } else {
            // Für "Speichern": nur schwimmer_id (falls gesetzt)
            $params = '';
            if (isset($_POST['schwimmer_id']) && !empty($_POST['schwimmer_id'])) {
                $params = '?schwimmer_id=' . (int)$_POST['schwimmer_id'];
            }
            header("Location: schwimmleistungen.php" . $params);
            exit();
        }
    } catch (PDOException $e) {
        $message = "Fehler beim Speichern: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Daten für Bearbeitung abrufen
$leistung = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM schwimmleistungen WHERE id = ?");
        $stmt->execute([$id]);
        $leistung = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Daten: " . $e->getMessage();
        $message_type = "danger";
        $action = 'list';
    }
}

// Alle Schwimmleistungen für die Liste abrufen
$alle_leistungen = [];
if ($action === 'list') {
    try {
        if (isset($_GET['schwimmer_id']) && !empty($_GET['schwimmer_id'])) {
            // Filter für bestimmten Schwimmer
            $stmt = $pdo->prepare("SELECT sl.*, s.vorname, s.nachname, s.geburtsjahr 
                                   FROM schwimmleistungen sl 
                                   JOIN schwimmer s ON sl.schwimmer_id = s.id 
                                   WHERE sl.schwimmer_id = ? 
                                   ORDER BY sl.datum DESC, s.nachname, s.vorname");
            $stmt->execute([(int)$_GET['schwimmer_id']]);
        } else {
            // Alle Leistungen aller Schwimmer anzeigen
            $stmt = $pdo->query("SELECT sl.*, s.vorname, s.nachname, s.geburtsjahr 
                                   FROM schwimmleistungen sl 
                                   JOIN schwimmer s ON sl.schwimmer_id = s.id 
                                   ORDER BY sl.datum DESC, s.nachname, s.vorname");
        }
        $alle_leistungen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Leistungen: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Schwimmer für Dropdown abrufen
$schwimmer_list = [];
try {
    $stmt = $pdo->query("SELECT id, vorname, nachname FROM schwimmer ORDER BY nachname, vorname");
    $schwimmer_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Fehler beim Laden der Schwimmer: " . $e->getMessage();
    $message_type = "danger";
}

// Aktueller Schwimmer für Filter
$current_schwimmer = null;
if (isset($_GET['schwimmer_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM schwimmer WHERE id = ?");
        $stmt->execute([$_GET['schwimmer_id']]);
        $current_schwimmer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorieren
    }
}

// Aktuelles Jahr für Altersberechnung
$current_year = date('Y');
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><i class="fas fa-swimming-pool"></i> Schwimmleistungen</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-header m-0">
                <i class="fas fa-swimming-pool"></i> Schwimmleistungen
            </h1>
            <a href="schwimmleistungen.php?action=add<?php echo (isset($_GET['schwimmer_id']) ? '&schwimmer_id=' . $_GET['schwimmer_id'] : (isset($current_schwimmer['id']) ? '&schwimmer_id=' . $current_schwimmer['id'] : '')); ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus"></i> Neue Leistung
            </a>
        </div>
        
        <p class="text-muted">Verwalten Sie hier die Schwimmleistungen der Schwimmer.</p>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Liste aller Schwimmleistungen -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Schwimmleistungen-Liste
            <?php if ($current_schwimmer): ?>
                <span class="float-end">
                    <span class="badge bg-info">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_schwimmer['vorname'] . ' ' . $current_schwimmer['nachname']); ?>
                    </span>
                </span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <!-- Filter -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form method="GET" action="schwimmleistungen.php" class="row g-3">
                        <div class="col-md-8">
                            <select class="form-select" name="schwimmer_id">
                                <option value="">Alle Schwimmer</option>
                                <?php foreach ($schwimmer_list as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" 
                                        <?php echo (isset($_GET['schwimmer_id']) && $_GET['schwimmer_id'] == $s['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtern
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-end">
                    <?php if (isset($_GET['schwimmer_id'])): ?>
                        <a href="schwimmleistungen.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Filter zurücksetzen
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Schwimmer</th>
                            <th>Alter</th>
                            <th>Bahnen Vormittag</th>
                            <th>Bahnen Nachmittag</th>
                            <th>Bahnen Gesamt</th>
                            <th>Bahnlänge</th>
                            <th>Gesamtstrecke</th>
                            <th>Datum</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alle_leistungen)): ?>
                            <?php foreach ($alle_leistungen as $l): ?>
                                <?php 
                                $alter = $current_year - $l['geburtsjahr'];
                                $bahnenlaenge = ($alter < 14) ? 25 : 50;
                                $bahnen_vormittag = (int)($l['anzahl_bahnen_vormittag'] ?? 0);
                                $bahnen_nachmittag = (int)($l['anzahl_bahnen_nachmittag'] ?? 0);
                                $bahnen_gesamt = $bahnen_vormittag + $bahnen_nachmittag;
                                $gesamtstrecke = $bahnen_gesamt * $bahnenlaenge;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($l['id']); ?></td>
                                    <td><?php echo htmlspecialchars($l['vorname'] . ' ' . $l['nachname']); ?></td>
                                    <td><?php echo $alter; ?></td>
                                    <td><?php echo $bahnen_vormittag; ?></td>
                                    <td><?php echo $bahnen_nachmittag; ?></td>
                                    <td><strong><?php echo $bahnen_gesamt; ?></strong></td>
                                    <td><?php echo $bahnenlaenge; ?> m</td>
                                    <td><?php echo number_format($gesamtstrecke, 0, ',', '.'); ?> m</td>
                                    <td><?php echo date('d.m.Y', strtotime($l['datum'])); ?></td>
                                    <td>
                                        <a href="schwimmleistungen.php?action=edit&id=<?php echo $l['id']; ?>" 
                                           class="btn btn-sm btn-warning action-btn" 
                                           data-bs-toggle="tooltip" title="Bearbeiten">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="schwimmleistungen.php?action=delete&id=<?php echo $l['id']; ?>" 
                                           class="btn btn-sm btn-danger action-btn confirm-delete" 
                                           data-confirm-message="Möchten Sie diese Leistung wirklich löschen?"
                                           data-bs-toggle="tooltip" title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">
                                    <?php echo isset($_GET['schwimmer_id']) ? 'Keine Leistungen für diesen Schwimmer gefunden' : 'Keine Schwimmleistungen gefunden'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3">Gesamt</th>
                            <th>
                                <?php
                                $total_vormittag = 0;
                                $total_nachmittag = 0;
                                $total_strecke = 0;
                                foreach ($alle_leistungen as $l) {
                                    $alter = $current_year - $l['geburtsjahr'];
                                    $bahnenlaenge = ($alter < 14) ? 25 : 50;
                                    $bv = (int)($l['anzahl_bahnen_vormittag'] ?? 0);
                                    $bn = (int)($l['anzahl_bahnen_nachmittag'] ?? 0);
                                    $total_vormittag += $bv;
                                    $total_nachmittag += $bn;
                                    $total_strecke += ($bv + $bn) * $bahnenlaenge;
                                }
                                echo $total_vormittag;
                                ?>
                            </th>
                            <th><?php echo $total_nachmittag; ?></th>
                            <th><?php echo $total_vormittag + $total_nachmittag; ?></th>
                            <th>-</th>
                            <th><?php echo number_format($total_strecke, 0, ',', '.'); ?> m</th>
                            <th colspan="2"></th>
                        </tr>
                    </tfoot>
                </table>
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
                    <?php echo $action === 'add' ? 'Neue Schwimmleistung' : 'Schwimmleistung bearbeiten'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <?php if ($leistung): ?>
                            <input type="hidden" name="id" value="<?php echo $leistung['id']; ?>">
                        <?php endif; ?>
                        
                        <?php if (isset($_GET["schwimmer_id"]) || isset($leistung["schwimmer_id"])): ?>
                            <input type="hidden" name="schwimmer_id" value="<?php echo (int)($_GET["schwimmer_id"] ?? $leistung["schwimmer_id"] ?? 0); ?>">
                            <div class="mb-3">
                                <label class="form-label">Schwimmer</label>
                                <input type="text" class="form-control" value="<?php
                                    $schwimmer_name = "";
                                    if (isset($_GET["schwimmer_id"]) && !empty($_GET["schwimmer_id"])) {
                                        $stmt = $pdo->prepare("SELECT vorname, nachname FROM schwimmer WHERE id = ?");
                                        $stmt->execute([(int)$_GET["schwimmer_id"]]);
                                        $s = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $schwimmer_name = $s ? htmlspecialchars($s["vorname"] . " " . $s["nachname"]) : "Unbekannt";
                                    } elseif (isset($leistung["schwimmer_id"]) && !empty($leistung["schwimmer_id"])) {
                                        $stmt = $pdo->prepare("SELECT vorname, nachname FROM schwimmer WHERE id = ?");
                                        $stmt->execute([(int)$leistung["schwimmer_id"]]);
                                        $s = $stmt->fetch(PDO::FETCH_ASSOC);
                                        $schwimmer_name = $s ? htmlspecialchars($s["vorname"] . " " . $s["nachname"]) : "Unbekannt";
                                    }
                                    echo $schwimmer_name;
                                ?>" readonly>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                Kein Schwimmer ausgewählt. Bitte wählen Sie einen Schwimmer aus der Liste.
                            </div>
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="anzahl_bahnen_vormittag" class="form-label">Bahnen Vormittag</label>
                                <input type="number" class="form-control validate-number" id="anzahl_bahnen_vormittag" name="anzahl_bahnen_vormittag" 
                                       value="<?php echo htmlspecialchars($leistung['anzahl_bahnen_vormittag'] ?? '0'); ?>" 
                                       min="0" max="1000">
                                <small class="form-text text-muted">0 = hat vormittags nicht geschwommen.</small>
                                <div class="invalid-feedback">Bitte geben Sie eine gültige Anzahl ein.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="anzahl_bahnen_nachmittag" class="form-label">Bahnen Nachmittag</label>
                                <input type="number" class="form-control validate-number" id="anzahl_bahnen_nachmittag" name="anzahl_bahnen_nachmittag" 
                                       value="<?php echo htmlspecialchars($leistung['anzahl_bahnen_nachmittag'] ?? '0'); ?>" 
                                       min="0" max="1000">
                                <small class="form-text text-muted">0 = hat nachmittags nicht geschwommen.</small>
                                <div class="invalid-feedback">Bitte geben Sie eine gültige Anzahl ein.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="datum" class="form-label">Datum *</label>
                                <input type="date" class="form-control" id="datum" name="datum" 
                                       value="<?php echo htmlspecialchars($leistung['datum'] ?? date('Y-m-d')); ?>" 
                                       required>
                                <div class="invalid-feedback">Bitte geben Sie ein gültiges Datum ein.</div>
                            </div>
                        </div>
                        
                        <?php if ($leistung): ?>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Bahnlänge</label>
                                    <?php 
                                    $schwimmer_alter = $current_year - ($leistung['geburtsjahr'] ?? 0);
                                    $bahnenlaenge = ($schwimmer_alter < 14) ? 25 : 50;
                                    $bv = (int)($leistung['anzahl_bahnen_vormittag'] ?? 0);
                                    $bn = (int)($leistung['anzahl_bahnen_nachmittag'] ?? 0);
                                    $gesamtstrecke = ($bv + $bn) * $bahnenlaenge;
                                    ?>
                                    <input type="text" class="form-control" 
                                           value="<?php echo $bahnenlaenge; ?> m" 
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Bahnen gesamt</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ($bv + $bn); ?>" 
                                           readonly>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Gesamtstrecke</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo number_format($gesamtstrecke, 0, ',', '.'); ?> m" 
                                           readonly>
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
                            <a href="schwimmleistungen.php<?php echo isset($_GET['schwimmer_id']) ? '?schwimmer_id=' . $_GET['schwimmer_id'] : ''; ?>" 
                               class="btn btn-outline-secondary">
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
                        <li>Die Bahnlänge wird automatisch basierend auf dem Alter des Schwimmers berechnet</li>
                        <li>Schwimmer unter 14 Jahren: 25m pro Bahn</li>
                        <li>Schwimmer ab 14 Jahren: 50m pro Bahn</li>
                        <li>Die Gesamtstrecke wird automatisch berechnet</li>
                    </ul>
                    
                    <?php if ($leistung): ?>
                        <hr>
                        <p><strong>Schwimmer-Informationen:</strong></p>
                        <?php 
                        $stmt = $pdo->prepare("SELECT * FROM schwimmer WHERE id = ?");
                        $stmt->execute([$leistung['schwimmer_id']]);
                        $schwimmer = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($schwimmer):
                            echo "<p><strong>Name:</strong> " . htmlspecialchars($schwimmer['vorname'] . ' ' . $schwimmer['nachname']) . "</p>";
                            echo "<p><strong>Geburtsjahr:</strong> " . htmlspecialchars($schwimmer['geburtsjahr']) . "</p>";
                            echo "<p><strong>Alter:</strong> " . ($current_year - $schwimmer['geburtsjahr']) . " Jahre</p>";
                            echo "<p><strong>Team:</strong> " . htmlspecialchars($schwimmer['team'] ?? '-') . "</p>";
                        endif;
                        ?>
                    <?php endif; ?>
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
