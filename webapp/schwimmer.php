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
        
        // Überprüfen, ob der Schwimmer existiert
        $stmt = $pdo->prepare("SELECT * FROM schwimmer WHERE id = ?");
        $stmt->execute([$id]);
        $schwimmer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($schwimmer) {
            // Schwimmer löschen (CASCADE löscht abhängige Einträge)
            $stmt = $pdo->prepare("DELETE FROM schwimmer WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Schwimmer erfolgreich gelöscht.";
            $message_type = "success";
        } else {
            $message = "Schwimmer nicht gefunden.";
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
        $vorname = sanitizeInput($_POST['vorname']);
        $nachname = sanitizeInput($_POST['nachname']);
        $geburtsjahr = (int)$_POST['geburtsjahr'];
        $team = sanitizeInput($_POST['team'] ?? '');
        
        if ($id) {
            // Aktualisieren
            $stmt = $pdo->prepare("UPDATE schwimmer SET vorname = ?, nachname = ?, geburtsjahr = ?, team = ? WHERE id = ?");
            $stmt->execute([$vorname, $nachname, $geburtsjahr, $team, $id]);
            $message = "Schwimmer erfolgreich aktualisiert.";
            $message_type = "success";
        } else {
            // Neu erstellen
            $stmt = $pdo->prepare("INSERT INTO schwimmer (vorname, nachname, geburtsjahr, team) VALUES (?, ?, ?, ?)");
            $stmt->execute([$vorname, $nachname, $geburtsjahr, $team]);
            $id = $pdo->lastInsertId();
            $message = "Schwimmer erfolgreich hinzugefügt.";
            $message_type = "success";
        }
        
        // Weiterleitung zur Bearbeitungsseite
        if (isset($_POST['save_and_continue'])) {
            header("Location: schwimmer.php?action=edit&id=$id");
            exit();
        } else {
            header("Location: schwimmer.php");
            exit();
        }
    } catch (PDOException $e) {
        $message = "Fehler beim Speichern: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Daten für Bearbeitung abrufen
$schwimmer = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM schwimmer WHERE id = ?");
        $stmt->execute([$id]);
        $schwimmer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Daten: " . $e->getMessage();
        $message_type = "danger";
        $action = 'list';
    }
}

// Alle Schwimmer für die Liste abrufen
$alle_schwimmer = [];
if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM schwimmer ORDER BY nachname, vorname");
        $alle_schwimmer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Schwimmer: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Teams für Dropdown abrufen
$teams = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT team FROM schwimmer WHERE team IS NOT NULL AND team != '' ORDER BY team");
    $teams = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignorieren, Teams sind optional
}

