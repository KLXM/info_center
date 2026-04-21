# REDAXO Info Center AddOn

🎯 **Intelligent Dashboard für REDAXO CMS** 

## 📋 Überblick

Das **Info Center** ist ein intelligentes Dashboard-System für REDAXO CMS, das wichtige Informationen und Quick-Actions direkt im Backend und Frontend bereitstellt. Es bietet sowohl vorgefertigte Widgets als auch einen revolutionären **Widget Builder** für benutzerdefinierte Dashboards.

---

## ✨ Hauptfeatures

### 🚀 **Intelligente Widgets**
- **🔍 Search Widget** - Livr-Suche mit Quick Actions
- **🏗️ Struktur-Navigation** - Interaktiver Kategoriebaum mit Lazy Loading, Domain-Filterung und direkten Frontend-Links
- **📊 Performance Stats** - Ladezeiten, Speicherverbrauch, DB-Queries
- **⏱️ TimeTracker** - Arbeitszeit-Tracking mit Pausen und Berichten
- **📝 Artikel Widget** - Zuletzt bearbeitete Inhalte mit direkten Links
- **🔗 URL Widget** - URL2/YRewrite Datensätze verwalten
- **🛠️ Upkeep Integration** - Wartungs-Status auf einen Blick
- **⚙️ System-Informationen** - Server-Status und PHP-Infos

### 🎨 **Widget Builder**
- **🔧 No-Code Widgets** - Erstelle eigene Widgets ohne Programmierung
- **📊 YForm Integration** - Beliebige YForm-Tabellen als Widgets
- **🎯 Feldauswahl** - Wähle spezifische Felder oder lass das System entscheiden
- **🔍 Erweiterte Filter** - SQL-basierte Filterung für präzise Ergebnisse
- **🔗 Flexible Verlinkung** - YForm, URL Addon, YRewrite oder Custom URLs
- **👁️ Sichtbarkeits-Control** - Backend-only, Frontend-only oder beide
- **🌍 Mehrsprachig** - Deutsch und Englisch vollständig unterstützt

### 🎛️ **Intelligente Anzeige**
- **📱 Responsive Design** - Optimal auf allen Geräten
- **🖐️ Drag & Drop Sortierung** - Widgets einfach per Drag & Drop verschieben (benutzerspezifisch gespeichert)
- **⌨️ Keyboard-Sortierung** - Widgets per **Alt+↑/↓** (Mac: Option+↑/↓) verschieben – funktioniert nicht in Textfeldern
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

## 🏗️ Struktur-Navigation Widget

### 🎯 **Was ist die Struktur-Navigation?**
Ein **interaktiver Kategoriebaum** direkt im Info Center - perfekt für schnelle Navigation durch komplexe REDAXO-Strukturen!

### ✨ **Hauptfeatures**

#### 📂 **Intelligenter Kategoriebaum**
- **Hierarchische Darstellung** - Kategorien und Artikel in Baumstruktur
- **Akkordeon-Navigation** - Ein-/Ausklappbare Kategorien
- **Auto-Expansion** - Automatisches Öffnen zur aktuellen Position
- **Sortierung** - Unterkategorien zuerst, dann Artikel
- **Status-Indikatoren** - Farbcodierung (REDAXO Blau=Online, Grau=Offline, Rot=Gesperrt)

#### 👁️ **Quick Actions auf Hover**
- **Frontend-Preview** - Direkter Link zur Seite im Frontend (👁️ Auge-Icon)
- **Backend-Edit** - Schneller Zugriff auf Bearbeitung (✏️ Stift-Icon)
- **Target="_blank"** - Frontend-Links öffnen in neuem Tab
- **Smooth Transitions** - Butterweiche Animationen beim Hover
- **Kompaktes Layout** - Optimale Platznutzung
- **Für alle Elemente** - Kategorien UND Artikel haben beide Buttons

