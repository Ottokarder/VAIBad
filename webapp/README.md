# VAIBad - Datenverwaltungssystem

Ein webbasiertes Datenverwaltungssystem für das VAIBad zur Verwaltung von Schwimmern, Sponsoren und Schwimmleistungen.

## Installation

### Voraussetzungen
- Webserver (Apache, Nginx)
- PHP 8.0 oder höher
- MySQL/MariaDB 10.0 oder höher
- PDO-Erweiterung für PHP

### Schritte

1. **Dateien hochladen**
   - Laden Sie alle Dateien aus dem `webapp`-Verzeichnis auf Ihren Webserver hoch
   - Stellen Sie sicher, dass das Verzeichnis `uploads/` beschreibbar ist

2. **Datenbank erstellen**
   - Erstellen Sie eine neue MySQL-Datenbank
   - Importieren Sie die SQL-Datei `vaibad_database.sql`

3. **Konfiguration anpassen**
   - Bearbeiten Sie die Datei `config.php`
   - Tragen Sie Ihre Datenbank-Zugangsdaten ein:
     ```php
     $host = 'localhost';
     $dbname = 'vaibad';
     $username = 'IHR_BENUTZERNAME';
     $password = 'IHR_PASSWORT';
     ```

4. **Basis-URL anpassen**
   - Passen Sie die Basis-URL in `config.php` an:
     ```php
     $base_url = '/webapp/';
     ```

5. **Zugriffsrechte setzen**
   ```bash
   chmod -R 755 /pfad/zur/webapp/
   chmod -R 777 /pfad/zur/webapp/uploads/
   ```

6. **Webanwendung aufrufen**
   - Öffnen Sie Ihren Browser und navigieren Sie zu der installierten URL
   - Beispiel: `http://Ihre-Domain.de/webapp/`

## Funktionen

### Datenverwaltung
- **Schwimmer verwalten**: Hinzufügen, Bearbeiten, Löschen von Schwimmern
- **Sponsoren verwalten**: Hinzufügen, Bearbeiten, Löschen von Sponsoren
- **Schwimmleistungen verwalten**: Erfassung der geschwommenen Bahnen
- **Schwimmer-Sponsoren-Verknüpfungen**: Zuordnung von Sponsoren zu Schwimmern
- **Hauptsponsoren verwalten**: Spezielle Spendenregelungen für Hauptsponsoren

### Auswertungen
- **Schwimmer-Statistiken**: Strecken, Spenden, Teams, Altersgruppen
- **Sponsoren-Statistiken**: Spendenübersicht, Vergleich Hauptsponsoren vs. normale Sponsoren
- **Gesamtübersicht**: Komplette Übersicht aller Daten und Statistiken

### Datenbank
- **Import/Export**: SQL-Dateien importieren und Datenbank exportieren
- **Backup**: Einfaches Backup der Datenbank

## Datenbank-Struktur

Die Datenbank besteht aus folgenden Tabellen:

- `schwimmer`: Schwimmer-Daten (Vorname, Nachname, Geburtsjahr, Team)
- `sponsoren`: Sponsoren-Daten (Name, Hauptsponsor-Flag)
- `schwimmleistungen`: Schwimmleistungen (Schwimmer-ID, Anzahl Bahnen, Datum)
- `schwimmer_sponsoren`: Verknüpfung zwischen Schwimmern und Sponsoren (Betrag pro Bahn, Limit)
- `hauptsponsoren`: Hauptsponsoren-Einstellungen (Sponsor-ID, Betrag pro Bahn, Limit)

### Views
- `v_schwimmer_gesamtstrecke`: Gesamtstrecke pro Schwimmer
- `v_schwimmer_gesamtspenden`: Gesamtspenden pro Schwimmer
- `v_schwimmer_sponsor_spenden`: Detaillierte Spenden pro Schwimmer und Sponsor
- `v_hauptsponsor_spenden`: Spenden der Hauptsponsoren
- `v_gesamtspenden_hauptsponsoren`: Gesamtspenden aller Hauptsponsoren

## Berechnungen

### Bahnlänge
- Schwimmer unter 14 Jahren: 25 Meter pro Bahn
- Schwimmer ab 14 Jahren: 50 Meter pro Bahn

### Spendenberechnung
- **Normale Sponsoren**: Spendenbetrag = Anzahl Bahnen × Betrag pro Bahn
- **Mit Limit**: Spendenbetrag = min(Anzahl Bahnen × Betrag pro Bahn, Limit)
- **Hauptsponsoren**: Spendenbetrag = min(Gesamtbahnen aller Schwimmer × Betrag pro Bahn, Limit)

## Sicherheit

- **Formularvalidierung**: Client- und Serverseitige Validierung
- **SQL-Injection-Schutz**: Verwendung von Prepared Statements
- **XSS-Schutz**: HTML-Specialchars für Ausgaben
- **CSRF-Schutz**: (Kann in zukünftigen Versionen implementiert werden)

## Technische Details

### Frameworks und Bibliotheken
- **Bootstrap 5**: CSS-Framework für responsives Design
- **jQuery**: JavaScript-Bibliothek
- **Font Awesome**: Icons
- **DataTables**: Tabellen mit Such- und Sortierfunktionen

### Browser-Kompatibilität
- Chrome (empfohlen)
- Firefox
- Safari
- Edge
- Internet Explorer 11 (eingeschränkt)

## Fehlerbehebung

### Häufige Probleme

1. **Datenbankverbindung fehlgeschlagen**
   - Überprüfen Sie die Zugangsdaten in `config.php`
   - Stellen Sie sicher, dass der MySQL-Server läuft

2. **Dateien können nicht hochgeladen werden**
   - Überprüfen Sie die Berechtigungen des `uploads/`-Verzeichnisses
   - Stellen Sie sicher, dass PHP das Hochladen von Dateien erlaubt

3. **Stile werden nicht geladen**
   - Überprüfen Sie die Basis-URL in `config.php`
   - Stellen Sie sicher, dass die Pfade zu CSS- und JS-Dateien korrekt sind

4. **Fehler beim Importieren von SQL-Dateien**
   - Stellen Sie sicher, dass die SQL-Datei gültig ist
   - Überprüfen Sie die Berechtigungen der Datenbank

## Support

Bei Fragen oder Problemen:
- Überprüfen Sie die README.md-Datei
- Überprüfen Sie die Fehlerprotokolle
- Kontaktieren Sie den Administrator

## Lizenz

Dieses Projekt ist für den internen Gebrauch bestimmt.

## Version

1.0.0 - Erstversion