// Aktuelles Jahr für Altersberechnung
$current_year = date('Y');
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><i class="fas fa-user-friends"></i> Schwimmer</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-header m-0">
                <i class="fas fa-user-friends"></i> Schwimmer
            </h1>
            <a href="schwimmer.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neuer Schwimmer
            </a>
        </div>
        
        <p class="text-muted">Verwalten Sie hier die Schwimmer-Daten.</p>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Liste aller Schwimmer -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Schwimmer-Liste
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Geburtsjahr</th>
                            <th>Alter</th>
                            <th>Team</th>
                            <th>Erstellt am</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alle_schwimmer)): ?>
                            <?php foreach ($alle_schwimmer as $s): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($s['id']); ?></td>
                                    <td><?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?></td>
                                    <td><?php echo htmlspecialchars($s['geburtsjahr']); ?></td>
                                    <td><?php echo ($current_year - $s['geburtsjahr']); ?></td>
                                    <td><?php echo htmlspecialchars($s['team'] ?? '-'); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($s['erstelldatum'])); ?></td>
                                    <td>
                                        <a href="schwimmer.php?action=edit&id=<?php echo $s['id']; ?>" 
                                           class="btn btn-sm btn-warning action-btn" 
                                           data-bs-toggle="tooltip" title="Bearbeiten">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="schwimmleistungen.php?schwimmer_id=<?php echo $s['id']; ?>" 
                                           class="btn btn-sm btn-info action-btn" 
                                           data-bs-toggle="tooltip" title="Leistungen">
                                            <i class="fas fa-swimming-pool"></i>
                                        </a>
                                        <a href="schwimmer_sponsoren.php?schwimmer_id=<?php echo $s['id']; ?>" 
                                           class="btn btn-sm btn-secondary action-btn" 
                                           data-bs-toggle="tooltip" title="Sponsoren">
                                            <i class="fas fa-link"></i>
                                        </a>
                                        <a href="schwimmer.php?action=delete&id=<?php echo $s['id']; ?>" 
                                           class="btn btn-sm btn-danger action-btn confirm-delete" 
                                           data-confirm-message="Möchten Sie den Schwimmer <?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?> wirklich löschen? Alle zugehörigen Daten werden ebenfalls gelöscht."
                                           data-bs-toggle="tooltip" title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Keine Schwimmer gefunden</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
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
                    <?php echo $action === 'add' ? 'Neuer Schwimmer' : 'Schwimmer bearbeiten'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <?php if ($schwimmer): ?>
                            <input type="hidden" name="id" value="<?php echo $schwimmer['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="vorname" class="form-label">Vorname *</label>
                                <input type="text" class="form-control" id="vorname" name="vorname" 
                                       value="<?php echo htmlspecialchars($schwimmer['vorname'] ?? ''); ?>" 
                                       required>
                                <div class="invalid-feedback">Bitte geben Sie einen Vornamen ein.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="nachname" class="form-label">Nachname *</label>
                                <input type="text" class="form-control" id="nachname" name="nachname" 
                                       value="<?php echo htmlspecialchars($schwimmer['nachname'] ?? ''); ?>" 
                                       required>
                                <div class="invalid-feedback">Bitte geben Sie einen Nachnamen ein.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="geburtsjahr" class="form-label">Geburtsjahr *</label>
                                <input type="number" class="form-control validate-number" id="geburtsjahr" name="geburtsjahr" 
                                       value="<?php echo htmlspecialchars($schwimmer['geburtsjahr'] ?? ''); ?>" 
                                       min="1900" max="<?php echo $current_year; ?>" 
                                       required>
                                <div class="invalid-feedback">Bitte geben Sie ein gültiges Geburtsjahr ein.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="team" class="form-label">Team</label>
                                <select class="form-select" id="team" name="team">
                                    <option value="">-- Kein Team --</option>
                                    <?php foreach ($teams as $team): ?>
                                        <option value="<?php echo htmlspecialchars($team); ?>" 
                                            <?php echo ($schwimmer['team'] ?? '') === $team ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($team); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($schwimmer): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Erstellt am</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo date('d.m.Y H:i', strtotime($schwimmer['erstelldatum'])); ?>" 
                                           readonly>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Alter</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ($current_year - ($schwimmer['geburtsjahr'] ?? 0)); ?> Jahre" 
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
                            <a href="schwimmer.php" class="btn btn-outline-secondary">
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
                        <li>Das Geburtsjahr wird zur Berechnung des Alters verwendet</li>
                        <li>Schwimmer unter 14 Jahren haben eine Bahnlänge von 25m</li>
                        <li>Schwimmer ab 14 Jahren haben eine Bahnlänge von 50m</li>
                    </ul>
                    
                    <?php if ($schwimmer): ?>
                        <hr>
                        <p><strong>Schnellaktionen:</strong></p>
                        <div class="d-grid gap-2">
                            <a href="schwimmleistungen.php?action=add&schwimmer_id=<?php echo $schwimmer['id']; ?>" 
                               class="btn btn-sm btn-info">
                                <i class="fas fa-plus"></i> Leistung hinzufügen
                            </a>
                            <a href="schwimmer_sponsoren.php?action=add&schwimmer_id=<?php echo $schwimmer['id']; ?>" 
                               class="btn btn-sm btn-secondary">
                                <i class="fas fa-plus"></i> Sponsor hinzufügen
                            </a>
                        </div>
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