#### 🔍 **Live-Suche**
- **Echtzeit-Filter** - 300ms Debounce für Performance
- **Smart-Matching** - Suche in Namen und IDs
- **Parent-Expansion** - Gefundene Elemente werden automatisch angezeigt
- **Highlight-Effekt** - Suchergebnisse werden hervorgehoben

#### 🌐 **Multi-Domain Support** (YRewrite)
- **Domain-Switcher** - Automatisch bei > 1 Domain mit Mountpoint
- **Filter nach Domain** - Zeige nur Struktur einer Domain
- **"Alle Domains"** - Standard-Ansicht zeigt alles
- **Intelligente Erkennung** - Nur echte Domains, keine Default-Entries

#### 🎨 **Moderne UI**
- **Glassmorphism Design** - Moderne Optik mit Backdrop-Blur
- **Smooth Transitions** - Butterweiche Animationen (cubic-bezier)
- **Hover-Effekte** - Action-Buttons erscheinen bei Hover
- **Dark/Light Mode** - Automatische Theme-Anpassung
- **Responsive** - Perfekt auf allen Bildschirmgrößen
- **Keine IDs sichtbar** - Nur bei Hover im Tooltip (saubere UI)

#### ⚡ **Performance**
- **Lazy Loading** - API-basiertes Laden großer Strukturen (ab 100 Kategorien)
- **Smart Caching** - Intelligente Refresh-Logik
- **Debounced Search** - Optimierte Suche ohne Server-Last
- **Permission-Check** - Respektiert REDAXO Benutzerrechte
- **Progressive Enhancement** - Lädt nur sichtbare Elemente bei Bedarf

### 🎯 **Verwendung**

#### Hover für Actions
- **👁️ Frontend ansehen** - Linker Button öffnet Seite im neuen Tab
- **✏️ Backend bearbeiten** - Rechter Button öffnet Editor
- **Tooltips** - Zeigt Domain und ID bei Hover
- **Für alle Elemente** - Kategorien UND Artikel haben beide Buttons

#### Direkte Links
- **Kategorie-Klick** - Öffnet Struktur-Seite
- **Artikel-Klick** - Öffnet Artikel im Edit-Modus
- **Frontend-Button** - Öffnet mit YRewrite oder rex_getUrl()

#### Suche & Filter
- **Suchfeld** - Live-Suche in Kategorien und Artikeln
- **Domain-Dropdown** - Filter nach YRewrite-Domain (bei Multi-Domain)
- **Status-Filter** - Visuell nach Online/Offline/Gesperrt

