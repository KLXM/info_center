# REDAXO Info Center AddOn

🎯 **Intelligent Dashboard für REDAXO CMS** 

## 📋 Überblick

Das **Info Center** ist ein intelligentes Dashboard-System für REDAXO CMS, das wichtige Informationen und Quick-Actions direkt im Backend und Frontend bereitstellt. Es bietet sowohl vorgefertigte Widgets als auch einen revolutionären **Widget Builder** für benutzerdefinierte Dashboards.

---

## ✨ Hauptfeatures

### 🚀 **Intelligente Widgets**
- **📊 Performance Stats** - Ladezeiten, Speicherverbrauch, DB-Queries
- **⏱️ TimeTracker** - Arbeitszeit-Tracking mit Pausen und Berichten
- **📝 Artikel Widget** - Zuletzt bearbeitete Inhalte mit direkten Links
- **🔗 URL Widget** - URL2/YRewrite Datensätze verwalten
- **🛠️ Upkeep Integration** - Wartungs-Status auf einen Blick
- **⚙️ System-Informationen** - Server-Status und PHP-Infos

### 🎨 **Revolutionärer Widget Builder**
- **🔧 No-Code Widgets** - Erstelle eigene Widgets ohne Programmierung
- **📊 YForm Integration** - Beliebige YForm-Tabellen als Widgets
- **🎯 Feldauswahl** - Wähle spezifische Felder oder lass das System entscheiden
- **🔍 Erweiterte Filter** - SQL-basierte Filterung für präzise Ergebnisse
- **🔗 Flexible Verlinkung** - YForm, URL Addon, YRewrite oder Custom URLs
- **👁️ Sichtbarkeits-Control** - Backend-only, Frontend-only oder beide
- **🌍 Mehrsprachig** - Deutsch und Englisch vollständig unterstützt

### 🎛️ **Intelligente Anzeige**
- **📱 Responsive Design** - Optimal auf allen Geräten
- **🌙 Dark/Light Mode** - Automatisch oder manuell wählbar
- **📍 Flexible Positionierung** - Links oder rechts positionierbar
- **👤 Benutzer-spezifisch** - Individuelle Widget-Konfiguration pro User
- **🔄 Auto-Refresh** - Intelligente Aktualisierung der Daten

---

## 🛠️ Installation

### Voraussetzungen
- **REDAXO 5.18+**
- **PHP 8.1+**
- **YForm AddOn** (für Widget Builder)

### Setup
1. **AddOn hochladen** und installieren
2. **Berechtigungen setzen** (siehe Konfiguration)
3. **Widgets konfigurieren** unter `AddOns → Info Center → Einstellungen`
4. **Custom Widgets erstellen** mit dem Widget Builder

---

## 📖 Widget Builder - Detailguide

### 🎯 **Was ist der Widget Builder?**
Der Widget Builder ist ein **No-Code Tool**, mit dem Administratoren eigene Dashboard-Widgets erstellen können - ohne eine Zeile Code zu schreiben!

### 🚀 **Widgets erstellen - Step by Step**

#### 1. **Grundkonfiguration**
```
✅ Widget-Name vergeben
✅ YForm-Tabelle auswählen
✅ Anzahl der Datensätze festlegen (1-50)
```

#### 2. **Feldauswahl** (automatisch)
```
🔄 Felder werden automatisch nach Tabellen-Auswahl geladen
✅ Spezifische Felder auswählen oder alle verwenden
📋 System-Felder werden automatisch erkannt
```

#### 3. **Erweiterte Filterung**
```sql
-- Beispiele für Filter-Bedingungen:
status = 1
createdate >= CURDATE() - INTERVAL 30 DAY
status = 1 AND email LIKE '%@company.com'
```

#### 4. **Verlinkungsoptionen**
- **🔗 YForm Links** - Direkte Bearbeitung (empfohlen)
- **🎨 URL Addon** - Saubere URLs mit Schema
- **📝 YRewrite** - Strukturierte URL-Generierung
- **⚙️ Custom URLs** - Freie Platzhalter: `{id}`, `{name}`, etc.
- **🚫 Keine Links** - Nur Anzeige

#### 5. **Sichtbarkeits-Control**
- **🌐 Backend + Frontend** - Überall sichtbar
- **🖥️ Nur Backend** - Nur im REDAXO Backend
- **🌍 Nur Frontend** - Nur im Frontend für Backend-User

