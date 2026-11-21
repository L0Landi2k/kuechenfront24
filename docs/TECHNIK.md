# Technik und Livegang

## Voraussetzungen
WordPress ≥ 6.5, PHP ≥ 8.1 (getestet 8.3), WooCommerce ≥ 9 (getestet 11.3), GD-Bibliothek.

## Installation auf Staging
1. Staging-Kopie beim Hoster anlegen (oder neue WordPress-Instanz unter einer Subdomain).
2. WooCommerce installieren und aktivieren.
3. `dist/kf24-konfigurator.zip` unter *Plugins → Installieren → Plugin hochladen* installieren und aktivieren.
4. `dist/kuechenfront24.zip` unter *Design → Themes → Theme hochladen* installieren und aktivieren.
5. *WooCommerce → Küchenfront24 → Einrichtung starten* (legt 21 Fronten, 14 Muster und 371 Materialien an).
6. *Design → Küchenfront24 einrichten* (Seiten, Menüs, Startseite, klassischer Warenkorb/Kasse).
7. WooCommerce-Grundeinstellungen: Deutschland/Schweiz, Euro, Preise inkl. 19 % MwSt., Versandzonen DE und CH mit der Versandart „Küchenfront24 Versand“, Zahlarten.
8. Bestehende Metod-Produkte aus dem alten Shop per WooCommerce-Export/Import übernehmen.
9. Testbestellungen, dann Rechtstexte prüfen lassen.

WP-CLI: `wp kf24 setup`, `wp kf24 theme-setup`.

## Umzug
Weiterleitungen und .htaccess-Vorlagen liegen in `umzug/`. Ablauf siehe `umzug/LIESMICH.txt`.

## Testmodus
Die Testversion setzt die Option `kf24_demo`. Nur dann erscheinen Testleiste, Feedback-Knopf und Testpostfach, und E-Mails werden abgefangen. Auf dem Live-System ist die Option nicht gesetzt.

## Qualität
- WordPress Coding Standards (Security-, DB- und PHP-Regeln) geprüft; Nonces und Rechte bei allen Speicher- und AJAX-Aktionen.
- Lighthouse: Barrierefreiheit 100, Best Practices 100 (Start- und Produktseite).
- Preise werden serverseitig neu berechnet; manipulierte Formularwerte werden abgelehnt.