### 🆕 **Statusfarben**
- **REDAXO Blau (#4b9ad9)** - Online/Veröffentlicht
- **Grau (#6c757d)** - Offline/Unveröffentlicht
- **Rot (#ef4444)** - Gesperrt/Status 2

---

## 🔍 Search Widget

### 🎯 **Was ist das Search Widget?**
Eine **Spotlight-ähnliche Universalsuche** direkt im Info Center - durchsuche alle wichtigen REDAXO-Inhalte mit einer Eingabe!

### ✨ **Hauptfeatures**

#### 🔎 **Intelligente Suche**
- **Artikel** - Durchsucht Namen, Titel und alle Content-Felder (value1-20)
- **Kategorien** - Findet Kategorien in der Struktur
- **Module** - Sucht in Modul-Namen und Code
- **Templates** - Durchsucht Template-Namen und Code
- **Medien** - Findet Bilder, Videos und andere Dateien im Mediapool

#### 🚀 **Quick Actions wie Spotlight/Alfred**
- **🧮 Rechner** - Direkte Berechnung (z.B. `2+2`, `15*20%`, `(100+50)/2`)
  - Automatisches Kopieren des Ergebnisses in die Zwischenablage
- **📚 Wikipedia** - Suche mit Live-Vorschau (z.B. `wiki REDAXO`)
  - REST API Integration mit 500 Zeichen Vorschau
  - Öffnet vollständigen Artikel bei Klick
- **📖 Wörterbuch** - Definition nachschlagen (z.B. `define CMS`)
  - Wiktionary HTML API Integration mit echter Definition
  - Zeigt erste Definition mit 500 Zeichen Vorschau
- **📝 Blindtext** - Generator für Platzhaltertexte (z.B. `blindtext 500`)
  - 8 verschiedene lustige deutsche Texte (kein Lorem Ipsum!)
  - Automatisches Kopieren in Zwischenablage
  - Intelligenter Schnitt am Satzende
- **🔗 URL-Erkennung** - URLs werden automatisch erkannt und sind direkt öffenbar

#### 🔍 **Erweiterte Filter mit #-Prefix**
- **📅 Datum-Filter**
  - `#modified:today` - Heute geändert
  - `#modified:yesterday` - Gestern geändert
  - `#modified:last-week` - Letzte Woche geändert
  - `#modified:last-month` - Letzter Monat geändert
  - `#modified:2024-12-05` - An bestimmtem Datum geändert
  - `#created:today` - Heute erstellt
  - `#created:last-week` - Letzte Woche erstellt
- **👤 User-Filter**
  - `#author:username` - Erstellt von User
  - `#editor:username` - Bearbeitet von User
- **🎯 Kombinationen**
  - `Startseite #modified:today` - "Startseite" heute geändert
  - `#author:admin #created:last-week` - Von Admin letzte Woche erstellt
  - `#modified:yesterday #editor:admin` - Gestern von Admin bearbeitet

#### 🎨 **Rich Previews**
- **Bild-Thumbnails** - 32x32px Vorschau, 200x200px bei Hover
- **Video-Vorschau** - Tooltip mit Autoplay bei Hover
- **Code-Snippets** - Zeigt Treffer-Kontext bei Modulen/Templates
- **Optimierte Bilder** - Nutzt Media Manager (rex_media_small)

#### ⚡ **Performance & UX**
- **Berechtigungsprüfung** - Respektiert User-Rechte für Struktur/Medien
- **Extension Point** - `INFO_CENTER_SEARCH` für AddOn-Integration
- **Keyboard-Navigation** - ↑↓ für Navigation, Enter zum Öffnen, ESC zum Schließen
- **⌘K Shortcut** - Schneller Zugriff via Tastenkombination
- **Live-Suche** - Sofortige Ergebnisse ohne Reload

### 🎯 **Verwendung**

#### Normale Suche
```
REDAXO         → Findet alle Inhalte mit "REDAXO"
template       → Sucht in Templates und Modulen
logo.png       → Findet Mediendateien
```

#### Quick Actions
```
2+2                      → Rechner: = 4 (kopiert in Zwischenablage)
15*20%                   → Rechner: = 3
wiki Berlin              → Wikipedia mit Live-Vorschau
define CMS               → Wörterbuch-Definition aus Wiktionary
blindtext 500            → Generiert 500 Zeichen deutschen Blindtext
https://...              → URL wird erkannt und kann geöffnet werden
qr  https://             → QRCODE in PNG oder SVG
```

#### Erweiterte Filter
```
#modified:today          → Heute geänderte Inhalte
#created:yesterday       → Gestern erstellte Inhalte
#author:admin            → Von Admin erstellte Inhalte
#editor:username         → Von User bearbeitete Inhalte
Startseite #modified:today  → "Startseite" heute geändert
#author:admin #created:last-week  → Von Admin letzte Woche erstellt
```

#### Keyboard Shortcuts
- **⌘K** oder **Ctrl+K** - Search Widget fokussieren
- **↑/↓** - Durch Ergebnisse navigieren
- **Enter** - Ausgewähltes Ergebnis öffnen
- **ESC** - Suche schließen

### ⚙️ **Konfiguration**
Das Search Widget kann in den Einstellungen aktiviert/deaktiviert werden und unterstützt alle Standard-Widget-Optionen.

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
  search: { enabled: true, prio: -10 }     # Search Widget (NEU!)
  article: { enabled: true, prio: 1 }
  timetracker: { enabled: true, prio: 0 }
  stats: { enabled: true, prio: 5 }
  structure: { enabled: true, prio: 2 }
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
│   ├── InfoCenter.php          # Hauptklasse - Singleton für Widget-Management
│   ├── AbstractWidget.php      # Basisklasse für alle Widgets
│   ├── WidgetInterface.php     # Interface-Definition für Widgets
│   ├── api_widget_builder.php  # AJAX-API für Widget Builder
│   └── Widgets/
│       ├── ArticleWidget.php      # Zuletzt bearbeitete Artikel
│       ├── TimeTrackerWidget.php  # Arbeitszeit-Tracking
│       ├── CustomListWidget.php   # Widget Builder Ausgabe
│       ├── StatsWidget.php        # Performance-Statistiken
│       ├── SystemWidget.php       # System-Informationen
│       ├── UpkeepWidget.php       # Upkeep-Integration
│       └── UrlWidget.php          # URL2/YRewrite-Management
├── pages/
│   ├── config.php             # Konfigurationsseite
│   ├── index.php             # Hauptübersicht
│   └── widget_builder.php     # Widget Builder Interface
├── assets/
│   ├── css/
│   │   ├── info-center.css    # Haupt-Styling
│   │   └── timetracker.css    # TimeTracker-spezifische Styles
│   └── js/
│       ├── info-center.js     # Haupt-JavaScript
│       └── timetracker.js     # TimeTracker-Funktionalität
└── lang/
    ├── de_de.lang            # Deutsche Übersetzungen
    └── en_gb.lang            # Englische Übersetzungen
```

---

## 🎯 Klassen-Referenz

### 📋 **KLXM\InfoCenter\InfoCenter** (Hauptklasse)
Die zentrale Singleton-Klasse für das Widget-Management.

#### Wichtigste Methoden:
```php
// Singleton-Instanz erhalten
InfoCenter::getInstance(): InfoCenter

// Widget registrieren  
registerWidget(WidgetInterface $widget): void

// Alle registrierten Widgets abrufen
getWidgets(): array

// HTML-Output des kompletten Info Centers
get(): string

// Prüfen ob das Info Center gerendert werden soll
shouldRender(): bool
```

### 🔧 **KLXM\InfoCenter\AbstractWidget** (Basisklasse)
Abstrakte Basisklasse für alle Widgets mit Standard-Implementierungen.

#### Geschützte Properties:
```php
protected string $id;           // Eindeutige Widget-ID
protected string $title;        // Widget-Titel
protected float $priority;      // Sortierung (niedrigere Zahl = höher)
protected bool $enabled;        // Aktivierungs-Status
protected array $config;        // Widget-Konfiguration
protected bool $supportsLazyLoading; // Lazy Loading Support
```

#### Wichtigste Methoden:
```php
// Eindeutige Widget-ID (automatisch aus Klassenname)
getId(): string

// Widget-Titel (aus Sprachdatei oder direkt)
getTitle(): string

// Priorität für Sortierung
getPriority(): float
setPriority(float $priority): void

// Aktivierungs-Status
isEnabled(): bool
setEnabled(bool $enabled): void

// Konfiguration
getConfig(string $key = null): mixed
setConfig(array $config): void

// Content in Widget-Container wrappen
wrapContent(string $content, array $attributes = []): string

// Lazy Loading Support
supportsLazyLoading(): bool
getInitialContent(): string  // Muss implementiert werden wenn Lazy Loading
```

### 🔌 **KLXM\InfoCenter\WidgetInterface** (Interface)
Definiert die erforderlichen Methoden für alle Widgets.

```php
interface WidgetInterface
{
    public function getId(): string;
    public function getTitle(): string;
    public function getPriority(): float;
    public function setPriority(float $priority): void;
    public function isEnabled(): bool;
    public function setEnabled(bool $enabled): void;
    public function getConfig(string $key = null): mixed;
    public function setConfig(array $config): void;
    public function render(): string;            // Haupt-Render-Methode
    public function supportsLazyLoading(): bool;
    public function getInitialContent(): string; // Für Lazy Loading
    public function wrapContent(string $content, array $attributes = []): string;
}
```

---

## 🚀 Eigene Widgets entwickeln

### 1️⃣ **Einfaches Widget im Project-AddOn**

#### Schritt 1: Widget-Klasse erstellen
```php
<?php
// Datei: src/addons/project/lib/InfoCenterWidgets/NewsWidget.php

namespace Project\InfoCenterWidgets;

use KLXM\InfoCenter\AbstractWidget;
use rex_i18n;
use rex_sql;
use rex_escape;
use rex_url;

class NewsWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct();
        $this->title = rex_i18n::msg('project_news_widget_title', 'Aktuelle News');
        $this->priority = 15; // Nach System-Widgets, vor Custom Widgets
    }
    
    public function render(): string
    {
        // Daten aus der Datenbank holen
        $sql = rex_sql::factory();
        $sql->setQuery('
            SELECT id, title, createdate, status 
            FROM rex_news 
            WHERE status = 1 
            ORDER BY createdate DESC 
            LIMIT 5
        ');
        
        if (!$sql->getRows()) {
            return $this->wrapContent(
                '<p class="text-muted">' . rex_i18n::msg('project_no_news_found', 'Keine News gefunden') . '</p>'
            );
        }
        
        $content = '<div class="info-center-list">';
        
        foreach ($sql->getArray() as $row) {
            $editUrl = rex_url::backendController([
                'page' => 'content/articles',
                'article_id' => $row['id'],
                'clang' => 1
            ]);
            
            $content .= sprintf(
                '<div class="info-center-list-item">
                    <div class="item-title">
                        <a href="%s" title="%s">%s</a>
                    </div>
                    <div class="item-meta">
                        <span class="date">%s</span>
                        <span class="status status-%s">%s</span>
                    </div>
                </div>',
                $editUrl,
                rex_escape($row['title']),
                rex_escape($row['title']),
                strftime('%d.%m.%Y', $row['createdate']),
                $row['status'],
                $row['status'] ? 'Online' : 'Offline'
            );
        }
        
        $content .= '</div>';
        
        return $this->wrapContent($content, [
            'data-widget' => 'news',
            'class' => 'widget-news'
        ]);
    }
}
```

#### Schritt 2: Widget in Project-AddOn registrieren
```php
<?php
// Datei: src/addons/project/boot.php

// Info Center Widget registrieren (nur wenn Info Center verfügbar ist)
if (rex_addon::get('info_center')->isAvailable()) {
    
    // Widget nach Info Center Boot registrieren
    rex_extension::register('PACKAGES_INCLUDED', function() {
        $infoCenter = \KLXM\InfoCenter\InfoCenter::getInstance();
        
        // News Widget registrieren
        $newsWidget = new \Project\InfoCenterWidgets\NewsWidget();
        $infoCenter->registerWidget($newsWidget);
        
        // Weitere eigene Widgets...
        
    }, rex_extension::LATE);
}
```

#### Schritt 3: Übersetzungen hinzufügen
```php
// Datei: src/addons/project/lang/de_de.lang
project_news_widget_title = Aktuelle News
project_no_news_found = Keine aktuellen News vorhanden

// Datei: src/addons/project/lang/en_gb.lang  
project_news_widget_title = Recent News
project_no_news_found = No recent news available
```

### 2️⃣ **Erweiterte Widgets mit Lazy Loading**

```php
<?php
namespace Project\InfoCenterWidgets;

use KLXM\InfoCenter\AbstractWidget;

class AdvancedStatsWidget extends AbstractWidget
{
    protected bool $supportsLazyLoading = true;
    
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Erweiterte Statistiken';
        $this->priority = 8;
    }
    
    /**
     * Schnell ladender Initial-Content
     */
    public function getInitialContent(): string
    {
        return '<div class="loading-placeholder">Lade Statistiken...</div>';
    }
    
    /**
     * Vollständiger Content (wird via AJAX geladen)
     */
    public function render(): string
    {
        // Aufwändige Berechnungen hier
        $stats = $this->calculateComplexStats();
        
        $content = '<div class="stats-grid">';
        foreach ($stats as $key => $value) {
            $content .= sprintf(
                '<div class="stat-item">
                    <span class="stat-label">%s</span>
                    <span class="stat-value">%s</span>
                </div>',
                rex_escape($key),
                rex_escape($value)
            );
        }
        $content .= '</div>';
        
        return $this->wrapContent($content);
    }
    
    private function calculateComplexStats(): array
    {
        // Komplexe Statistik-Berechnungen...
        return [
            'Besucher heute' => '1.234',
            'Artikel gesamt' => '456',
            // ...
        ];
    }
}
```

### 3️⃣ **Widget-Konfiguration**

```php
<?php
namespace Project\InfoCenterWidgets;

use KLXM\InfoCenter\AbstractWidget;

class ConfigurableWidget extends AbstractWidget
{
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Konfigurierbares Widget';
        $this->priority = 20;
        
        // Standard-Konfiguration
        $this->setConfig([
            'limit' => 10,
            'show_date' => true,
            'category' => 'all'
        ]);
    }
    
    public function render(): string
    {
        $limit = $this->getConfig('limit');
        $showDate = $this->getConfig('show_date');
        $category = $this->getConfig('category');
        
        // Widget-Logik basierend auf Konfiguration...
        
        return $this->wrapContent($content);
    }
}

