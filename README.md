# 📺 ZIB1 KI-Summarizer & Dashboard

Dieses Projekt automatisiert die Analyse der **ZIB1 (Zeit im Bild)**. Mithilfe von Künstlicher Intelligenz werden die Untertitel der Sendung extrahiert, zusammengefasst und für den Nutzer interaktiv aufbereitet. Ergänzt wird die Applikation durch ein Dashboard mit aktuellen Wirtschaftsdaten für Österreich.

**Hinweis:** Die Website läuft lokal

---

## ✨ Hauptfunktionen

* **KI-Zusammenfassung:** Automatische Extraktion und Kürzung der täglichen ZIB1-Inhalte.
* **Interaktiver Chat:** Stelle spezifische Fragen zu den Nachrichten oder lass dir komplexe Themen allgemein von der KI erklären.
* **Wirtschafts-Monitoring:** Live-Daten auf einen Blick:
    * **Goldpreis** (aktueller Marktwert)
    * **Ölpreis** (Brent Crude)
    * **Inflationsrate** (aktuellster Monatswert für Österreich)
* **Daten-Management:**
    * Archivierung aller Zusammenfassungen in einer Datenbank.
    * **PDF-Export:** Lade Zusammenfassungen für die Dokumentation herunter.
    * Löschfunktion für die Datenbankpflege.

## Installation

* **Git Repository Klonen:** Klone das Git-Repository in einen beliebigen Ordner
* **Env Datei einrichten:** Bennene die '.env.example' Datei in '.env' um
* **API-Key erstellen:** Erstelle deinen Key auf 'https://aistudio.google.com/app/apikey?hl=de'
* **API-Key einfügen:** gehe in die '.env' Datei und füge bei 'AI_API_KEY=' deinen Key ein
* **Befehle ausführen:**
    * **Composer installieren:** ```composer install```
    * **Npm installieren:** ```npm install```
    * **App-Key generieren:** ```php artisan key:generate```
    * **Datenbank erstellen:** ```New-Item -Path "database\database.sqlite" -ItemType File```
    * **Migrieren:** ```php artisan migrate```
* **Website starten:** Füge die Website bei Herd hinzu -> gib den Befehl ```npm run dev``` ein

## Easy Website start
* **ZIB.bat:** Öffne die Datei 'ZIB.bat' und füge die notwendigen Pfade, sowie den Link ein
* **Starten:** Jetzt kann die Website mit einem einfachen Doppelklick auf die Datei gestartet werden

## ⚖️ Disclaimer
Dieses Tool dient zu Informationszwecken. Die Daten der Untertitel sind urheberrechtlich geschütztes Eigentum des ORF. Dieses Projekt steht in keiner offiziellen Verbindung zum ORF.
