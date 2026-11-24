# Metod & Faktum – Produktdatenbank

## Entscheidung (24.11.2026)
Die alten Variantenprodukte (jedes Metod-Teil ein Produkt mit Holzart × Breite × Höhe × Anschlag) sind gestrichen:
zu viele Klicks für Kunden und tausende Einzelpreise für Michi. Es gilt nur noch dieses Modell:

Drei Bausteine, jeweils **einmal** gepflegt:

| Baustein | Was | Wo |
|---|---|---|
| Teileliste | Alle Metod- und Faktum-Teile mit Fertigungsmaß, Art, Anschlag, Topfband-Positionen, Fertigungshinweis | Mein Shop → Metod & Faktum → Teileliste |
| Preisformel | je Frontfamilie: m²-Preis, Kante je lfm, Stückpauschale, Mindestpreis | Mein Shop → Metod & Faktum → Preisformel |
| Festpreise | nur wo gewünscht: fester Preis für ein Teil in einer Front (überschreibt die Formel) | Mein Shop → Metod & Faktum → Preise |

Materialien/Texturen kommen aus der Materialbibliothek (einmal pro Material).

### Preis eines Teils
```
Grundpreis = max( Mindestpreis ;  Fläche m² × m²-Preis  +  Umfang lfm × Kantenpreis  +  Stückpauschale )
             – oder der Festpreis, wenn für dieses Teil in dieser Front einer eingetragen ist
Teilpreis  = Grundpreis + Grifffräsung + Topfbänder (falls mitbestellt) + Aufsteckdichtung
```
Mindestfläche (Einstellung „min_area“) gilt zusätzlich. Die Formel gilt auch für alle Maßanfertigungen – Metod, Faktum und „nach Maß“ rechnen damit identisch.

### Für den Kunden
Ein Weg: **Fronten bestellen → Küche wählen → Front wählen → Konfigurator.**
Im Konfigurator bei „Maße“: **Ihre Küche → Teil für diese Front** (Auswahlliste) setzt Maß, Art, Anschlag, Topfband-Positionen und Fertigungshinweis.
Darunter **„Ganze Küche: alle Teile im Überblick“** – jedes angetippte Teil wird eine Position der Frontenliste (wie früher die Aufmaßliste). Stückzahl direkt in der Liste mit − / +.
Ändert der Kunde das Maß, wird es automatisch eine Maßanfertigung (Formelpreis). Die Aufmaß-Tabelle zum Ausdrucken nutzt dieselben Daten.

### Aufwand im Vergleich
| | früher (Variantenprodukte) | jetzt |
|---|---|---|
| Produkte | ~200 + tausende Varianten | 1 je Frontfamilie (heute 21) |
| Neue Holzart | jede Variante | 1 Material anlegen, bei der Front anhaken |
| Preiserhöhung PG2 um 5 % | jede Variante | 1 Feld (m²-Preis) |
| Neues Metod-Teil | neues Produkt + Varianten | 1 Zeile in der Teileliste |
| Kalkulation nachvollziehbar | nein (Einzelpreise) | ja (Formel + sichtbare Ausnahmen) |

### Offene Punkte für Michi
1. Formelwerte je Frontfamilie (Kante, Pauschale, Mindestpreis) – oder alte Preise als Festpreise übernehmen.
2. Metod-Topfband-Positionen je Tür (heute Standardberechnung) bestätigen oder eintragen.
3. Alte Metod-Preisliste (Export aus dem alten Shop) für den Abgleich Formel ↔ heutige Preise.