// Konfiguration in boot.php setzen:
$widget = new \Project\InfoCenterWidgets\ConfigurableWidget();
$widget->setConfig([
    'limit' => 20,
    'show_date' => false,
    'category' => 'news'
]);
$infoCenter->registerWidget($widget);
```

### 4️⃣ **Bedingte Widget-Registrierung**

```php
<?php
// In boot.php - Widgets nur unter bestimmten Bedingungen registrieren

rex_extension::register('PACKAGES_INCLUDED', function() {
    $infoCenter = \KLXM\InfoCenter\InfoCenter::getInstance();
    $user = rex::getUser();
    
    // Widget nur für Administratoren
    if ($user && $user->isAdmin()) {
        $adminWidget = new \Project\InfoCenterWidgets\AdminWidget();
        $infoCenter->registerWidget($adminWidget);
    }
    
    // Widget nur bei vorhandenem YForm
    if (rex_addon::get('yform')->isAvailable()) {
        $yformWidget = new \Project\InfoCenterWidgets\YFormWidget();
        $infoCenter->registerWidget($yformWidget);
    }
    
    // Widget nur für bestimmte Benutzerrollen
    if ($user && $user->hasPerm('project[editor]')) {
        $editorWidget = new \Project\InfoCenterWidgets\EditorWidget();
        $infoCenter->registerWidget($editorWidget);
    }
    
}, rex_extension::LATE);
```

### 💡 **Best Practices für Widget-Entwicklung**

#### ✅ **Do's**
- **Verwende Lazy Loading** für aufwändige Berechnungen
- **Implementiere Caching** für häufig abgerufene Daten
- **Nutze Sprachdateien** für alle Texte
- **Prüfe Berechtigungen** vor der Datenanzeige
- **Escape alle Ausgaben** für Sicherheit
- **Verwende CSS-Klassen** für konsistentes Styling
- **Registriere Widgets in `PACKAGES_INCLUDED`** mit `rex_extension::LATE`

#### ❌ **Don'ts**
- **Keine schweren DB-Queries** im Constructor
- **Nicht ohne Berechtigung** sensible Daten anzeigen
- **Keine direkten rex_sql** Ausgaben ohne Escaping
- **Nicht ohne Fehlerbehandlung** externe APIs aufrufen
- **Keine Widgets direkt in boot.php** registrieren (zu früh)

---

## 📝 Vollständiges Beispiel: Project-AddOn Integration

### Dateistruktur für Project-AddOn:
```
src/addons/project/
├── boot.php                           # Widget-Registrierung
├── lib/
│   └── InfoCenterWidgets/
│       ├── NewsWidget.php             # News-Widget
│       ├── UserStatsWidget.php        # Benutzer-Statistiken
│       └── CustomDashboardWidget.php  # Benutzerdefiniertes Dashboard
├── pages/
│   └── info_center_config.php         # Konfigurationsseite
├── assets/
│   └── info-center-project.css        # Widget-spezifische Styles
└── lang/
    ├── de_de.lang                     # Deutsche Übersetzungen
    └── en_gb.lang                     # Englische Übersetzungen