### 💡 **Widget Builder Features**

#### 🔒 **Automatische Sicherheit**
- **CSRF-Schutz** - Automatische Token-Generierung
- **Session-Management** - Frontend-Backend-Verknüpfung
- **Berechtigung-Prüfung** - Sichere Zugriffskontrolle

#### 🎨 **Intelligente Darstellung**
- **Artikel-Style** - Wie "Zuletzt bearbeitet" Widget
- **Meta-Informationen** - Zusätzliche Feld-Anzeige
- **Auto-ID-Loading** - Automatische ID-Extraktion für Links
- **Responsive Layout** - Optimal auf allen Geräten

#### 🌍 **Vollständige Mehrsprachigkeit**
- **Deutsche Oberfläche** - Komplette deutsche Übersetzung
- **English Interface** - Full English translation support
- **Dynamic Localization** - Automatische Spracherkennung

---

## ⚙️ Konfiguration

### 🎛️ **Globale Einstellungen**
```yaml
# Position des Info Centers
position: right  # oder 'left'

# Theme-Modus  
darkmode: auto   # 'auto', 'light', 'dark'

# Widget-Aktivierung
widgets:
  article: { enabled: true, prio: 1 }
  timetracker: { enabled: true, prio: 0 }
  stats: { enabled: true, prio: 5 }
  # ... weitere Widgets
```

### 👤 **Benutzer-spezifische Einstellungen**
Jeder Benutzer kann individuell konfigurieren:
- ✅ Welche Widgets angezeigt werden
- ✅ Widget-Reihenfolge anpassen
- ✅ Persönliche Präferenzen

### 🔐 **Berechtigungen**
```yaml
permissions:
  info_center: "Info Center anzeigen"
  info_center[config]: "Info Center konfigurieren"  
  info_center[admin]: "Widget Builder verwenden"
```

---

## 🎨 Frontend-Integration

### 🌍 **Automatische Frontend-Anzeige**
- **Backend-User im Frontend** - Automatische Erkennung
- **Session-Verknüpfung** - Nahtlose Backend-Integration
- **Responsive Design** - Optimal auf allen Geräten
- **Asset-Management** - Automatisches CSS/JS Loading

### 📱 **Responsive Verhalten**
```css
/* Automatische Anpassung */
@media (max-width: 768px) {
  /* Mobile Optimierung */
}

@media (min-width: 1200px) {
  /* Desktop Volldarstellung */
}
```

---

## 🔧 Entwickler-Informationen

### 📦 **Architektur**
```
src/addons/info_center/
├── lib/
│   ├── InfoCenter.php          # Hauptklasse
│   ├── AbstractWidget.php      # Widget-Basisklasse
│   ├── api_widget_builder.php  # AJAX-API
│   └── Widgets/
│       ├── ArticleWidget.php
│       ├── TimeTrackerWidget.php
│       ├── CustomListWidget.php # Widget Builder Ausgabe
│       └── ...
├── pages/
│   ├── config.php             # Einstellungen
│   └── widget_builder.php     # Widget Builder Interface
├── assets/
│   ├── css/info-center.css    # Styling
│   └── js/info-center.js      # JavaScript
└── lang/
    ├── de_de.lang            # Deutsche Übersetzung
    └── en_gb.lang            # Englische Übersetzung
```

### 🔌 **Eigene Widgets entwickeln**
```php
<?php
namespace KLXM\InfoCenter\Widgets;

use KLXM\InfoCenter\AbstractWidget;

class MyCustomWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct(); 
        $this->title = 'Mein Widget';
        $this->priority = 10;
    }
    
    public function render(): string
    {
        $content = '<div>Mein Widget Content</div>';
        return $this->wrapContent($content);
    }
}
```

### 📡 **API Integration**
```javascript
// Widget Builder AJAX
fetch('/redaxo/index.php?rex-api-call=widget_builder&action=get_table_fields&table_name=my_table')
  .then(response => response.text())
  .then(html => {
    // Feldliste verarbeiten
  });
```

---

## 🌍 Mehrsprachigkeit

### 🇩🇪 **Deutsche Lokalisierung**
- ✅ Vollständige deutsche Übersetzung
- ✅ Technische Begriffe korrekt übersetzt
- ✅ Kontext-sensitive Hilfen
- ✅ Inline-Dokumentation

