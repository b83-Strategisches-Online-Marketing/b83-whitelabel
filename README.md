# b83 White Label

Internes Plugin von b83 – Strategisches Online Marketing für die betreuten Kundenseiten. Es ersetzt White Label CMS und deckt genau das ab, was wir brauchen:

1. Fußzeile im Backend mit b83-Hinweis statt „Danke für dein Vertrauen in WordPress“
2. Kein WordPress-Logo oben links in der Werkzeugleiste (samt Untermenü)
3. b83-Logo auf der Login-Seite, verlinkt auf b83.at
4. Dashboard mit dem Widget „Kundeninformation“ und nur den Widgets, die freigegeben sind
5. Reduziertes Admin-Menü für alle, die keine b83-Benutzer sind

Die Standardwerte stehen im Code (`Settings::defaults()`). Auf den meisten Seiten reicht es, das Plugin zu aktivieren.

## Installation

1. ZIP aus dem neuesten [Release](https://github.com/b83-Strategisches-Online-Marketing/b83-whitelabel/releases) hochladen und aktivieren.
2. Wer das Plugin aktiviert, wird automatisch b83-Benutzer.
3. Unter **b83 → White Label → Menü** die Freigaben prüfen und einmal speichern.
4. White Label CMS deaktivieren und löschen.

Updates kommen danach über GitHub wie bei den anderen b83-Plugins.

## Einstellungen

Die Seite **b83 → White Label** sehen nur b83-Benutzer.

- **Allgemein:** Fußzeilentexte, WordPress-Logo, Login-Logo (standardmäßig an, per Häkchen abschaltbar; Mediathek oder b83-Logo), Breite und Link, Spalte „Registriert“ in der Benutzerliste (für alle, nur b83-Benutzer oder aus), Sperre für ausgeblendete Seiten, Löschen der Daten bei Deinstallation.
- **Dashboard:** Titel und Text des eigenen Widgets, Liste aller Widgets zum Freigeben. Die Liste wird beim Aufruf des Dashboards durch einen b83-Benutzer aktualisiert.
- **Menü:** Das komplette Admin-Menü dieser Website als Baum mit Häkchen. Die Liste wird bei jedem Seitenaufruf eines b83-Benutzers aktualisiert. Neue Menüpunkte sind mit „neu“ markiert und für Kunden ausgeblendet, bis sie angehakt werden.
- **b83-Benutzer:** Administratoren, die alles sehen. Ist niemand ausgewählt, gelten alle Administratoren als b83-Benutzer.

### Standard-Freigaben

Solange das Menü nicht gespeichert wurde, sehen Kunden: Dashboard (ohne Aktualisierungen), Beiträge, Seiten, Medien, Profil und Elementor-Vorlagen. Dashboard und Profil sind immer sichtbar.

Im Dashboard bleiben standardmäßig das eigene Widget, alle Widgets mit einer ID ab `b83` (z. B. b83 Alert) und die WordPress-Warnungen zu PHP und Browser.

### Sperre

Ausblenden allein verhindert nicht, dass jemand eine Seite über die URL öffnet. Mit aktiver Sperre (Standard) leitet das Plugin Aufrufe ausgeblendeter Menüseiten aufs Dashboard um. Seiten, die nicht im Menü stehen (z. B. `post.php` zum Bearbeiten, der Elementor-Editor), sind davon nicht betroffen.

Wer wirklich nichts verstellen darf, sollte zusätzlich eine Rolle mit weniger Rechten bekommen.

### Notfall

```php
define( 'B83_WL_DISABLE', true );
```

in der `wp-config.php` schaltet alle Anpassungen ab. Die Einstellungsseite bleibt erreichbar.

## Filter

| Filter | Zweck |
| --- | --- |
| `b83_wl_is_agency` | Ob ein Benutzer als b83-Benutzer gilt (`bool $result, int $user_id`) |
| `b83_wl_default_menu` | Standard-Hauptpunkte, solange das Menü nicht gespeichert wurde |
| `b83_wl_default_hidden_children` | Unterpunkte, die standardmäßig ausgeblendet sind (`update-core.php`) |
| `b83_wl_always_allowed_pages` | Hauptpunkte, die immer sichtbar sind (`index.php`, `profile.php`) |
| `b83_wl_default_widgets` | Zusätzliche Dashboard-Widgets, die standardmäßig sichtbar sind |
| `b83_wl_footer_text` | Text links in der Fußzeile |
| `b83_wl_login_logo_url` | Adresse des Login-Logos |

## Daten

| Option | Inhalt |
| --- | --- |
| `b83_whitelabel_settings` | Alle Einstellungen |
| `b83_whitelabel_known_menu` | Zuletzt erfasstes Admin-Menü |
| `b83_whitelabel_known_widgets` | Zuletzt erfasste Dashboard-Widgets |
| `b83_whitelabel_db_version` | Datenbank-Version |

## Release

Version in `b83-whitelabel.php` (Header und `B83_WL_VERSION`) erhöhen, Abschnitt im `CHANGELOG.md` ergänzen, auf `main` pushen. Die GitHub Action erstellt das Release mit `b83-whitelabel.zip`.
