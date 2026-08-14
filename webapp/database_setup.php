<?php
/**
 * VAIBad - Datenbank-Einrichtung / Migration
 *
 * Diese Datei richtet das Datenbankschema für die Vormittag-/Nachmittag-Trennung
 * (zwei Durchläufe) und die Startnummer pro Schwimmer ein.
 *
 * Sie ist idempotent: bestehende Spalten/Views werden nicht doppelt angelegt.
 * Für Neuinstallationen wird das vollständige Schema angelegt, für bestehende
 * Installationen werden die neuen Spalten ergänzt und bestehende Daten migriert:
 *   - alte Spalte anzahl_bahnen -> anzahl_bahnen_vormittag (falls vorhanden)
 *
 * Aufruf im Browser: database_setup.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

$results = [];
$error = '';

function log_step(&$results, $label, $ok, $detail = '') {
    $results[] = ['label' => $label, 'ok' => $ok, 'detail' => $detail];
}

// Hilfsfunktion: prüfen, ob eine Spalte existiert
function column_exists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch() !== false;
    } catch (PDOException $e) {
        return false;
    }
}

try {
    // ------------------------------------------------------------------
    // Tabelle: schwimmer (inkl. startnummer)
    // ------------------------------------------------------------------
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schwimmer (
            id INT AUTO_INCREMENT PRIMARY KEY,
            startnummer INT UNIQUE,
            vorname VARCHAR(100) NOT NULL,
            nachname VARCHAR(100) NOT NULL,
            geburtsjahr INT NOT NULL,
            team VARCHAR(100) DEFAULT NULL,
            erstelldatum DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        log_step($results, 'Tabelle schwimmer', true, 'angelegt oder bereits vorhanden');
    } catch (PDOException $e) {
        log_step($results, 'Tabelle schwimmer', false, $e->getMessage());
    }

    // startnummer-Spalte ergänzen (für bestehende Installationen)
    if (!column_exists($pdo, 'schwimmer', 'startnummer')) {
        try {
            $pdo->exec("ALTER TABLE schwimmer ADD COLUMN startnummer INT NULL AFTER id");
            log_step($results, 'Spalte schwimmer.startnummer', true, 'hinzugefügt');
        } catch (PDOException $e) {
            log_step($results, 'Spalte schwimmer.startnummer', false, $e->getMessage());
        }
        // Unique-Index nur setzen, wenn noch keiner vorhanden ist
        try {
            $indexes = $pdo->query("SHOW INDEX FROM schwimmer WHERE Column_name = 'startnummer'")->fetchAll(PDO::FETCH_ASSOC);
            if (empty($indexes)) {
                $pdo->exec("ALTER TABLE schwimmer ADD UNIQUE INDEX uniq_startnummer (startnummer)");
                log_step($results, 'Index uniq_startnummer', true, 'angelegt');
            }
        } catch (PDOException $e) {
            log_step($results, 'Index uniq_startnummer', false, $e->getMessage());
        }
    } else {
        log_step($results, 'Spalte schwimmer.startnummer', true, 'bereits vorhanden');
    }

    // Startnummern für bestehende Schwimmer vergeben (aufsteigend nach id)
    try {
        $stmt = $pdo->query("SELECT id FROM schwimmer WHERE startnummer IS NULL ORDER BY id");
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $upd = $pdo->prepare("UPDATE schwimmer SET startnummer = ? WHERE id = ?");
        $next = (int)$pdo->query("SELECT COALESCE(MAX(startnummer), 0) FROM schwimmer")->fetchColumn();
        $count = 0;
        foreach ($rows as $sid) {
            $next++;
            $upd->execute([$next, $sid]);
            $count++;
        }
        log_step($results, 'Bestehende Startnummern', true, $count . ' Schwimmer aktualisiert');
    } catch (PDOException $e) {
        log_step($results, 'Bestehende Startnummern', false, $e->getMessage());
    }

    // ------------------------------------------------------------------
    // Tabelle: sponsoren
    // ------------------------------------------------------------------
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sponsoren (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            ist_hauptsponsor TINYINT(1) NOT NULL DEFAULT 0,
            erstelldatum DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        log_step($results, 'Tabelle sponsoren', true, 'angelegt oder bereits vorhanden');
    } catch (PDOException $e) {
        log_step($results, 'Tabelle sponsoren', false, $e->getMessage());
    }

    // ------------------------------------------------------------------
    // Tabelle: schwimmleistungen (Vormittag/Nachmittag als feste Spalten)
    // ------------------------------------------------------------------
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schwimmleistungen (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schwimmer_id INT NOT NULL,
            anzahl_bahnen_vormittag INT NOT NULL DEFAULT 0,
            anzahl_bahnen_nachmittag INT NOT NULL DEFAULT 0,
            datum DATE NOT NULL,
            erstelldatum DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_sl_schwimmer FOREIGN KEY (schwimmer_id) REFERENCES schwimmer(id) ON DELETE CASCADE,
            INDEX idx_sl_schwimmer (schwimmer_id),
            INDEX idx_sl_datum (datum)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        log_step($results, 'Tabelle schwimmleistungen', true, 'angelegt oder bereits vorhanden');
    } catch (PDOException $e) {
        log_step($results, 'Tabelle schwimmleistungen', false, $e->getMessage());
    }

    // Migration bestehender schwimmleistungen-Spalten (idempotent)
    // 1) Neue Spalten ergänzen, falls fehlend
    if (!column_exists($pdo, 'schwimmleistungen', 'anzahl_bahnen_vormittag')) {
        try {
            $pdo->exec("ALTER TABLE schwimmleistungen ADD COLUMN anzahl_bahnen_vormittag INT NOT NULL DEFAULT 0 AFTER schwimmer_id");
            log_step($results, 'Spalte anzahl_bahnen_vormittag', true, 'hinzugefügt');
        } catch (PDOException $e) {
            log_step($results, 'Spalte anzahl_bahnen_vormittag', false, $e->getMessage());
        }
    } else {
        log_step($results, 'Spalte anzahl_bahnen_vormittag', true, 'bereits vorhanden');
    }
    if (!column_exists($pdo, 'schwimmleistungen', 'anzahl_bahnen_nachmittag')) {
        try {
            $pdo->exec("ALTER TABLE schwimmleistungen ADD COLUMN anzahl_bahnen_nachmittag INT NOT NULL DEFAULT 0 AFTER anzahl_bahnen_vormittag");
            log_step($results, 'Spalte anzahl_bahnen_nachmittag', true, 'hinzugefügt');
        } catch (PDOException $e) {
            log_step($results, 'Spalte anzahl_bahnen_nachmittag', false, $e->getMessage());
        }
    } else {
        log_step($results, 'Spalte anzahl_bahnen_nachmittag', true, 'bereits vorhanden');
    }

    // 2) Alte Einzelspalte anzahl_bahnen in vormittag übernehmen, dann löschen
    if (column_exists($pdo, 'schwimmleistungen', 'anzahl_bahnen')) {
        try {
            $pdo->exec("UPDATE schwimmleistungen SET anzahl_bahnen_vormittag = anzahl_bahnen WHERE anzahl_bahnen_vormittag = 0");
            log_step($results, 'Migration anzahl_bahnen -> vormittag', true, 'Daten übernommen');
        } catch (PDOException $e) {
            log_step($results, 'Migration anzahl_bahnen -> vormittag', false, $e->getMessage());
        }
        try {
            $pdo->exec("ALTER TABLE schwimmleistungen DROP COLUMN anzahl_bahnen");
            log_step($results, 'Alte Spalte anzahl_bahnen', true, 'entfernt');
        } catch (PDOException $e) {
            log_step($results, 'Alte Spalte anzahl_bahnen', false, $e->getMessage());
        }
    } else {
        log_step($results, 'Alte Spalte anzahl_bahnen', true, 'nicht vorhanden, nichts zu migrieren');
    }

    // ------------------------------------------------------------------
    // Tabelle: schwimmer_sponsoren
    // ------------------------------------------------------------------
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS schwimmer_sponsoren (
            id INT AUTO_INCREMENT PRIMARY KEY,
            schwimmer_id INT NOT NULL,
            sponsor_id INT NOT NULL,
            betrag_pro_bahn DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            limit_betrag DECIMAL(10,2) DEFAULT NULL,
            erstelldatum DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_ss_schwimmer FOREIGN KEY (schwimmer_id) REFERENCES schwimmer(id) ON DELETE CASCADE,
            CONSTRAINT fk_ss_sponsor FOREIGN KEY (sponsor_id) REFERENCES sponsoren(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_schwimmer_sponsor (schwimmer_id, sponsor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        log_step($results, 'Tabelle schwimmer_sponsoren', true, 'angelegt oder bereits vorhanden');
    } catch (PDOException $e) {
        log_step($results, 'Tabelle schwimmer_sponsoren', false, $e->getMessage());
    }

    // ------------------------------------------------------------------
    // Tabelle: hauptsponsoren
    // ------------------------------------------------------------------
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS hauptsponsoren (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sponsor_id INT NOT NULL,
            betrag_pro_bahn DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            limit_betrag DECIMAL(10,2) DEFAULT NULL,
            erstelldatum DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_hs_sponsor FOREIGN KEY (sponsor_id) REFERENCES sponsoren(id) ON DELETE CASCADE,
            UNIQUE KEY uniq_hauptsponsor (sponsor_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        log_step($results, 'Tabelle hauptsponsoren', true, 'angelegt oder bereits vorhanden');
    } catch (PDOException $e) {
        log_step($results, 'Tabelle hauptsponsoren', false, $e->getMessage());
    }

    // ------------------------------------------------------------------
    // Views (immer neu anlegen -> DROP IF EXISTS + CREATE)
    // ------------------------------------------------------------------

    // Bahnlänge abhängig vom Alter (unter 14 = 25m, ab 14 = 50m)
    $pdo->exec("DROP VIEW IF EXISTS v_schwimmer_gesamtstrecke");
    $pdo->exec("CREATE VIEW v_schwimmer_gesamtstrecke AS
        SELECT s.id, s.startnummer, s.vorname, s.nachname, s.geburtsjahr, s.team,
               (YEAR(CURDATE()) - s.geburtsjahr) AS `alter`,
               COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) AS anzahl_bahnen_vormittag,
               COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0) AS anzahl_bahnen_nachmittag,
               COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0) AS anzahl_bahnen,
               CASE WHEN (YEAR(CURDATE()) - s.geburtsjahr) < 14 THEN 25 ELSE 50 END AS bahnenlaenge_m,
               CASE WHEN (YEAR(CURDATE()) - s.geburtsjahr) < 14 THEN 25 ELSE 50 END
                 * (COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0)) AS gesamtstrecke_m
        FROM schwimmer s
        LEFT JOIN schwimmleistungen sl ON sl.schwimmer_id = s.id
        GROUP BY s.id, s.startnummer, s.vorname, s.nachname, s.geburtsjahr, s.team");
    log_step($results, 'View v_schwimmer_gesamtstrecke', true, 'aktualisiert (vormittag/nachmittag/gesamt)');

    $pdo->exec("DROP VIEW IF EXISTS v_schwimmer_gesamtspenden");
    $pdo->exec("CREATE VIEW v_schwimmer_gesamtspenden AS
        SELECT s.id, s.startnummer, s.vorname, s.nachname, s.team,
               COALESCE(SUM(LEAST(
                   (COALESCE(sl.anzahl_bahnen_vormittag,0) + COALESCE(sl.anzahl_bahnen_nachmittag,0)) * ss.betrag_pro_bahn,
                   IFNULL(ss.limit_betrag, (COALESCE(sl.anzahl_bahnen_vormittag,0) + COALESCE(sl.anzahl_bahnen_nachmittag,0)) * ss.betrag_pro_bahn)
               )), 0) AS gesamtspenden
        FROM schwimmer s
        LEFT JOIN schwimmer_sponsoren ss ON ss.schwimmer_id = s.id
        LEFT JOIN schwimmleistungen sl ON sl.schwimmer_id = s.id
        GROUP BY s.id, s.startnummer, s.vorname, s.nachname, s.team");
    log_step($results, 'View v_schwimmer_gesamtspenden', true, 'aktualisiert');

    $pdo->exec("DROP VIEW IF EXISTS v_schwimmer_sponsor_spenden");
    $pdo->exec("CREATE VIEW v_schwimmer_sponsor_spenden AS
        SELECT s.id AS schwimmer_id, s.startnummer, s.vorname AS schwimmer_vorname, s.nachname AS schwimmer_nachname, s.team,
               sp.id AS sponsor_id, sp.name AS sponsor_name,
               ss.betrag_pro_bahn, ss.limit_betrag,
               COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) AS anzahl_bahnen_vormittag,
               COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0) AS anzahl_bahnen_nachmittag,
               COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0) AS anzahl_bahnen,
               CASE WHEN (YEAR(CURDATE()) - s.geburtsjahr) < 14 THEN 25 ELSE 50 END AS bahnenlaenge_m,
               COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0) AS anzahl_bahnen_roh,
               (COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0)) * ss.betrag_pro_bahn AS spendenbetrag_roh,
               LEAST(
                   (COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0)) * ss.betrag_pro_bahn,
                   IFNULL(ss.limit_betrag, (COALESCE(SUM(sl.anzahl_bahnen_vormittag), 0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag), 0)) * ss.betrag_pro_bahn)
               ) AS spendenbetrag
        FROM schwimmer s
        JOIN schwimmer_sponsoren ss ON ss.schwimmer_id = s.id
        JOIN sponsoren sp ON sp.id = ss.sponsor_id
        LEFT JOIN schwimmleistungen sl ON sl.schwimmer_id = s.id
        GROUP BY s.id, s.startnummer, s.vorname, s.nachname, s.team, sp.id, sp.name, ss.betrag_pro_bahn, ss.limit_betrag");
    log_step($results, 'View v_schwimmer_sponsor_spenden', true, 'aktualisiert');

    $pdo->exec("DROP VIEW IF EXISTS v_hauptsponsor_spenden");
    $pdo->exec("CREATE VIEW v_hauptsponsor_spenden AS
        SELECT hs.id, sp.id AS sponsor_id, sp.name AS sponsor_name, hs.betrag_pro_bahn, hs.limit_betrag,
               (SELECT COALESCE(SUM(sl.anzahl_bahnen_vormittag),0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag),0) FROM schwimmleistungen sl) AS gesamt_bahnen,
               (SELECT COALESCE(SUM(sl.anzahl_bahnen_vormittag),0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag),0) FROM schwimmleistungen sl) * hs.betrag_pro_bahn AS spendenbetrag_roh,
               LEAST(
                   (SELECT COALESCE(SUM(sl.anzahl_bahnen_vormittag),0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag),0) FROM schwimmleistungen sl) * hs.betrag_pro_bahn,
                   IFNULL(hs.limit_betrag, (SELECT COALESCE(SUM(sl.anzahl_bahnen_vormittag),0) + COALESCE(SUM(sl.anzahl_bahnen_nachmittag),0) FROM schwimmleistungen sl) * hs.betrag_pro_bahn)
               ) AS spendenbetrag
        FROM hauptsponsoren hs
        JOIN sponsoren sp ON sp.id = hs.sponsor_id");
    log_step($results, 'View v_hauptsponsor_spenden', true, 'aktualisiert');

    $pdo->exec("DROP VIEW IF EXISTS v_gesamtspenden_hauptsponsoren");
    $pdo->exec("CREATE VIEW v_gesamtspenden_hauptsponsoren AS
        SELECT COALESCE(SUM(spendenbetrag), 0) AS gesamtspenden_hauptsponsoren
        FROM v_hauptsponsor_spenden");
    log_step($results, 'View v_gesamtspenden_hauptsponsoren', true, 'aktualisiert');

} catch (PDOException $e) {
    $error = $e->getMessage();
}

$page_title = "Datenbank-Einrichtung";
?>

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo $base_url; ?>"><i class="fas fa-home"></i></a></li>
                <li class="breadcrumb-item active"><i class="fas fa-database"></i> Datenbank-Einrichtung</li>
            </ol>
        </nav>
        <h1 class="page-header"><i class="fas fa-database"></i> Datenbank-Einrichtung / Migration</h1>
        <p class="text-muted">Richtet das Schema für Vormittag-/Nachmittag-Durchläufe und Startnummern ein. Diese Schritte können gefahrlos wiederholt werden.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?php echo escapeOutput($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header"><i class="fas fa-tasks"></i> Ergebnisse</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr><th>Schritt</th><th>Status</th><th>Hinweis</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td><?php echo escapeOutput($r['label']); ?></td>
                            <td>
                                <?php if ($r['ok']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> OK</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Fehler</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?php echo escapeOutput($r['detail']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            <a href="<?php echo $base_url; ?>" class="btn btn-outline-secondary"><i class="fas fa-home"></i> Zur Startseite</a>
            <a href="database_setup.php" class="btn btn-primary"><i class="fas fa-redo"></i> Erneut ausführen</a>
        </div>
    </div>
</div>

<?php
$additional_scripts = [
    $base_url . 'js/main.js'
];
require_once __DIR__ . '/includes/footer.php';