```

### Komplette boot.php für Project-AddOn:
```php
<?php
// src/addons/project/boot.php

// Info Center Integration (nur wenn verfügbar)
if (rex_addon::get('info_center')->isAvailable()) {
    
    // CSS für eigene Widgets einbinden
    if (rex::isBackend() && rex::getUser()) {
        rex_view::addCssFile($this->getAssetsUrl('info-center-project.css'));
    }
    
    // Widgets nach vollständiger Initialisierung registrieren
    rex_extension::register('PACKAGES_INCLUDED', function() {
        
        $infoCenter = \KLXM\InfoCenter\InfoCenter::getInstance();
        $user = rex::getUser();
        
        if (!$user) return; // Nur für eingeloggte User
        
        // 1. News Widget - für alle Benutzer
        $newsWidget = new \Project\InfoCenterWidgets\NewsWidget();
        $infoCenter->registerWidget($newsWidget);
        
        // 2. User Stats Widget - nur für Admins und Redakteure
        if ($user->hasPerm('project[editor]') || $user->isAdmin()) {
            $userStatsWidget = new \Project\InfoCenterWidgets\UserStatsWidget();
            $infoCenter->registerWidget($userStatsWidget);
        }
        
        // 3. Admin Dashboard - nur für Admins
        if ($user->isAdmin()) {
            $adminWidget = new \Project\InfoCenterWidgets\CustomDashboardWidget();
            $infoCenter->registerWidget($adminWidget);
        }
        
    }, rex_extension::LATE); // LATE ist wichtig!
}
```

### 📡 **API Integration**
```javascript
// Widget Builder AJAX API
fetch('/redaxo/index.php?rex-api-call=widget_builder&action=get_table_fields&table_name=my_table')
  .then(response => response.text())
  .then(html => {
    // Feldliste verarbeiten
  });

