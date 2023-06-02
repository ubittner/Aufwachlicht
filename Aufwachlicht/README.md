# Aufwachlicht

Entspannt aufwachen. Simuliert ein Aufwachlicht (Wake-Up Light) für einen natürlichen Sonnenaufgang.  

Für dieses Modul besteht kein Anspruch auf Fehlerfreiheit, Weiterentwicklung, sonstige Unterstützung oder Support.  
Bevor das Modul installiert wird, sollte unbedingt ein Backup von IP-Symcon durchgeführt werden.  
Der Entwickler haftet nicht für eventuell auftretende Datenverluste oder sonstige Schäden.  
Der Nutzer stimmt den o.a. Bedingungen, sowie den Lizenzbedingungen ausdrücklich zu.

## Funktionen

Mit dieser Funktion kann das Aufwachlicht geschaltet werden.

```text
boolean AWL_ToggleWakeUpLight(integer $InstanceID, boolean $State, integer $Mode = 0);
```

Konnte der Befehl erfolgreich ausgeführt werden, liefert er als Ergebnis `TRUE`, andernfalls `FALSE`.

| Parameter    | Beschreibung   | Wert                        |
|--------------|----------------|-----------------------------|
| `InstanceID` | ID der Instanz | 12345                       |
| `State`      | Status         | false = Aus, true = An      |
| `Mode`       | Modus          | 0 = manuell, 1 = Wochenplan |


**Beispiel:**

Das Aufwachlicht soll manuell eingeschaltet werden.

```php
$id = 12345;
$result = AWL_ToggleWakeUpLight($id, true);
var_dump($result);
```