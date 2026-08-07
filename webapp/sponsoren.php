<?php
require_once __DIR__ . '/config.php';

// Seite Titel definieren
$page_title = "Sponsoren verwalten";

require_once __DIR__ . '/includes/header.php';

// Aktionen verarbeiten
$action = $_GET['action'] ?? 'list';
$message = '';
$message_type = 'info';

// Löschen
if ($action === 'delete' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        
        // Überprüfen, ob der Sponsor existiert
        $stmt = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
        $stmt->execute([$id]);
        $sponsor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sponsor) {
            // Sponsor löschen (CASCADE löscht abhängige Einträge)
            $stmt = $pdo->prepare("DELETE FROM sponsoren WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Sponsor erfolgreich gelöscht.";
            $message_type = "success";
        } else {
            $message = "Sponsor nicht gefunden.";
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
        $name = sanitizeInput($_POST['name']);
        $ist_hauptsponsor = isset($_POST['ist_hauptsponsor']) ? 1 : 0;
        
        if ($id) {
            // Aktualisieren
            $stmt = $pdo->prepare("UPDATE sponsoren SET name = ?, ist_hauptsponsor = ? WHERE id = ?");
            $stmt->execute([$name, $ist_hauptsponsor, $id]);
            $message = "Sponsor erfolgreich aktualisiert.";
            $message_type = "success";
        } else {
            // Neu erstellen
            $stmt = $pdo->prepare("INSERT INTO sponsoren (name, ist_hauptsponsor) VALUES (?, ?)");
            $stmt->execute([$name, $ist_hauptsponsor]);
            $id = $pdo->lastInsertId();
            $message = "Sponsor erfolgreich hinzugefügt.";
            $message_type = "success";
        }
        
        // Weiterleitung zur Bearbeitungsseite
        if (isset($_POST['save_and_continue'])) {
            header("Location: sponsoren.php?action=edit&id=$id");
            exit();
        } else {
            header("Location: sponsoren.php");
            exit();
        }
    } catch (PDOException $e) {
        $message = "Fehler beim Speichern: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Daten für Bearbeitung abrufen
$sponsor = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
        $stmt->execute([$id]);
        $sponsor = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Daten: " . $e->getMessage();
        $message_type = "danger";
        $action = 'list';
    }
}

// Alle Sponsoren für die Liste abrufen
$alle_sponsoren = [];
if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT s.*, 
                                    (SELECT COUNT(*) FROM schwimmer_sponsoren WHERE sponsor_id = s.id) as schwimmer_count,
                                    (SELECT COUNT(*) FROM hauptsponsoren WHERE sponsor_id = s.id) as is_hauptsponsor_entry
                             FROM sponsoren s 
                             ORDER BY s.ist_hauptsponsor DESC, s.name");
        $alle_sponsoren = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Sponsoren: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Hauptsponsoren-Informationen