### �🇧 **English Localization**
- ✅ Complete English translation
- ✅ Professional terminology
- ✅ Context-aware help texts
- ✅ Inline documentation

### 🔄 **Automatische Spracherkennung**
Das System erkennt automatisch die REDAXO Backend-Sprache und passt die Oberfläche entsprechend an.

---

## 🚀 Performance & Sicherheit

### ⚡ **Performance-Optimierung**
- **🔄 Lazy Loading** - Widgets werden nur bei Bedarf geladen
- **💾 Caching** - Intelligente Zwischenspeicherung
- **📦 Asset-Optimierung** - Minimierte CSS/JS-Dateien
- **🎯 Selective Loading** - Nur aktive Widgets werden geladen

### 🔒 **Sicherheitsfeatures**
- **🛡️ CSRF-Schutz** - Automatische Token-Validierung
- **👤 Berechtigungsprüfung** - Mehrstufiges Rechtesystem
- **🔐 Session-Sicherheit** - Sichere Frontend-Backend-Verknüpfung
- **🚫 Input-Validierung** - SQL-Injection-Schutz

---

## 🎯 Use Cases

### 📊 **Für Redakteure**
- **⏱️ Arbeitszeit tracken** mit TimeTracker
- **📝 Schneller Zugriff** auf zuletzt bearbeitete Artikel
- **🔍 Dashboard-Übersicht** über wichtige Inhalte

### 👨‍💼 **Für Projektmanager**
- **📈 Performance-Monitoring** mit Stats Widget
- **📋 Custom Dashboards** mit Widget Builder
- **👥 Team-Übersicht** mit benutzerdefinierten Widgets

### 🛠️ **Für Entwickler**
- **⚙️ System-Monitoring** mit technischen Widgets
- **🔧 Debug-Informationen** auf einen Blick
- **🎨 Erweiterbare Architektur** für eigene Widgets

### 🏢 **Für Agenturen**
- **👥 Kunden-spezifische Dashboards** erstellen
- **📊 Projekt-Monitoring** mit Custom Widgets
- **🎯 Maßgeschneiderte Lösungen** ohne Programmierung

---

## 🤝 Support & Community

