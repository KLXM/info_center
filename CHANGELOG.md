# Changelog

## [2.5.0] - 2026-04-21

### Added
- **Admin-Befehl `#user login email [Rolle]`**: Legt einen neuen REDAXO-Benutzer direkt aus dem Suchfeld an. Ein kryptografisch sicheres temporäres Passwort wird generiert. `password_change_required = 1` erzwingt die Passwortänderung beim ersten Login. Zugangsdaten werden in einem Modal angezeigt und können per Klick kopiert werden.
- **Admin-Befehl `#showusers`**: Listet alle REDAXO-Benutzer mit Status, Rolle und letztem Login in einem Modal auf.
- **Admin-Befehl `#clearcache`**: Leert den kompletten REDAXO-Cache (`rex_delete_cache()`) und gibt direktes Feedback.
- **Admin-Befehl `#userdisable login`**: Deaktiviert einen Benutzeraccount sofort (`status = 0`). Self-Lock ist verhindert – der ausführende Admin kann sich nicht selbst sperren.
- Alle Admin-Befehle nutzen den neuen API-Endpunkt `rex_api_info_center_admin_command` mit CSRF-Token-Validierung.
- Hilfe-Modal zeigt Admin-Befehle in eigenem Abschnitt (nur für Admins sichtbar).

### Fixed
- Such-Widget: Quick Action px↔rem/em-Konvertierung war fehlerhaft. Eingaben wie `16px rem` oder `1.5rem px` wurden nicht erkannt. Ersetzt durch Regex-basierte Mustererkennung mit expliziter Richtungserkennung. Unterstützt jetzt: `16px`, `16px rem`, `16px to rem`, `1.5rem`, `1.5rem px`, `1.5rem to px`.
- Such-Widget: Start-Artikel erschienen doppelt in den Suchergebnissen (einmal als Kategorie, einmal als Artikel). Start-Artikel werden jetzt aus der Artikel-Liste dedupliziert.
- Suchfilter `created:today` / `modified:today` etc. funktionierten nicht, da das `#`-Präfix in der Regex zwingend war. Das Präfix ist jetzt optional.

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

