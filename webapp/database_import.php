<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

$message = '';
$message_type = 'info';

// SQL-Datei importieren
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['sql_file'])) {
    try {
        $file = $_FILES['sql_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Fehler beim Hochladen der Datei: " . $file['error']);
        }
        
        if (!in_array($file['type'], ['text/plain', 'text/x-sql', 'application/octet-stream'])) {
            throw new Exception("Ungültiger Dateityp. Bitte laden Sie eine SQL-Datei hoch.");
        }
        
        $sql = file_get_contents($file['tmp_name']);
        if ($sql === false) {
            throw new Exception("Fehler beim Lesen der Datei.");
        }
        
        // SQL-Befehle ausführen
        $statements = explode(';', $sql);
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $pdo->exec($statement);
                    $success_count++;
                } catch (PDOException $e) {
                    $error_count++;
                    // Ignorieren und weiter machen
                }
            }
        }
        
        $message = "SQL-Import abgeschlossen. $success_count Befehle erfolgreich ausgeführt, $error_count mit Fehlern.";
        $message_type = $error_count > 0 ? 'warning' : 'success';
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'danger';
    }
}

// Datenbank-Export
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    try {
        // Alle Tabellen exportieren
        $tables = ['schwimmer', 'sponsoren', 'schwimmleistungen', 'schwimmer_sponsoren', 'hauptsponsoren'];
        $sql = "-- VAIBad Datenbank-Export
-- Erstellt am: " . date('Y-m-d H:i:s') . "

";
        
        foreach ($tables as $table) {
            // Tabellenstruktur
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $sql .= "-- Tabellenstruktur für Tabelle `$table`
";
            $sql .= $row['Create Table'] . ";

";
            
            // Daten
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
                $sql .= "-- Daten für Tabelle `$table`
";
                $columns = array_keys($rows[0]);
                $sql .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES
";
                
                foreach ($rows as $row) {
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = 'NULL';
                        } elseif (is_numeric($value)) {
                            $values[] = $value;
                        } else {
                            $values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $sql .= "(" . implode(', ', $values) . "),
";
                }
                
                $sql = rtrim($sql, ",\n") . ";

";
            }
        }
        
        // Datei zum Download anbieten
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="vaibad_export_' . date('Ymd_His') . '.sql"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit();
        
    } catch (Exception $e) {
        $message = "Fehler beim Export: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Datenbank-Info abrufen
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $table_info = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $stmt->fetchColumn();
        $table_info[$table] = $count;
    }
} catch (PDOException $e) {
    $message = "Fehler beim Abrufen der Tabelleninformationen: " . $e->getMessage();
    $message_type = 'danger';
}
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><i class="fas fa-database"></i> Datenbank-Import/Export</li>
            </ol>
        </nav>
        
        <h1 class="page-header">
            <i class="fas fa-database"></i> Datenbank-Import/Export
        </h1>
        <p class="text-muted">Importieren oder exportieren Sie die Datenbank.</p>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-import"></i> SQL-Datei importieren
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label for="sql_file" class="form-label">SQL-Datei *</label>
                        <input type="file" class="form-control" id="sql_file" name="sql_file" 
                               accept=".sql,text/plain" required>
                        <div class="invalid-feedback">Bitte wählen Sie eine SQL-Datei aus.</div>
                        <small class="form-text text-muted">
                            Nur SQL-Dateien (.sql) werden akzeptiert
                        </small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-file-import"></i> Importieren
                        </button>
                        <a href="<?php echo $base_url; ?>" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-file-export"></i> Datenbank exportieren
            </div>
            <div class="card-body">
                <p>Exportieren Sie die aktuelle Datenbank als SQL-Datei.</p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Der Export enthält alle Tabellen und Daten der VAIBad-Datenbank.
                </div>
                
                <a href="database_import.php?action=export" class="btn btn-success">
                    <i class="fas fa-file-export"></i> Datenbank exportieren
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Datenbank-Informationen
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tabelle</th>
                                <th>Einträge</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($table_info)): ?>
                                <?php foreach ($table_info as $table => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($table); ?></td>
                                        <td><?php echo $count; ?></td>
                                        <td>
                                            <a href="<?php echo $base_url . str_replace('_', '.php?table=', $table) . '.php'; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class="fas fa-table"></i> Anzeigen
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted">Keine Tabellen gefunden</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
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