### 📚 **Dokumentation**
- **GitHub Repository** - [https://github.com/klxm/info_center](https://github.com/klxm/info_center)
- **REDAXO Slack** - #addons Channel
- **Community Forum** - REDAXO.org

### 🐛 **Bug Reports & Features**
- **GitHub Issues** für Bug-Reports
- **Feature Requests** via GitHub Discussions
- **Pull Requests** willkommen!

---

## 📄 Lizenz

MIT License - Siehe [LICENSE.md](LICENSE.md) für Details.

## 👨‍💻 Autor

**KLXM Crossmedia GmbH**  
Entwickelt für REDAXO CMS - Das intelligente Dashboard-System für moderne Webentwicklung.

---

**🎉 Viel Spaß mit dem Info Center - Ihrem intelligenten REDAXO Dashboard!**

## Description

This AddOn provides an integrated file browser with code editor for REDAXO CMS. It enables direct file editing through the backend interface for **emergency situations only**.

**FUNCTIONALITY SCOPE:**
- ✅ **Edit existing** files for quick fixes
- ✅ **Virtual trash system** with restore functionality  
- ✅ **Protected system files** (automatic prevention)
- ✅ **Automatic self-deactivation** after 2 days of inactivity
- 🛠️ **Exclusively** for emergency repairs

## ⚠️ Security & Usage Warnings

**CRITICAL:** This AddOn should **ONLY be used in emergencies**!

### 🔒 **Security Features:**
- ✅ **Admin access only** - restricted to administrators
- ✅ **Protected system files** - prevents deletion of critical files
- ✅ **Auto-deactivation** - deactivates after 2 days of inactivity
- ✅ **Data cleanup** - removes backups and trash on deactivation
- ✅ **Virtual trash system** - deleted files can be restored

### ⚠️ **Usage Guidelines:**
- 🚫 **Production environment:** Never leave activated on live servers
- � **Emergency only:** Use only for critical bug fixes
- 💾 **Backup first:** Always backup your site before using
- ⏱️ **Time limit:** Addon automatically deactivates after 2 days inactivity
- �️ **Clean exit:** All data is automatically cleaned up on deactivation

### 🏆 **Best Practices:**
1. **Use proper development setup locally**
2. **Deploy changes through established workflows**  
3. **Keep this addon for true emergencies only**
4. **Test changes in staging environment first**
5. **Document any emergency changes made**

## Features

### 🛠️ **Emergency Tools:**
- **File Browser** with NextCloud-style interface
- **Monaco Editor** (VS Code) - **local installation** (no CDN!)
- **Fullscreen mode** for focused editing (F11 or button)
- **Live full-text search** in project files
- **Line navigation** from search results
- **Offline capable** - works without internet connection

### 🔐 **Safety Features:**
- **Automatic backups** on every file modification
- **Virtual trash system** - restore accidentally deleted files
- **Protected system files** - prevents deletion of critical files
- **Backup management** with restore and cleanup functions
- **Auto-deactivation** after 2 days of inactivity
- **Data cleanup** on deactivation

### 📝 **File Operations:**
- ✅ **Edit existing files** (with syntax highlighting)
- ✅ **Delete files** (to virtual trash - can be restored)
- ✅ **Restore files** from trash
- ❌ **No file creation** - use proper IDE for development
- 🔒 **System file protection** (index.php, .htaccess, config files, etc.)

## Installation

⚠️ **Before Installation:** Ensure you have a proper local development setup for regular work!

1. **Upload AddOn** to REDAXO
2. **Monaco Editor Setup**: 
   ```bash
   cd src/addons/code/assets
   npm install
   npm run build
   ```
3. **Install and activate** the AddOn
4. **Use ONLY for emergency repairs**
5. **Addon will auto-deactivate** after 2 days of inactivity

## Usage Workflow

### 🚨 **Emergency Situation:**
1. **Backup your site first** (always!)
2. **Activate the AddOn** (if not already active)
3. **Make minimal necessary changes** only
4. **Test the fix immediately**
5. **Document what you changed**
6. **Let the AddOn auto-deactivate** (or deactivate manually)
7. **Implement proper fix in your development environment**
8. **Deploy through your normal workflow**

### 🏠 **For Regular Development:**
- **Use local development environment** (Docker, XAMPP, etc.)
- **Use professional IDE** (VS Code, PhpStorm, etc.)
- **Use version control** (Git with proper branching)
- **Use deployment tools** (rsync, CI/CD, etc.)
- **Test in staging environment**

## System Requirements

- **REDAXO 5.18+**
- **PHP 8.1+**
- **Node.js & NPM** (for Monaco Editor setup)
- **Administrator permissions**
- **Local development environment** (for regular work)

## Configuration

The AddOn can be configured via `package.yml`:

```yaml
config:
    auto_deactivate_after_days: 2    # Auto-deactivation after inactivity
    cleanup_data_on_deactivate: true # Clean backups/trash on deactivation
```

## Auto-Deactivation System

- **Automatic deactivation** after 2 days of inactivity (configurable)
- **Silent operation** - no warnings or notifications
- **Data cleanup** - backups and trash are automatically removed
- **Secure cleanup** - all addon data is removed on deactivation

## Why This Approach?

### 🚨 **Emergency Tool Philosophy:**
This AddOn is designed as a "fire extinguisher" - always available when needed, but not for daily use.

### 🏠 **Proper Development Setup:**
- **Local environment** gives you full control and safety
- **Version control** prevents data loss and enables collaboration  
- **Professional IDEs** provide better debugging, autocomplete, and tools
- **Staging environments** let you test safely before going live
- **Deployment automation** reduces human error

### ⚖️ **Risk vs. Benefit:**
- **High risk:** Direct server file editing
- **High benefit:** Quick emergency fixes when other tools unavailable
- **Mitigation:** Auto-deactivation, backups, protected files, trash system

---

**Remember: Great power comes with great responsibility. Use this tool wisely!** 🦸‍♂️

## Warning Against Misuse

This tool is **exclusively** for:
- ✅ Emergency repairs (editing only!)
- ✅ Quick bug fixes of existing files
- ✅ Development/debugging (local only)

**NOT for:**
- ❌ Regular development
- ❌ Production editing
- ❌ Permanent installation
- ❌ Creating or deleting files

## Disclaimer

Use at your own risk. Always create complete backups before using this tool.

## License

This AddOn is licensed under the MIT License. See [LICENSE.md](LICENSE.md) for details.

## Author
KLXM Crossmedia GmbH

Developed for REDAXO CMS - Emergency tool for administrators