// Lazy Loading für eigene Widgets
fetch('/redaxo/index.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: 'rex-api-call=info_center_lazy&widget_id=my_widget'
})
.then(response => response.text())
.then(html => {
    document.querySelector('[data-widget="my_widget"] .widget-content').innerHTML = html;
});
```

---

## 🔌 Erweiterte Integration

### 📊 **YForm Integration**
```php
<?php
// YForm-Tabellen als Widget anzeigen
namespace Project\InfoCenterWidgets;

use KLXM\InfoCenter\AbstractWidget;
use rex_yform_manager_dataset;

class YFormDataWidget extends AbstractWidget
{
    private string $tableName;
    
    public function __construct(string $tableName)
    {
        parent::__construct();
        $this->tableName = $tableName;
        $this->title = 'YForm: ' . $tableName;
    }
    
    public function render(): string
    {
        // Prüfen ob YForm verfügbar ist
        if (!rex_addon::get('yform')->isAvailable()) {
            return $this->wrapContent('<p>YForm nicht verfügbar</p>');
        }
        
        // Daten über YForm Manager laden
        $items = rex_yform_manager_dataset::query($this->tableName)
            ->limit(10)
            ->orderBy('id', 'DESC')
            ->find();
        
        $content = '<div class="yform-widget-list">';
        foreach ($items as $item) {
            $content .= sprintf(
                '<div class="yform-item">
                    <a href="/redaxo/index.php?page=yform/manager/data_edit&table_name=%s&data_id=%s">
                        %s
                    </a>
                </div>',
                $this->tableName,
                $item->getId(),
                $item->getValue('name') ?: 'ID: ' . $item->getId()
            );
        }
        $content .= '</div>';
        
        return $this->wrapContent($content);
    }
}
```

### 🌐 **Frontend-Backend Verknüpfung**
```php
<?php
// Widget nur im Frontend für Backend-User anzeigen
namespace Project\InfoCenterWidgets;