$hauptsponsoren_list = [];
try {
    $stmt = $pdo->query("SELECT hs.*, sp.name as sponsor_name 
                         FROM hauptsponsoren hs 
                         JOIN sponsoren sp ON hs.sponsor_id = sp.id");
    $hauptsponsoren_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignorieren
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><i class="fas fa-hand-holding-heart"></i> Sponsoren</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-header m-0">
                <i class="fas fa-hand-holding-heart"></i> Sponsoren
            </h1>
            <a href="sponsoren.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Neuer Sponsor
            </a>
        </div>
        
        <p class="text-muted">Verwalten Sie hier die Sponsoren-Daten.</p>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Liste aller Sponsoren -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Sponsoren-Liste
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Typ</th>
                                    <th>Schwimmer</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($alle_sponsoren)): ?>
                                    <?php foreach ($alle_sponsoren as $s): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($s['id']); ?></td>
                                            <td><?php echo htmlspecialchars($s['name']); ?></td>
                                            <td>
                                                <?php if ($s['ist_hauptsponsor']): ?>
                                                    <span class="badge bg-success">Hauptsponsor</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Normaler Sponsor</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($s['schwimmer_count']); ?></td>
                                            <td>
                                                <a href="sponsoren.php?action=edit&id=<?php echo $s['id']; ?>" 
                                                   class="btn btn-sm btn-warning action-btn" 
                                                   data-bs-toggle="tooltip" title="Bearbeiten">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="schwimmer_sponsoren.php?sponsor_id=<?php echo $s['id']; ?>" 
                                                   class="btn btn-sm btn-info action-btn" 
                                                   data-bs-toggle="tooltip" title="Verknüpfte Schwimmer">
                                                    <i class="fas fa-link"></i>
                                                </a>
                                                <?php if ($s['ist_hauptsponsor'] && !$s['is_hauptsponsor_entry']): ?>
                                                    <a href="hauptsponsoren.php?action=add&sponsor_id=<?php echo $s['id']; ?>" 
                                                       class="btn btn-sm btn-primary action-btn" 
                                                       data-bs-toggle="tooltip" title="Als Hauptsponsor konfigurieren">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                <?php elseif ($s['ist_hauptsponsor'] && $s['is_hauptsponsor_entry']): ?>
                                                    <a href="hauptsponsoren.php?sponsor_id=<?php echo $s['id']; ?>" 
                                                       class="btn btn-sm btn-light action-btn" 
                                                       data-bs-toggle="tooltip" title="Hauptsponsor-Einstellungen">
                                                        <i class="fas fa-cog"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="sponsoren.php?action=delete&id=<?php echo $s['id']; ?>" 
                                                   class="btn btn-sm btn-danger action-btn confirm-delete" 
                                                   data-confirm-message="Möchten Sie den Sponsor <?php echo htmlspecialchars($s['name']); ?> wirklich löschen? Alle zugehörigen Daten werden ebenfalls gelöscht."
                                                   data-bs-toggle="tooltip" title="Löschen">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">Keine Sponsoren gefunden</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i> Sponsoren-Statistik
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Hauptsponsoren</h5>
                        <p class="text-muted">
                            <?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren WHERE ist_hauptsponsor = 1");
                            $count = $stmt->fetchColumn();
                            echo $count;
                            ?> Hauptsponsoren
                        </p>
                    </div>
                    <div class="mb-3">
                        <h5>Normale Sponsoren</h5>
                        <p class="text-muted">
                            <?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren WHERE ist_hauptsponsor = 0");
                            $count = $stmt->fetchColumn();
                            echo $count;
                            ?> normale Sponsoren
                        </p>
                    </div>
                    <div>
                        <h5>Gesamt</h5>
                        <p class="text-muted">
                            <?php 
                            $stmt = $pdo->query("SELECT COUNT(*) FROM sponsoren");
                            $count = $stmt->fetchColumn();
                            echo $count;
                            ?> Sponsoren
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
                    <?php echo $action === 'add' ? 'Neuer Sponsor' : 'Sponsor bearbeiten'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <?php if ($sponsor): ?>
                            <input type="hidden" name="id" value="<?php echo $sponsor['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($sponsor['name'] ?? ''); ?>" 
                                   required>
                            <div class="invalid-feedback">Bitte geben Sie einen Namen ein.</div>
                        </div>
                        
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="ist_hauptsponsor" name="ist_hauptsponsor" 
                                   <?php echo ($sponsor['ist_hauptsponsor'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="ist_hauptsponsor">
                                Ist Hauptsponsor
                            </label>
                            <small class="form-text text-muted">
                                Hauptsponsoren haben spezielle Spendenregelungen
                            </small>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" name="save" class="btn btn-primary">
                                <i class="fas fa-save"></i> Speichern
                            </button>
                            <button type="submit" name="save_and_continue" class="btn btn-secondary">
                                <i class="fas fa-save"></i> Speichern & Weiter bearbeiten
                            </button>
                            <a href="sponsoren.php" class="btn btn-outline-secondary">
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
                        <li>Hauptsponsoren können spezielle Spendenbeträge pro Bahn haben</li>
                        <li>Hauptsponsoren können auch ein Spendenlimit haben</li>
                    </ul>
                    
                    <?php if ($sponsor): ?>
                        <hr>
                        <p><strong>Schnellaktionen:</strong></p>
                        <div class="d-grid gap-2">
                            <a href="schwimmer_sponsoren.php?action=add&sponsor_id=<?php echo $sponsor['id']; ?>" 
                               class="btn btn-sm btn-info">
                                <i class="fas fa-plus"></i> Mit Schwimmer verknüpfen
                            </a>
                            <?php if ($sponsor['ist_hauptsponsor']): ?>
                                <a href="hauptsponsoren.php?sponsor_id=<?php echo $sponsor['id']; ?>" 
                                   class="btn btn-sm btn-warning">
                                    <i class="fas fa-cog"></i> Hauptsponsor-Einstellungen
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($sponsor['ist_hauptsponsor']): ?>
                            <hr>
                            <p><strong>Hauptsponsor-Informationen:</strong></p>
                            <?php 
                            $stmt = $pdo->prepare("SELECT * FROM hauptsponsoren WHERE sponsor_id = ?");
                            $stmt->execute([$sponsor['id']]);
                            $hs = $stmt->fetch(PDO::FETCH_ASSOC);
                            if ($hs):
                                echo "<p>Betrag pro Bahn: " . htmlspecialchars($hs['betrag_pro_bahn'] ?? 'Nicht konfiguriert');
                                echo "</p><p>Limit: ";
                                echo htmlspecialchars($hs['limit'] ?? 'Kein Limit');
                                echo "</p>";
                            else:
                                echo "<p class='text-muted'>Keine Hauptsponsor-Einstellungen gefunden</p>";
                            endif;
                            ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>