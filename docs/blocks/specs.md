## Technische Daten

- Produkt: {{product_name}}
- Leistung: {{power_w}}
- Schutzart: {{ip_rating}}

## Überblick
- Visualisiert Brechung und Fokussierung von mm-Wellen-Radarstrahlen in 2D.
- Simuliert die Wirkung dielektrischer Linsen mit frei definierbaren Geometrien.
- Unterstützt iterative Linsenkonstruktion durch schnelle Anpassung der Parameter in `src/main.js`.
- Liefert RealWorld Ergebnisse für echte, 3D-druckbare Linsen!
- Komplette Integration in FreeCAD, Linsen lassen sich (fast) komplett automatisiert erzeugen 😄👍
- Kurzes Video dazu: https://youtu.be/QgKHenz11SE

## Was ist Radaroptik?
Moderne mm-Wellen-Radarchips besitzen häufig eine relativ breite Abstrahlkeule von 60 bis 90 Grad. Für Punkt- oder Distanzmessungen ist jedoch eine stärker fokussierte Keule besser geeignet. Bei Frequenzen um 60 GHz beträgt die Wellenlänge nur etwa 5 mm – groß genug, um dielektrische Linsen ähnlich wie in der optischen Abbildung zu verwenden, und gleichzeitig einfach herstellbar, zum Beispiel mittels 3D-Druck.

Für mm-Wellen gelten die grundlegenden Prinzipien der Wellenoptik:
- **Huygenssches Prinzip** – beschreibt die Ausbreitung von Wellenfronten
- **Fermatsches Prinzip** – beschreibt den Strahlverlauf als Weg minimaler optischer Länge
- **Snelliussches Brechungsgesetz** – beschreibt die Brechung und Reflexion an Grenzflächen unterschiedlicher dielektrischer bzw. optischer Materialien

Die Simulation zeigt, wie sich Radarstrahlen durch verschiedene Medien bewegen. Sie betrachtet ausschließlich die Hauptstrahlablenkung und bildet keine Nebenkeulen oder Mehrwegeeffekte ab. Für den Entwurf von Linsengeometrien reicht dieser vereinfachte Ansatz in der Praxis häufig aus.

> [!Note]
> Die Simulation zeigt nur Vektoren. Dadurch, dass die Radar-Wellenlänge im Verhältnis zu den geometrischen Dimensionen recht hoch ist, führt das in der Realität zu einer Unschärfe. Daraus ergeben sich folgende Randbedingungen:
> - Realistische Öffnungswinkel einfacher, "kleiner" Radar-Optiken von z. B. 30 mm liegen im Bereich von minimal ca. 10 - 15 °.
> - Aufgrund dieser begrenzten Öffnungswinkel spielt die bei Radarchips oft leichte Versetzung von RX- und TX-Antennen keine Rolle.
> - Die Antennen auf den Radarchips sind üblicherweise auf das Dielektrikum Luft abgestimmt. Eine zusätzliche dielektrische Antenne (z. B. eine Radarlinse) kann diese Anpassung stören, weshalb in der Praxis ein Abstand von etwa zwei Wellenlängen oder mehr ratsam ist.

In der Praxis findet man für die üblicherweise eingesetzten Materialien ABS, PLA, PETG und für 100%-Infill (für 3D-FDM-Druck) Dielektrizitätskonstanten $ε_r$ zwischen etwa 2.5 und 3.0 ([siehe './Docus/...'](./Docus/ChatGPT_DielektrischeEigenschaftenABS_PLA_PETG_60GHz.md)). Da die Brechung zu Luft die Wurzel $\sqrt{ε_r}$ ist, sind die Designs alle ähnlich und über leichte Variationen Distanz/Radius kann man leicht das Optimum finden. Sphärische Linsen (ohne asphärische Korrekturen) lassen sich am einfachsten drucken und sind daher immer ein guter Ausgangspunkt. "Unebenheiten" der Linse, die deutlich unter der Wellenlänge liegen, sind kein Problem.

Normalerweise sind die $ε_r$ für handelsübliches Material nicht bekannt. Eine grobe Messung ist möglich, indem ein Testblock des Materials in den Strahl einer Distanzmessung eingefügt wird. Dadurch misst der Sensor eine etwas größere Distanz. Diese, auf die Dicke des Testblocks bezogen, ergibt die relative Lichtgeschwindigkeit $c_r$ im Material und damit $ε_r = (c_r / c_0)^2$.
Für ein getestetes PLA-Material wurde so experimentell ein $ε_r$ von ca. 2.5 bestimmt.

> [!Important]
> "Echtes" 100%-Infill lässt sich nie erreichen. In kleinen Hohlräumen kann sich immer noch Wasser sammeln. Gedruckte Linsen sind i. d. R. nicht wirklich für den Außeneinsatz geeignet.

Presets für Typen:
- '0': plankonvexe, hyperbolische Linse mit planer Austrittsfläche
- '1': plankonvexe, (a-/)sphärische Linse mit planer Eintrittsfläche
- '2': plane, kohärente Fresnel-Linse

> [!Tip]
> - Der **Typ '0'** (mit planer Austrittsfläche) erreicht ideale asphärische Korrektur mit den Parametern (nach DIN ISO 10110-12):  
> $f_{sag}( y ) = \frac{ y^2 }{ focusRadius + \sqrt{focusRadius^2 - (1 + k) C^2 y^2}}$<br><br>
> für (wie im Beispiel Typ '0'):<br>
> $focusRadius = X_{fixed} * (\sqrt{ε_r} - 1)$ und  $hyperK = -ε_r$<br>
> ergibt sich als Optimum:<br>
> $X_{fixed} = 10 mm$ und $ε_r = 2.5$ : $focusRadius = 5.8 mm$ und $hyperK = -2.5$
>
> - **Typ '1'** (mit planer Eintrittsfläche) ist bereits als rein sphärische Linse leicht druckbar mit ausreichend guten Ergebnissen für erste Tests. In der Praxis liefert die asphärische Korrektur dann aber für Linsen kleineren Durchmessers nochmal deutliche Verbesserungen.
>
> - **Typ '2'** (kohärente Fresnel-Linse) ist zwar schön flach, aber Achtung: hier werden 2 oder mehr Wellenzüge überlagert, evtl. also etwas weniger exakt.

## Reale Ergebnisse

Eine reale Linse vom Typ '0' mit exzellenter Performance:
- hergestellt per CNC aus ABS Vollmaterial, damit voll Outdoor-tauglich
- Leichte Modifikationen gegenüber 3D-Druck: 
  - Focus wird zur Anpassung um +1mm verschoben.
  - Übergang an der inneren Ecke wurde mit Radius 2mm verrundet, damit einfacher zu fertigen.
  
> [!IMPORTANT]
> 📧⚙️🛠️ Anfragen für technische Kooperationen sind jederzeit gerne  willkommen!




