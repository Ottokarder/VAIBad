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
        
        // Überprüfen, ob die Verknüpfung existiert
        $stmt = $pdo->prepare("SELECT * FROM schwimmer_sponsoren WHERE id = ?");
        $stmt->execute([$id]);
        $verknuepfung = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($verknuepfung) {
            // Verknüpfung löschen
            $stmt = $pdo->prepare("DELETE FROM schwimmer_sponsoren WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = "Verknüpfung erfolgreich gelöscht.";
            $message_type = "success";
        } else {
            $message = "Verknüpfung nicht gefunden.";
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
        $sponsor_id = (int)$_POST['sponsor_id'];
        $betrag_pro_bahn = (float)str_replace(',', '.', $_POST['betrag_pro_bahn']);
        $limit_betrag = $_POST['limit_betrag'] !== '' ? (float)str_replace(',', '.', $_POST['limit_betrag']) : null;
        
        if ($id) {
            // Aktualisieren
            $stmt = $pdo->prepare("UPDATE schwimmer_sponsoren SET schwimmer_id = ?, sponsor_id = ?, betrag_pro_bahn = ?, limit_betrag = ? WHERE id = ?");
            $stmt->execute([$schwimmer_id, $sponsor_id, $betrag_pro_bahn, $limit_betrag, $id]);
            $message = "Verknüpfung erfolgreich aktualisiert.";
            $message_type = "success";
        } else {
            // Neu erstellen
            $stmt = $pdo->prepare("INSERT INTO schwimmer_sponsoren (schwimmer_id, sponsor_id, betrag_pro_bahn, limit_betrag) VALUES (?, ?, ?, ?)");
            $stmt->execute([$schwimmer_id, $sponsor_id, $betrag_pro_bahn, $limit_betrag]);
            $id = $pdo->lastInsertId();
            $message = "Verknüpfung erfolgreich hinzugefügt.";
            $message_type = "success";
        }
        
        // Weiterleitung
        $filter_params = '';
        if (isset($_GET['schwimmer_id'])) {
            $filter_params .= '&schwimmer_id=' . $_GET['schwimmer_id'];
        }
        if (isset($_GET['sponsor_id'])) {
            $filter_params .= '&sponsor_id=' . $_GET['sponsor_id'];
        }
        
        if (isset($_POST['save_and_continue'])) {
            header("Location: schwimmer_sponsoren.php?action=edit&id=$id" . $filter_params);
            exit();
        } else {
            header("Location: schwimmer_sponsoren.php" . $filter_params);
            exit();
        }
    } catch (PDOException $e) {
        $message = "Fehler beim Speichern: " . $e->getMessage();
        $message_type = "danger";
    }
}

// Daten für Bearbeitung abrufen
$verknuepfung = null;
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM schwimmer_sponsoren WHERE id = ?");
        $stmt->execute([$id]);
        $verknuepfung = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Daten: " . $e->getMessage();
        $message_type = "danger";
        $action = 'list';
    }
}

