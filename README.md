# AUTODOC 📚

Ein automatisiertes Dokumentationssystem von **JoEmbedded**

Dieses Repository dient dazu, Dokumentation automatisiert aus Bausteinen zu erstellen – mit Unterstützung für **Emojis** und **GitHub-Alerts**. Die erstellten Markdown-Dateien können mit KI-Unterstützung übersetzt, kompaktiert oder anderweitig verarbeitet und in verschiedene Formate konvertiert werden.

---

## 🎯 Übersicht

**AUTODOC** ermöglicht einen effizienten Workflow für die Erstellung mehrsprachiger, professioneller Dokumentation unter optionalem KI-Einsatz. **AUTODOC** kann aber auch für vieles andere verwendet werden: Zusammenfassungen, Verzierungen, ...,
da es sich als Kommandozeilen-Tool leicht integrieren lässt.

Zwei typische Anwendungsfälle sind im Repository direkt als Beispiele enthalten:

| Anwendungsfall | Quellverzeichnis | Ausgabeverzeichnis | Beschreibung |
|----------------|------------------|--------------------|--------------|
| **Blöcke zusammensetzen** | `docs_blocks/` | `build_blocks/` | Dokumentation aus wiederverwendbaren Bausteinen zusammenstellen, übersetzen und konvertieren |
| **Einzeldatei konvertieren** | `docs_T350/` | `docs_T350/` (in-place) | Fertige MD-Datei schnell in HTML und PDF umwandeln – ideal für Datenblätter |