use KLXM\InfoCenter\AbstractWidget;
use rex;
use rex_backend_login;

class FrontendOnlyWidget extends AbstractWidget
{
    public function render(): string
    {
        // Nur im Frontend anzeigen, wenn Backend-User eingeloggt
        if (!rex::isFrontend()) {
            return '';
        }
        
        // Backend-User im Frontend prüfen
        $user = rex_backend_login::createUser();
        if (!$user) {
            return '';
        }
        
        $content = '<div class="frontend-widget">';
        $content .= '<h4>Frontend-Modus aktiv</h4>';
        $content .= '<p>Du siehst diese Seite als: ' . $user->getLogin() . '</p>';
        $content .= '</div>';
        
        return $this->wrapContent($content);
    }
}
```

### 🎨 **CSS-Integration für eigene Widgets**
```css
/* Eigene Widget-Styles in project/assets/info-center-custom.css */

/* News Widget Styling */
.widget-news .info-center-list-item {
    border-left: 3px solid #007cba;
    padding-left: 10px;
    margin-bottom: 8px;
}

.widget-news .item-title a {
    font-weight: bold;
    color: #333;
    text-decoration: none;
}

.widget-news .item-meta {
    font-size: 0.85em;
    color: #666;
    margin-top: 2px;
}

.widget-news .status {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.8em;
}

