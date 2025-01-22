# REDAXO Info Center

## In development 🐣

Ein modernes Info- und Steuerungszentrum für REDAXO CMS, inspiriert vom macOS Control Center.

## Features

- Moderne, schwebende Seitenleiste für Frontend und Backend
- Eigene Webkomponenten, unabhängig vom Design-System
- Automatischer Dark Mode Support
- Modulares Widget-System
- Lazy Loading für bessere Performance
- Themefähig über CSS Custom Properties

## Installation

1. Im REDAXO Installer das Addon "info_center" herunterladen
2. Installation durchführen
3. Addon aktivieren

Das Info Center ist dann automatisch im Frontend und Backend für eingeloggte Benutzer verfügbar.

## Standard Widgets

### System Widget
- Zeigt REDAXO Version, PHP Version und MySQL Version
- Schnellzugriff auf Systemeinstellungen (für Administratoren)
- Links zu Logs und System-Reports

### Artikel Widget
- Informationen zum aktuellen Artikel
- Status und Metadaten
- Schnelle Bearbeitung im Backend
- Pfad-Navigation

## Entwickler Dokumentation

### Eigene Widgets erstellen

1. Eine neue Klasse erstellen, die `AbstractWidget` erweitert:

```php
use KLXM\InfoCenter\AbstractWidget;

class CustomWidget extends AbstractWidget 
{
    public function render(): string 
    {
        return $this->wrapContent('Widget Content');
    }
}
```

2. Widget im Info Center registrieren:

```php
// In boot.php des eigenen Addons
rex_extension::register('INFO_CENTER_INIT', function() {
    InfoCenter::getInstance()->registerWidget(new CustomWidget());
});
```

### Widget mit Lazy Loading

```php
class LazyWidget extends AbstractWidget 
{
    protected bool $supportsLazyLoading = true;

    public function getInitialContent(): string 
    {
        return $this->wrapContent('Loading...');
    }

    public function render(): string 
    {
        // Vollständiger Inhalt
        return $this->wrapContent('Loaded Content');
    }
}
```

### CSS anpassen

Das Info Center nutzt CSS Custom Properties für das Theming:

```css
:root {
    --info-center-background-color: #ffffff;
    --info-center-text-color: #333333;
    --info-center-border-color: #dddddd;
    /* ... weitere Properties ... */
}
```

## Unterschiede zur Minibar

Das Info Center ist eine moderne Neuinterpretation der Minibar mit folgenden Verbesserungen:

- Modulare Webkomponenten statt jQuery
- Bessere Performance durch Lazy Loading
- Modernes Design im Stil des macOS Control Centers
- Einfachere Widget-Entwicklung
- Bessere Theme-Unterstützung
- Responsive Design

## Beitragen

1. Fork erstellen
2. Feature Branch erstellen (`git checkout -b feature/AmazingFeature`)
3. Änderungen committen (`git commit -m 'Add some AmazingFeature'`)
4. Branch pushen (`git push origin feature/AmazingFeature`)
5. Pull Request erstellen

## Credits

Entwickelt von KLXM Crossmedia GmbH