> [!NOTE]
> MD-Dateien sind optimal für Embeddings. Man kann die MD-Dateien einer Sprache in einen Vector-Store laden, und die KI kann sie z.B. für ein Assistenz-System (wie [**JoKnow**](https://joembedded.de/x3/aiplay/sw/jolaunch.html) ) verwenden. Dazu reicht EINE Sprache, da die KI in nahezu jeder (anderen) Sprache antworten kann.

Als Hilfe sind einige der in der Doku erwähnten Dateien im Output belassen.

---

## 📁 Projektstruktur

| Verzeichnis | Beschreibung |
|-------------|--------------|
| `docs_blocks/` | Quell-Dateien für Anwendungsfall 1 (Blöcke und Rezepte) |
| `build_blocks/` | Generierter Output für Anwendungsfall 1 (MD, HTML, PDF) |
| `docs_T350/` | Beispiel für Anwendungsfall 2 – fertige MD-Datei inkl. Bilder |
| `makedocu/` | Hilfs-Dateien (GitHub-Flavored-Markdown, ...) und Tools für Konvertierung |
| `flavours_css/` | CSS-Dateien (flavoured_light, flavoured_medium) zur Auswahl |
| `tools/` | PHP-Scripts für Build und KI-Verarbeitung |
| `secret/` | API-Keys (z.B. für OpenAI) |

---

## 🗂️ Anwendungsfall 1: Dokumentation aus Bausteinen

Workflow:
```bash
docs_blocks/recipes/*.md → build_blocks/*.md → KI-Verarbeitung → HTML/PDF
```

Die Quell-Dateien in `docs_blocks/` sind in zwei Ebenen aufgeteilt:

```
docs_blocks/
  recipes/        ← Rezept-Dateien (steuern den Zusammenbau)
    produkt-a.md
  blocks/         ← Wiederverwendbare Textbausteine
    intro.md
    warranty.md
  testtext.md     ← Einfache Test-Quelldatei
```

Rezept-Dateien verwenden `{{include: ...}}`-Anweisungen und YAML-Frontmatter-Variablen (`{{product_name}}` etc.), um Bausteine zu einer vollständigen MD-Datei zusammenzusetzen.

### 1️⃣ Markdown zusammensetzen

```bash
php tools/build.php docs_blocks/recipes/produkt-a.md build_blocks/produkt_a_de.md
```

**Eingabe:** Rezept-Datei mit Include-Anweisungen  
**Ausgabe:** Vollständige MD-Datei in `build_blocks/`

> [!TIP]
> Die Deutschen MD-/TXT-Dateien sind optimales Material für Embedding in Vector-Stores,
> ein paar Test-MD wurden bereits früher in [**JoKnow**](https://joembedded.de/x3/aiplay/sw/jolaunch.html) verbaut. Zum Testen, wie gut der Vector-Store damit klarkommt: Top!

---

### 2️⃣ KI-gestützte Dokumentverarbeitung (via OpenAI)

**mdtool.php** ist ein flexibles Tool zur KI-basierten Verarbeitung von Markdown-Dateien, z. B. Übersetzen, Kompaktieren, Zusammenfassung einfügen.

#### 📖 Syntax

```bash
php tools/mdtool.php <inputfile.md> [optionen] [outputfile.md]
```

**Parameter:**
- `inputfile.md` - Eingabedatei (mandatory)
- `outputfile.md` - Ausgabedatei (optional, sonst stdout)

**Optionen** (Details siehe PHP-Quellcode):
- `-c <datei>` - Instructions aus Datei laden. Sinnvoll z. B. bei professionellen Übersetzungen, wo z. B. Formatierungen beachtet werden müssen. Als Beispiel ist hier `tools/translate_de_en.txt`
- `-i "<text>"` - Instructions direkt angeben. Sinnvoll für Kleinigkeiten, z. B. Rechtschreibprüfung bei reinen Textblöcken oder Erstellen einer Zusammenfassung
- `-m <modell>` - Model überschreiben (default: `gpt-4.1-mini`)
  (Note: `gpt-4.1-mini` is perfect for technical translations, `nano` is sometimes to relaxed, `gpt-5` normally oversized. For prompts: Ask **ChatGPT** )

#### 📝 Beispiele

**Übersetzen (DE→EN):**
```bash
php tools/mdtool.php build_blocks/produkt_a_de.md build_blocks/produkt_a_en.md -c tools/translate_de_en.txt
```

**Kompaktieren:**
```bash
php tools/mdtool.php build_blocks/produkt_a_de.md build_blocks/produkt_a_de_compact.md -i "Compact to small summary"
```

**Direkte Ausgabe (stdout via > in Datei):**
```bash
php tools/mdtool.php build_blocks/produkt_a_de.md -i "Translate to English" > build_blocks/output.md
```

**'Verzierte' Version mit Spoiler:**
```bash
php tools/mdtool.php docs_blocks/testtext.md build_blocks/testtext_verziert_spoiler.md -i "Füge am Anfang der Datei eine kurze Zusammenfassung als GitHub-Alert '> [!NOTE] >' ein, füge dann den Originaltext hintenan und verschönere den gesamten Text mit Emojis"
```

**Anderes Model verwenden:**
```bash
php tools/mdtool.php build_blocks/produkt_a_de.md build_blocks/produkt_a_en.md -m gpt-4.1-nano -c tools/translate_de_en.txt
```

**Datei kopieren (ohne KI-Verarbeitung):**
```bash
php tools/mdtool.php build_blocks/produkt_a_de.md build_blocks/produkt_a_de_copy.md
```

> [!IMPORTANT]
> KI-Verarbeitung benötigt einen **OpenAI API-Key** in `secret/keys.inc.php`.  
> Ohne Instructions (`-c` oder `-i`) wird die Datei nur kopiert (kein API-Call).

> [!TIP]
> YAML-Frontmatter bleibt immer unverändert – nur der Dokumenten-Body wird verarbeitet.

---

### 3️⃣ Konvertierung zu HTML / PDF (Anwendungsfall 1)

Nach dem KI-Schritt liegen fertige MD-Dateien in `build_blocks/`. Diese können direkt mit Pandoc konvertiert werden (siehe Abschnitte 4️⃣ und 5️⃣ unten), oder komfortabel über `docrun.bat`:

```bash
makedocu/docrun build_blocks/produkt_a_de -h -p
```

**Ausgabe** (im selben Verzeichnis wie die MD-Datei):
- `produkt_a_de.html` + `flavoured.css`
- `produkt_a_de.pdf`

---

## 📄 Anwendungsfall 2: Einzeldatei direkt konvertieren

Für fertige Markdown-Dateien (z.B. Datenblätter, Handbücher) die **bereits vollständig** vorliegen, entfallen die Schritte 1 und 2. Das Beispiel `docs_T350/` zeigt diesen Workflow:

```
docs_T350/
  T350_handbuch.md      ← fertige Quelldatei
  OSX_ad4to20mA.png     ← Bild(er) direkt daneben
  Howto_T350.txt        ← kurze Anleitung
```

Ein einziger Aufruf aus dem **Wurzelverzeichnis** erzeugt HTML und PDF im selben Verzeichnis:

```bash
./makedocu/docrun docs_T350/T350_handbuch -h -p
```

**Ergebnis** in `docs_T350/`:
- `T350_handbuch.html` + `flavoured.css`
- `T350_handbuch.pdf`

> [!TIP]
> Dies ist der schnellste Weg für Datenblätter und Einzeldokumente: MD-Datei und Bilder in ein Verzeichnis legen, `docrun` aufrufen – fertig!

### 🌐 Optionale Übersetzung (Anwendungsfall 2)

Die fertige MD-Datei kann vor der Konvertierung optional per KI übersetzt werden. Demo DE→EN für `T350_handbuch.md`:

```bash
php tools/mdtool.php docs_T350/T350_handbuch.md docs_T350/T350_manual.md -c tools/translate_de_en.txt
```

Anschließend die übersetzte Datei genauso konvertieren:

```bash
./makedocu/docrun docs_T350/T350_manual -h -p
```

**Ergebnis** in `docs_T350/`:
- `T350_manual_en.html` + `T350_manual_en.pdf`

> [!NOTE]
> Die Übersetzung benötigt einen OpenAI API-Key in `secret/keys.inc.php`. Die Bilder im selben Verzeichnis werden automatisch für beide Sprachversionen mitverwendet.

**docrun.bat – Syntax:**

```bash
makedocu/docrun <pfad/dateiname> [-h] [-p]
```

| Argument | Bedeutung |
|----------|-----------|
| `pfad/dateiname` | Pfad zur MD-Datei **ohne** `.md`-Erweiterung (relativ oder absolut) |
| `-h` | HTML-Ausgabe erzeugen |
| `-p` | PDF-Ausgabe erzeugen |
| (beide) | HTML **und** PDF erzeugen |

**CSS-Auswahl:**
Die verwendete `flavoured.css` wird automatisch von `makedocu/` ins Zielverzeichnis kopiert. Die CSS-Variante kann gewählt werden:
- `flavours_css/flavoured_light/flavoured.css` - leichtgewichtiges Design
- `flavours_css/flavoured_medium/flavoured.css` - ausgewogenes Design (Default)

Kopiere die gewünschte Variante nach `makedocu/flavoured.css`.

---

### 4️⃣ PDF erzeugen (manuell mit Pandoc)

Konvertiere Markdown in professionelle PDFs mit **Pandoc**.
Üblicherweise verwendet LaTeX bereits schöne Serifen-Schriftarten, was bei HTML weniger verbreitet ist, da dort eher serifenlose Schriftarten vorherrschen. Dazu kann ggf. eine eigene YAML-Datei die Pandoc-Voreinstellungen ändern. Diese können im Frontmatter oder in einer separaten Datei (Muster in `makedocu/commonpdf.yml`) hinterlegt werden.

Es gibt mehrere LaTeX-Engines für Pandoc ("LuaLaTeX", "XeLaTeX", ...) und nicht jede kann auf jedem System alles. Im Zweifelsfall hilft leider nur Probieren... Die Engine wird mit `--pdf-engine=lualatex` oder `--pdf-engine=xelatex` gesetzt.

**Hinweis:** Hier mein Setup für Windows. Für Linux können evtl. auch andere Emoji-Fonts verwendet werden (z.B. `Noto Color Emoji`, als Mainfont z.B. auch "Helvetica", "Liberation Sans", "Comic Sans MS", ...). Fehlende Fonts werden aufgelistet. **Pandoc** ist bei PDF meist recht langsam (dauert oft mehrere Sekunden, bei HTML dagegen meist viel schneller).


#### 🔹 Mit LuaLaTeX (empfohlen für (farbige) Emojis)

#### 📝 Frontmatter für farbige Emojis

Füge einen Fallback-Font für Emojis hinzu (oder im MetaFile):

```yaml
mainfont: "Arial"
mainfontfallback:
    - "Segoe UI Emoji:mode=harf"
```

**Font-Liste anzeigen:**
```bash
fc-list
```

Direkt:
```bash
pandoc build_blocks/produkt_a_de.md -f gfm+alerts --lua-filter=makedocu/github-alerts.lua --pdf-engine=lualatex 
  -H makedocu/preamble.tex  -o build_blocks/produkt_a_de.pdf
```

Mit separater Meta-Datei (2 Bsp.):
```bash
pandoc build_blocks/produkt_a_de.md -f gfm+alerts --lua-filter=makedocu/github-alerts.lua --pdf-engine=lualatex --metadata-file=makedocu/commonpdf.yml -H makedocu/preamble.tex  -o build_blocks/produkt_a_de.pdf
```

```bash
pandoc build_blocks/testtext_verziert_spoiler.md -f gfm+alerts --lua-filter=makedocu/github-alerts.lua --pdf-engine=lualatex --metadata-file=makedocu/commonpdf.yml -H makedocu/preamble.tex  -o build_blocks/testtext_verziert_spoiler.pdf
```

### 5️⃣ HTML erzeugen (manuell mit Pandoc)

Erstelle standalone HTML-Dateien mit CSS-Styling (*.lua-Filter ist nicht nötig!):

```bash
pandoc build_blocks/produkt_a_de.md -f gfm+alerts --css=flavoured.css --standalone -o build_blocks/produkt_a_de.html
```

Optional (auch) Bilder dazu kopieren:
```
Copy-Item -Recurse -Force img build_blocks/
```

> [!TIP]
> Kopiere die gewünschte CSS-Datei nach `build_blocks/flavoured.css` vor dem ersten Aufruf!
> Im Verzeichnis `flavours_css/` gibt es zwei CSS-Varianten zur Auswahl:
> - **`flavoured_medium/flavoured.css`** – ausgewogenes, modernes Design (Default)
> - **`flavoured_light/flavoured.css`** – leichtgewichtige Variante, gut als Ausgangsbasis für eigene Anpassungen


**Eigenschaften des HTML-Outputs:**
- ✅ Schnelle Konvertierung
- ✅ Native Alert-Unterstützung (kein Lua-Filter nötig)
- ✅ Responsive Design

---

## Über die 🎨 GitHub-Alerts

**AUTODOC** unterstützt GitHub Flavored Markdown mit farbigen Alert-Boxen:

```markdown
> [!NOTE]
> Informative Hinweise in Blau

> [!TIP]
> Praktische Tipps in Grün

> [!IMPORTANT]
> Wichtige Infos in Lila

> [!WARNING]
> Warnungen in Orange

> [!CAUTION]
> Kritische Hinweise in Rot
```

---

## 📌 Nützliche Links

- [Emoji-Liste (Unicode)](https://github.com/Fantantonio/Emoji-List-Unicode)
- [Pandoc Dokumentation](https://pandoc.org/)
- [GitHub Alerts Syntax](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax#alerts)
- [JoKnow auf GitHub](https://github.com/joembedded/AiPlayground)

---

## 💡 Tipps

- **Schneller Workflow:** HTML für Vorschau, PDF für finale Version
- **Font-Probleme:** `fc-list` zeigt verfügbare Schriften
- **Große Dokumente:** PDF-Erstellung kann mehrere Sekunden dauern
- **Embeddings:** MD-Dateien eignen sich perfekt für KI-Assistenten

---

## 🤝 Support

Bei Fragen zu GitHub-Alerts, Pandoc-Filtern, HTML oder CSS:
> Frag **Claude Sonnet** – er kennt sich da sehr gut aus! 🤖

---

*Made with ❤️ by JoEmbedded*
