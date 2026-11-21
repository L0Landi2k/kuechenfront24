# Küchenfront24 – neue Website mit Shop

Komplette neue Website für **kuechenfront24.de**: WordPress-Theme, WooCommerce-Konfigurator für Fronten nach Maß und die vereinfachte Verwaltung „Mein Shop“.

## ▶ Testversion öffnen

**[Testversion starten](https://playground.wordpress.net/?blueprint-url=https://raw.githubusercontent.com/L0Landi2k/kuechenfront24/main/blueprint.json)**

Läuft komplett im Browser (WordPress Playground), ohne Installation. Beim ersten Öffnen dauert es 1–2 Minuten, bis alles aufgebaut ist. Man ist automatisch als Shop-Inhaber „Michi“ angemeldet. Es wird nichts bestellt, bezahlt oder verschickt – alle E-Mails landen im Testpostfach unter *Mein Shop → E-Mails (Test)*.

Unten am Bildschirm gibt es den Knopf **„Testliste & Feedback“**: eine Schritt-für-Schritt-Liste zum Durchklicken und ein Notizfeld. Die Notizen lassen sich am Ende per WhatsApp, E-Mail oder zum Kopieren weitergeben.

## Was drin ist

| Bereich | Inhalt |
|---|---|
| Website | Startseite, Shop-Übersicht, Frontarten, IKEA Metod/Faktum, Nach Maß, Preisrechner, Aufmaß-Tabelle (online, druckbar, mit Faktum-/Metod-Bestellhilfe), RAL-Farben, Muster mit Farb-/Holzauswahl, Referenzen mit Vorher/Nachher, Ratgeber, FAQ, Service, Kontakt, Rechtstexte, „Vertrag widerrufen“ |
| Konfigurator | Preis je m², Live-Zeichnung mit Material, Topfbänder, Griffbohrungen, Grifffräsung, Plausibilitätsprüfung, Mindermengen- und Längenzuschlag, Versand nach Gewicht, Fertigungsblatt |
| Mein Shop | Übersicht, Fronten anlegen / duplizieren / ein- und ausschalten / löschen, Materialbibliothek mit Fotos per Drag & Drop, Referenzen mit festen Layouts, Preise & Versand |
| Materialien | 371 Materialien: Massivholz, Furnier, Egger-Holz-, Uni- und Materialdekore, alle RAL-Farben, Fenix |

## Aktuelle Annahmen

- Topfband mitliefern: **4,00 € je Stück**
- Aufsteckdichtung: **2,00 € je lfm (fiktiv)** – wird im Shop mit „fiktiv“ angezeigt, bis der echte Preis feststeht
- Fertigungsmaß maximal **2.800 × 900 mm**
- RAL-Farbwerte aus einer offenen Farbtabelle (MIT-Lizenz). Holz-, Furnier-, Fenix- und Dekorbilder sind **einheitlich erzeugte Platzhalter** und werden durch echte Fotos ersetzt (einfach per Drag & Drop in *Materialien & Fotos*).
- Das Bild „Hängesideboard“ ist eine Illustration, das Über-uns-Bild ein Platzhalter – beide werden durch echte Fotos ersetzt.
- Rechtstexte werden vor dem Livegang geprüft.

Alle Werte sind in *Mein Shop → Preise & Versand* änderbar.

## Aufbau

```
blueprint.json          Bauplan für die Testversion (WordPress Playground)
plugin/                 Plugin „Küchenfront24 Konfigurator“ (Quellcode)
theme/                  Theme „Küchenfront24“ (Quellcode)
dist/                   Fertige ZIP-Dateien zum Hochladen in WordPress (./build.sh)
playground/             Einrichtung der Testdaten
umzug/                  Weiterleitungen und .htaccess für den Umzug
docs/                   Anleitung für den Inhaber und technische Hinweise
```

## Live gehen (Kurzfassung)

Siehe [docs/TECHNIK.md](docs/TECHNIK.md). In einem Satz: Staging-Kopie beim Hoster anlegen, WooCommerce + die beiden ZIPs aus `dist/` installieren, *Design → Küchenfront24 einrichten* und *WooCommerce → Küchenfront24 → Einrichtung* ausführen, testen, Weiterleitungen aus `umzug/` setzen, live schalten.
