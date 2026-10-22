# Changelog

## [2.6.0] - 2026-10-22

### Added
- **Issue Tracker Widget:** „Problem mit dieser Seite melden“ direkt aus dem Info Center – im Frontend und im
  Backend. Voraussetzung: AddOn issue_tracker (FriendsOfREDAXO) und Recht `issue_tracker[]`; sonst wird das
  Widget nicht registriert. Betreff, Beschreibung, Priorität und Zuständigkeit („Alle (gemeinsame Zuständigkeit)“
  als Standard). Die aktuelle Seite wird automatisch angehängt und angezeigt: im Frontend Artikel + Sprache als
  betroffene Seite und die volle Adresse (nur eigene Website), im Backend die Backend-Seite als Herkunft nach den
  Regeln des Issue Trackers, beim Bearbeiten eines Artikels zusätzlich dieser. Anlegen wie im Tracker selbst
  (Verlauf, Benachrichtigungen). Danach Link „Thema #… öffnen“ (im Backend in der Ebene des Trackers), dazu die
  Zahl der mir zugewiesenen offenen Themen und die letzten drei.
- Neuer API-Endpunkt `rex_api_info_center_issue_report`: nur POST, CSRF-Token im Body, Backend-Session und Recht
  werden geprüft, Eingaben serverseitig validiert (Längen, Priorität, Zuständige, Artikel, Adresse).

### Changed
- Widget-Sortierung per Alt+Pfeil reagiert auch nicht mehr, solange eine Auswahlliste den Fokus hat.

## [2.5.3] - 2026-10-19

### Fixed
- **Kein Session-Cookie mehr für Besucher:** Im Frontend startete das AddOn bei jedem Aufruf eine Backend-Session, damit
  bekam jeder Besucher ein `PHPSESSID`-Cookie (Datenschutz-Hinweis nötig, Seiten schlechter cachebar). Jetzt wird die
  Session nur aufgenommen, wenn das Login-Cookie schon da ist (`rex_backend_login::hasSession()`); für angemeldete
  Redakteure funktioniert das Info Center im Frontend wie bisher. Auf der Konsole (CLI) wird nichts gestartet.

## [2.5.2] - 2026-10-13

### Fixed
- **Kontrast (Barrierefreiheit):** gedämpfte Schrift jetzt ≥ 4,5:1 – hell `#4b5563` statt `#6b7280`, dunkel `#aab6c6` statt `#94a3b8`. Betrifft u. a. inaktive Reiter („Struktur“), Beschriftungen und Hinweise in Widgets. axe meldet in hellem und dunklem Theme keine Kontrastfehler mehr (vorher 5 bzw. 1).
- **Breiten-Knöpfe S/M/L/XL:** volle Textfarbe statt gedämpft (lagen auf dem halbtransparenten Panel bei 2,9:1).
- **TimeTracker:** Statustext und „Heute“ über die Theme-Farben (vorher fest `#7f8c8d`, 2,9:1); Zeit und Werte folgen der Textfarbe des Themes. Start/Pause/Stopp mit dunkleren Flächen, damit die weiße Schrift lesbar ist.
- **Suchfeld:** ragte über den Widget-Rand (100 % Breite plus Innenabstand ohne `box-sizing`); langer Platzhalter wird mit „…“ gekürzt.
- **Tastatur-Fokus:** deutlich sichtbarer Fokusrahmen für Links, Knöpfe und Suchfeld im Info Center.

## [2.5.1] - 2026-10-09

### Fixed
- Struktur-Widget: „Error loading structure“ nach dem Absenden eines Backend-Formulars (z. B. YForm). Die Seite lag dann unter `index.php` ohne Query-String, an den die API-URL nur `&rex-api-call=…` anhängte – das ergab einen ungültigen Pfad (404). Gleiches beim Zurücksetzen der Domain-Auswahl. Die URL wird jetzt über `URLSearchParams` gebaut.
- Fataler Fehler mit uikit_theme_builder: Die veraltete Registrierung des Live-Theme-Editor-Widgets ist entfernt, das Widget registriert sich in uikit_theme_builder selbst.
- Abstände im Info Center: Fremde Theme-CSS (u. a. der Live-Theme-Editor) setzte `margin-top` auf Elemente im Info Center; die Ursache ist an der Quelle behoben, die zwischenzeitliche `revert`-Gegenregel wieder entfernt.
- Farbwähler (Pickit Color) im Info Center: Eine Farbauswahl schloss das Info Center nicht mehr versehentlich, wenn der Farbwähler sein Popup schon während des Klicks entfernt.
- Frontend: CSS/JS des Info Centers mit Versionsparameter eingebunden, damit Browser nach Updates keine veralteten Dateien verwenden.

