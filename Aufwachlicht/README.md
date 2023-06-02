# Aufwachlicht

Diese Instanz simuliert einen natürlichen Sonnenaufgang für ein entspanntes aufwachen und erhöht die Helligkeit einer Lampe.

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

## Wochenplan

Die Einschaltfunktion kann auch über den Wochenplan ausgelöst werden.

## Funktionen

Mit dieser Funktion kann das Aufwachlicht geschaltet werden.

```text
boolean AWL_ToggleWakeUpLight(integer $InstanceID, boolean $State);
```

Konnte der Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis `TRUE`, andernfalls `FALSE`.

| Parameter    | Beschreibung   | Wert                        |
|--------------|----------------|-----------------------------|
| `InstanceID` | ID der Instanz | z.B. 12345                  |
| `State`      | Status         | false = Aus, true = An      |


**Beispiel:**

Das Aufwachlicht soll manuell eingeschaltet werden.

```php
$id = 12345;
$result = AWL_ToggleWakeUpLight($id, true);
var_dump($result);
```

| Vorgang                     | Gerätestatus                                | Aktion  |
|-----------------------------|---------------------------------------------|---------|
| Beim Einschalten            | Lampe ist bereits eingeschaltet             | Abbruch |
| Bei Erhöhung der Helligkeit | Lampe wurde inzwischen wieder ausgeschaltet | Abbruch |
| Bei Erhöhung der Helligkeit | Helligkeit wurde bereits manuell verändert  | Abbruch |