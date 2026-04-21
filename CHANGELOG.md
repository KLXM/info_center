# Changelog

## [2.4.0] - 2026-04-21

### Fixed
- Struktur-Widget: Artikel auf der gleichen Ebene wie Kategorien wurden optisch um genau eine Einrückungsebene zu tief dargestellt. Ursache: Der `.info-center-tree-spacer` (`width: 0`) erzeugte im Flex-Container trotzdem einen `gap: 8px`, und das Artikel-Icon hatte zusätzlich `margin-left: 4px` – zusammen 12 px, was einer `ul`-Einrückungsebene (`padding-left: 12px`) entspricht. Fix: `padding-left` des Artikel-Nodes auf 12 px gesetzt, Spacer via `display: none` aus dem Flex-Flow entfernt, `margin-left` am Artikel-Icon entfernt.

### Changed
- Struktur-Widget: Artikel werden nun ohne Box/Bubble dargestellt – nur Trennlinien (`border-bottom`) trennen sie voneinander. Kategorien behalten ihren Rahmen und sind damit klar visuell unterscheidbar. Padding und Hover-Verhalten bleiben erhalten.

## [2.3.5] - 2026-03-12

### Changed
- Artikel-Widget: Jeder Backend-User sieht automatisch seine eigenen zuletzt bearbeiteten Artikel – ohne explizite Berechtigungsvergabe.
- `info_center[recent_articles]` bedeutet nun: **alle** Artikel-Änderungen aller User sehen (nicht nur eigene).
- `info_center[all_articles]` entfernt (war redundant).
- Artikel-Widget zeigt max. 5 Einträge, weitere per Toggle einblendbar.
- Einstellungen-Button nur noch für Admins und User mit `info_center[config]` sichtbar.
- Berechtigungen werden nun korrekt via `rex_perm::register()` in der `boot.php` registriert.

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