## [2.5.0] - 2026-04-21

### Added
- **Admin-Befehl `#user login email [Rolle]`**: Legt einen neuen REDAXO-Benutzer direkt aus dem Suchfeld an. Ein kryptografisch sicheres temporäres Passwort wird generiert. `password_change_required = 1` erzwingt die Passwortänderung beim ersten Login. Zugangsdaten werden in einem Modal angezeigt und können per Klick kopiert werden. `admin` als Rolle setzt `admin = 1`.
- **Admin-Befehl `#showusers`**: Listet alle REDAXO-Benutzer mit Status, Rolle und letztem Login in einem Modal auf.
- **Admin-Befehl `#clearcache`**: Leert den kompletten REDAXO-Cache (`rex_delete_cache()`) und gibt direktes Feedback.
- **Admin-Befehl `#userdisable login`**: Deaktiviert einen Benutzeraccount sofort (`status = 0`). Self-Lock ist verhindert – der ausführende Admin kann sich nicht selbst sperren.
- **Admin-Befehl `#userenable login`**: Aktiviert einen gesperrten Benutzeraccount (status = 1). Gegenstück zu `#userdisable`.
- **Admin-Befehl `#passwd login`**: Generiert ein neues kryptografisch sicheres Temporärpasswort für einen bestehenden Benutzer. `password_change_required = 1` erzwingt die Passwortänderung.
- **Admin-Befehl `#mediasize`**: Zeigt Gesamtgröße, Dateianzahl und Top-10-Dateien des Medienordners in einem Modal.
- **Admin-Befehl `#logview [n]`**: Zeigt die letzten n Einträge aus dem REDAXO-Systemlog (Standard: 20, max: 500) in einem lesbaren Modal.
- Alle Admin-Befehle nutzen den neuen API-Endpunkt `rex_api_info_center_admin_command` mit CSRF-Token-Validierung.
- Hilfe-Modal zeigt alle Admin-Befehle in eigenem Abschnitt (nur für Admins sichtbar).

### Fixed
- Such-Widget: Quick Action px↔rem/em-Konvertierung war fehlerhaft. Eingaben wie `16px rem` oder `1.5rem px` wurden nicht erkannt. Ersetzt durch Regex-basierte Mustererkennung mit expliziter Richtungserkennung. Unterstützt jetzt: `16px`, `16px rem`, `16px to rem`, `1.5rem`, `1.5rem px`, `1.5rem to px`.
- Such-Widget: Start-Artikel erschienen doppelt in den Suchergebnissen (einmal als Kategorie, einmal als Artikel). Start-Artikel werden jetzt aus der Artikel-Liste dedupliziert.
- Suchfilter `created:today` / `modified:today` etc. funktionierten nicht, da das `#`-Präfix in der Regex zwingend war. Das Präfix ist jetzt optional.
- Benutzer-Bearbeiten-Link in `#showusers` verwendete falsche Backend-URL (`users/user` → `users/users`) und HTML-escapte `&amp;` in JSON-Ausgabe.

## [2.4.0] - 2026-04-21

### Fixed
- Struktur-Widget: Artikel auf der gleichen Ebene wie Kategorien wurden optisch um genau eine Einrückungsebene zu tief dargestellt. Ursache: Der `.info-center-tree-spacer` (`width: 0`) erzeugte im Flex-Container trotzdem einen `gap: 8px`, und das Artikel-Icon hatte zusätzlich `margin-left: 4px` – zusammen 12 px, was einer `ul`-Einrückungsebene (`padding-left: 12px`) entspricht. Fix: `padding-left` des Artikel-Nodes auf 12 px gesetzt, Spacer via `display: none` aus dem Flex-Flow entfernt, `margin-left` am Artikel-Icon entfernt.
- Widget-Sortierung per Tastatur: Pfeiltasten allein lösten versehentlich das Widget-Verschieben aus, auch beim Schreiben in Textfeldern (z. B. WriteAssist-Prompt). Shortcut auf **Alt+↑/↓** (Mac: Option+↑/↓) umgestellt. Zusätzliche Guard: Input/Textarea/contenteditable werden explizit ausgenommen. (Fixes #22)

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

