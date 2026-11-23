# Changelog

## Unveröffentlicht

- Neu: Option „Redakteuren Elementor → Übermittlungen erlauben“ (Reiter „Allgemein“, Standard aus). Elementor verlangt dafür sonst `manage_options`, Redakteure sahen den Menüpunkt trotz Freigabe nicht

## 1.5.1 – 2026-11-16

- Behoben: Nach einem Update zeigte der Browser weiter das alte Farbschema aus dem Cache (Inhaltsbereiche blieben orange). Die Schema-Datei wird jetzt mit der Plugin-Version geladen

## 1.5.0 – 2026-11-16

- Farbschema „b83“ färbt nur noch die Seitenleiste und die Werkzeugleiste; Links, Buttons und Formulare in den Inhaltsbereichen bleiben im WordPress-Standard

## 1.4.0 – 2026-11-16

- Login-Seite: Logo, Meldungen, Formular und Links sitzen jetzt gemeinsam in einem weißen Kasten (alle drei Gestaltungen)
- Standardbreite des Login-Logos 96 statt 120 px (bestehende Seiten mit 120 px werden umgestellt)

## 1.3.0 – 2026-11-16

- Login-Seite im b83-Design: dunkler Farbverlauf als Standard, wahlweise heller Verlauf oder eigenes Hintergrundbild; abgerundeter Kasten, Button und Links in b83-Orange, Fußzeilentext unter dem Formular
- Der Farbverlauf bewegt sich langsam (abschaltbar, ruht bei „Bewegung reduzieren“)
- Neues Farbschema „b83“ für das Backend: für alle vorgeben (Standard), nur im Profil anbieten oder aus
- Neuer Filter `b83_wl_login_background_url`

## 1.2.0 – 2026-11-13

- Neue Spalte „Registriert“ in der Benutzerliste, sortierbar (neueste zuerst); einstellbar unter Allgemein → Benutzerliste: für alle, nur für b83-Benutzer oder aus

## 1.1.1 – 2026-11-06

- Menü-Liste in den Einstellungen in derselben Reihenfolge wie das echte Menü: Plugins mit eigener Position (z. B. Simple History) standen bisher am Ende der Liste
- Neue Unterpunkte unter Dashboard und Profil (z. B. von Plugins) sind für Kunden jetzt ebenfalls ausgeblendet, bis sie angehakt werden

## 1.1.0 – 2026-11-01

- Neuer Standardtext in der Fußzeile: „© Webentwicklung: b83.at - Strategisches Online Marketing“ (bestehende Seiten erhalten ihn, wenn der alte Text nicht angepasst wurde)
- b83-Logo auf der Login-Seite lässt sich per Häkchen abschalten (Standard: an)

## 1.0.0 – 2026-11-01

- Erste Version als Ersatz für White Label CMS
- Fußzeile mit b83-Hinweis, WordPress-Version nur für b83-Benutzer
- WordPress-Logo in der Werkzeugleiste entfernt
- b83-Logo auf der Login-Seite, eigenes Bild aus der Mediathek möglich
- Dashboard-Widget „Kundeninformation“, weitere Widgets nur nach Freigabe
- Admin-Menü je Website per Häkchen freigeben, ausgeblendete Seiten werden gesperrt
- b83-Benutzer frei wählbar, Notbremse über B83_WL_DISABLE
