# Changelog

## [2.3.4] - 2026-03-12

### Fixed
- Artikel-Widget: Berechtigungslogik analog zu `quick_navigation` umgestellt. Nicht-Admins sehen nur ihre eigenen zuletzt bearbeiteten Artikel (`WHERE updateuser = login`). Keine zusätzliche `hasCategoryPerm`-Prüfung, da Zugriff zum Bearbeitungszeitpunkt bereits vorhanden war.
- Gate-Check in `renderRecentArticles()`: Ohne Berechtigung `info_center[recent_articles]` (und kein Admin) wird kein Artikel-Verlauf angezeigt.

## [2.3.3] - 2026-03-12

### Fixed
- Einstellungen-Button für Nicht-Admins (ohne Berechtigung `info_center[config]`) ausgeblendet.
- Artikel-Widget: Zuletzt geänderte Artikel nach Kategoriezugriff gefiltert (replaced by 2.3.4).

## [2.3.2] - 2026-03-12

### Fixed
- Fixed an issue where external third-party widgets (e.g. Matomo) could not be toggled via the user settings.
- Einstellungs-Seite listet nun automatisch alle Fremd-Widgets dynamisch zur Konfiguration auf.
- Drittanbieter-Widgets prüfen nun ordnungsgemäß die benutzerdefinierten und globalen Anzeige-Einstellungen in `AbstractWidget`.
- Helligkeit der Sidebar in Light Mode verbessert: Sidebar-Hintergrund auf `rgba(166, 186, 209, 0.71)` angepasst, um Kontraste (z. B. Struktur-Domainauswahl) besser sichtbar zu machen.