// Alle Verknüpfungen für die Liste abrufen
$alle_verknuepfungen = [];
if ($action === 'list') {
    try {
        $where = [];
        $params = [];
        
        if (isset($_GET['schwimmer_id'])) {
            $where[] = "ss.schwimmer_id = ?";
            $params[] = (int)$_GET['schwimmer_id'];
        }
        if (isset($_GET['sponsor_id'])) {
            $where[] = "ss.sponsor_id = ?";
            $params[] = (int)$_GET['sponsor_id'];
        }
        
        $where_clause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $stmt = $pdo->prepare("SELECT ss.*, 
                                    s.vorname as schwimmer_vorname, 
                                    s.nachname as schwimmer_nachname,
                                    s.geburtsjahr as schwimmer_geburtsjahr,
                                    sp.name as sponsor_name,
                                    sp.ist_hauptsponsor
                             FROM schwimmer_sponsoren ss 
                             JOIN schwimmer s ON ss.schwimmer_id = s.id 
                             JOIN sponsoren sp ON ss.sponsor_id = sp.id 
                             $where_clause 
                             ORDER BY s.nachname, s.vorname, sp.name");
        $stmt->execute($params);
        $alle_verknuepfungen = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $message = "Fehler beim Laden der Verknüpfungen: " . $e->getMessage();
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

// Sponsoren für Dropdown abrufen
$sponsoren_list = [];
try {
    $stmt = $pdo->query("SELECT id, name FROM sponsoren ORDER BY name");
    $sponsoren_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Fehler beim Laden der Sponsoren: " . $e->getMessage();
    $message_type = "danger";
}

// Aktueller Schwimmer und Sponsor für Filter
$current_schwimmer = null;
$current_sponsor = null;
if (isset($_GET['schwimmer_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM schwimmer WHERE id = ?");
        $stmt->execute([$_GET['schwimmer_id']]);
        $current_schwimmer = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Ignorieren
    }
}
if (isset($_GET['sponsor_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
        $stmt->execute([$_GET['sponsor_id']]);
        $current_sponsor = $stmt->fetch(PDO::FETCH_ASSOC);
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
                <li class="breadcrumb-item active"><i class="fas fa-link"></i> Schwimmer-Sponsoren</li>
            </ol>
        </nav>
        
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="page-header m-0">
                <i class="fas fa-link"></i> Schwimmer-Sponsoren
            </h1>
            <a href="schwimmer_sponsoren.php?action=add<?php echo (isset($_GET['schwimmer_id']) ? '&schwimmer_id=' . $_GET['schwimmer_id'] : '') . (isset($_GET['sponsor_id']) ? '&sponsor_id=' . $_GET['sponsor_id'] : ''); ?>" 
               class="btn btn-primary">
                <i class="fas fa-plus"></i> Neue Verknüpfung
            </a>
        </div>
        
        <p class="text-muted">Verwalten Sie hier die Verknüpfungen zwischen Schwimmern und Sponsoren.</p>
    </div>
</div>

<?php if ($action === 'list'): ?>
    <!-- Liste aller Verknüpfungen -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list"></i> Schwimmer-Sponsoren-Liste
            <div class="float-end">
                <?php if ($current_schwimmer): ?>
                    <span class="badge bg-info me-2">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($current_schwimmer['vorname'] . ' ' . $current_schwimmer['nachname']); ?>
                    </span>
                <?php endif; ?>
                <?php if ($current_sponsor): ?>
                    <span class="badge bg-success">
                        <i class="fas fa-hand-holding-heart"></i> <?php echo htmlspecialchars($current_sponsor['name']); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <!-- Filter -->
            <div class="row mb-3">
                <div class="col-md-12">
                    <form method="GET" action="schwimmer_sponsoren.php" class="row g-3">
                        <div class="col-md-5">
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
                        <div class="col-md-5">
                            <select class="form-select" name="sponsor_id">
                                <option value="">Alle Sponsoren</option>
                                <?php foreach ($sponsoren_list as $sp): ?>
                                    <option value="<?php echo $sp['id']; ?>" 
                                        <?php echo (isset($_GET['sponsor_id']) && $_GET['sponsor_id'] == $sp['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sp['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i> Filtern
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php if (isset($_GET['schwimmer_id']) || isset($_GET['sponsor_id'])): ?>
                <div class="mb-3">
                    <a href="schwimmer_sponsoren.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Filter zurücksetzen
                    </a>
                </div>
            <?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Schwimmer</th>
                            <th>Alter</th>
                            <th>Sponsor</th>
                            <th>Betrag/Bahn</th>
                            <th>Limit</th>
                            <th>Gesamtspende</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($alle_verknuepfungen)): ?>
                            <?php foreach ($alle_verknuepfungen as $v): ?>
                                <?php 
                                $alter = $current_year - $v['schwimmer_geburtsjahr'];
                                $bahnenlaenge = ($alter < 14) ? 25 : 50;
                                
                                // Gesamtspenden aus den Leistungen berechnen
                                $stmt_leistungen = $pdo->prepare("SELECT COALESCE(SUM(anzahl_bahnen), 0) as total_bahnen FROM schwimmleistungen WHERE schwimmer_id = ?");
                                $stmt_leistungen->execute([$v['schwimmer_id']]);
                                $total_bahnen = $stmt_leistungen->fetchColumn();
                                
                                $spendenbetrag_roh = $total_bahnen * $v['betrag_pro_bahn'];
                                $spendenbetrag = $v['limit_betrag'] ? min($spendenbetrag_roh, $v['limit_betrag']) : $spendenbetrag_roh;
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($v['id']); ?></td>
                                    <td><?php echo htmlspecialchars($v['schwimmer_vorname'] . ' ' . $v['schwimmer_nachname']); ?></td>
                                    <td><?php echo $alter; ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($v['sponsor_name']); ?>
                                        <?php if ($v['ist_hauptsponsor']): ?>
                                            <span class="badge bg-success ms-1">Hauptsponsor</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo number_format($v['betrag_pro_bahn'], 2, ',', '.'); ?> €</td>
                                    <td><?php echo $v['limit_betrag'] ? number_format($v['limit_betrag'], 2, ',', '.') . ' €' : '-'; ?></td>
                                    <td><?php echo number_format($spendenbetrag, 2, ',', '.'); ?> €</td>
                                    <td>
                                        <a href="schwimmer_sponsoren.php?action=edit&id=<?php echo $v['id']; ?>" 
                                           class="btn btn-sm btn-warning action-btn" 
                                           data-bs-toggle="tooltip" title="Bearbeiten">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="schwimmer_sponsoren.php?action=delete&id=<?php echo $v['id']; ?>" 
                                           class="btn btn-sm btn-danger action-btn confirm-delete" 
                                           data-confirm-message="Möchten Sie diese Verknüpfung wirklich löschen?"
                                           data-bs-toggle="tooltip" title="Löschen">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Keine Verknüpfungen gefunden</td>
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
                    <?php echo $action === 'add' ? 'Neue Verknüpfung' : 'Verknüpfung bearbeiten'; ?>
                </div>
                <div class="card-body">
                    <form method="POST" class="needs-validation" novalidate>
                        <?php if ($verknuepfung): ?>
                            <input type="hidden" name="id" value="<?php echo $verknuepfung['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="schwimmer_id" class="form-label">Schwimmer *</label>
                                <select class="form-select" id="schwimmer_id" name="schwimmer_id" required>
                                    <option value="">-- Schwimmer auswählen --</option>
                                    <?php foreach ($schwimmer_list as $s): ?>
                                        <option value="<?php echo $s['id']; ?>" 
                                            <?php echo ($verknuepfung['schwimmer_id'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($s['vorname'] . ' ' . $s['nachname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Bitte wählen Sie einen Schwimmer aus.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="sponsor_id" class="form-label">Sponsor *</label>
                                <select class="form-select" id="sponsor_id" name="sponsor_id" required>
                                    <option value="">-- Sponsor auswählen --</option>
                                    <?php foreach ($sponsoren_list as $sp): ?>
                                        <option value="<?php echo $sp['id']; ?>" 
                                            <?php echo ($verknuepfung['sponsor_id'] ?? '') == $sp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($sp['name']); ?>
                                            <?php 
                                            $stmt_check = $pdo->prepare("SELECT ist_hauptsponsor FROM sponsoren WHERE id = ?");
                                            $stmt_check->execute([$sp['id']]);
                                            $is_hs = $stmt_check->fetchColumn();
                                            if ($is_hs):
                                                echo " (Hauptsponsor)";
                                            endif;
                                            ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Bitte wählen Sie einen Sponsor aus.</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="betrag_pro_bahn" class="form-label">Betrag pro Bahn (€) *</label>
                                <div class="input-group">
                                    <input type="text" class="form-control validate-decimal" id="betrag_pro_bahn" name="betrag_pro_bahn" 
                                           value="<?php echo htmlspecialchars(str_replace('.', ',', $verknuepfung['betrag_pro_bahn'] ?? '0.00')); ?>" 
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                                <div class="invalid-feedback">Bitte geben Sie einen gültigen Betrag ein.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="limit_betrag" class="form-label">Limit-Betrag (€)</label>
                                <div class="input-group">
                                    <input type="text" class="form-control validate-decimal" id="limit_betrag" name="limit_betrag" 
                                           value="<?php echo htmlspecialchars($verknuepfung['limit_betrag'] !== null ? str_replace('.', ',', $verknuepfung['limit_betrag']) : ''); ?>" 
                                           placeholder="Kein Limit">
                                    <span class="input-group-text">€</span>
                                </div>
                                <small class="form-text text-muted">Optional - Maximale Spende für diesen Schwimmer</small>
                            </div>
                        </div>
                        
                        <?php if ($verknuepfung): ?>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label class="form-label">Informationen</label>
                                    <?php 
                                    $stmt_schwimmer = $pdo->prepare("SELECT * FROM schwimmer WHERE id = ?");
                                    $stmt_schwimmer->execute([$verknuepfung['schwimmer_id']]);
                                    $schwimmer = $stmt_schwimmer->fetch(PDO::FETCH_ASSOC);
                                    
                                    $stmt_sponsor = $pdo->prepare("SELECT * FROM sponsoren WHERE id = ?");
                                    $stmt_sponsor->execute([$verknuepfung['sponsor_id']]);
                                    $sponsor = $stmt_sponsor->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($schwimmer && $sponsor):
                                        echo "<div class='alert alert-info'>";
                                        echo "<p><strong>Schwimmer:</strong> " . htmlspecialchars($schwimmer['vorname'] . ' ' . $schwimmer['nachname']) . "</p>";
                                        echo "<p><strong>Sponsor:</strong> " . htmlspecialchars($sponsor['name']) . "</p>";
                                        echo "<p><strong>Betrag pro Bahn:</strong> " . number_format($verknuepfung['betrag_pro_bahn'], 2, ',', '.') . " €</p>";
                                        echo "<p><strong>Limit:</strong> " . ($verknuepfung['limit_betrag'] ? number_format($verknuepfung['limit_betrag'], 2, ',', '.') . " €" : "Kein Limit") . "</p>";
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
                            <a href="schwimmer_sponsoren.php<?php echo (isset($_GET['schwimmer_id']) ? '?schwimmer_id=' . $_GET['schwimmer_id'] : '') . (isset($_GET['sponsor_id']) ? '&sponsor_id=' . $_GET['sponsor_id'] : ''); ?>" 
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
                        <li>Der Betrag pro Bahn wird für die Spendenberechnung verwendet</li>
                        <li>Das Limit ist optional und begrenzt die maximale Spende für diesen Schwimmer</li>
                        <li>Wenn kein Limit angegeben ist, gibt es keine Obergrenze</li>
                    </ul>
                    
                    <hr>
                    <p><strong>Berechnung:</strong></p>
                    <p>Gesamtspende = Anzahl Bahnen × Betrag pro Bahn</p>
                    <p>Wenn Limit gesetzt: Gesamtspende = min(Anzahl Bahnen × Betrag pro Bahn, Limit)</p>
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