.widget-news .status-1 {
    background: #d4edda;
    color: #155724;
}

.widget-news .status-0 {
    background: #f8d7da;
    color: #721c24;
}

/* Loading Animation für Lazy Loading Widgets */
.loading-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 60px;
    color: #666;
    font-style: italic;
}

.loading-placeholder::after {
    content: '';
    width: 16px;
    height: 16px;
    margin-left: 10px;
    border: 2px solid #ddd;
    border-top: 2px solid #007cba;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
```

### 🔧 **Widget-Konfiguration über Backend**

```php
<?php
// Eigene Widgets konfigurierbar machen
// Datei: src/addons/project/pages/info_center_config.php

$content = '';
$addon = rex_addon::get('project');

// Konfiguration verarbeiten
if (rex_post('save', 'boolean')) {
    $config = rex_post('config', 'array');
    
    // News Widget Konfiguration
    $addon->setConfig('info_center_news', [
        'limit' => (int) $config['news_limit'],
        'show_date' => (bool) $config['news_show_date'],
        'category_filter' => $config['news_category']
    ]);
    
    echo rex_view::success('Konfiguration gespeichert');
}

// Aktuelle Konfiguration laden
$newsConfig = $addon->getConfig('info_center_news', [
    'limit' => 5,
    'show_date' => true,
    'category_filter' => ''
]);

// Formular
$formElements = [];

$n = [];
$n['label'] = '<label for="news_limit">Anzahl News anzeigen:</label>';
$n['field'] = '<input type="number" id="news_limit" name="config[news_limit]" value="' . $newsConfig['limit'] . '" min="1" max="20">';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="news_show_date">Datum anzeigen:</label>';
$n['field'] = '<input type="checkbox" id="news_show_date" name="config[news_show_date]" value="1"' . ($newsConfig['show_date'] ? ' checked' : '') . '>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$content .= '
<fieldset>
    <input type="submit" name="save" value="Speichern" class="btn btn-primary">
</fieldset>';

$content = '<form action="' . rex_url::currentBackendPage() . '" method="post">' . $content . '</form>';

echo $content;
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

Thomas Skerbis 

**[KLXM Crossmedia GmbH](https://klxm.de )**  
Entwickelt für REDAXO CMS - Das intelligente Dashboard-System für moderne Webentwicklung.

---

**🎉 Viel Spaß mit dem Info Center - Ihrem intelligenten REDAXO Dashboard!**
