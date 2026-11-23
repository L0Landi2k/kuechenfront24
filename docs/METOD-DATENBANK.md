# Metod & Faktum – Produktdatenbank

## Variante A (heute, wie im alten Shop)
Jedes Metod-Teil ist ein eigenes WooCommerce-Produkt mit Varianten (Holzart × Breite × Höhe × Anschlag × Griff).
Bei 6 Frontfamilien, rund 30 Teilen und 4–10 Ausführungen entstehen schnell **tausende Varianten**, die jede einen eigenen Preis braucht.
Neue Holzart = jede Variante anfassen. Preiserhöhung = jede Variante anfassen.

## Variante B (Test)
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
Im Konfigurator gibt es bei „Maße“ das Feld **Küchensystem → Teil**. Ein Klick setzt Maß, Art, Anschlag, Topfband-Positionen und Fertigungshinweis.
Ändert der Kunde das Maß, wird es automatisch eine Maßanfertigung (Formelpreis).
In „Planen & Preis“ füllen die Bestellhilfe-Knöpfe dieselben Daten ein.

### Aufwand im Vergleich
| | Variante A | Variante B |
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
