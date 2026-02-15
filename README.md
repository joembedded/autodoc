# AUTODOC 📚

Ein automatisiertes Dokumentationssystem von **JoEmbedded**

Dieses Repository dient dazu, Dokumentation automatisiert aus Bausteinen zu erstellen – mit Unterstützung für **Emojis** und **GitHub-Alerts**. Die erstellten Markdown-Dateien können mit KI-Unterstützung übersetzt, kompaktiert oder anderweitig verarbeitet und in verschiedene Formate konvertiert werden.

---

## 🎯 Übersicht

**AUTODOC** ermöglicht einen effizienten Workflow für die Erstellung mehrsprachiger, professioneller Dokumentation unter optionalem KI-Einsatz. **AUTODOC** kann aber auch für vieles andere verwendet werden: Zusammenfassungen, Verzierungen, ...,
da es sich als Kommandozeilen-Tool leicht integrieren lässt.

Workflow z.B.:
```bash
docs/recipes/*.md → build/*.md → KI-Verarbeitung → HTML/PDF
```

> [!NOTE]
> MD-Dateien sind optimal für Embeddings. Man kann die MD-Dateien einer Sprache in einen Vector-Store laden, und die KI kann sie z.B. für ein Assistenz-System (wie [**JoKnow**](https://joembedded.de/x3/aiplay/sw/jolaunch.html) ) verwenden. Dazu reicht EINE Sprache, da die KI in nahezu jeder (anderen) Sprache antworten kann.

Als Hilfe sind einige der in der Doku erwähnten Dateien im Output belassen.

---

## 📁 Projektstruktur

| Verzeichnis | Beschreibung |
|-------------|--------------|
| `docs/` | Quell-Dateien (Blöcke und Rezepte) |
| `build/` | Gesamter generierter Output (MD, HTML, PDF) |
| `flavoured/` | Hilfs-Dateien (GitHub-Flavored-Markdown, ...) und Tools für Konvertierung |
| `tools/` | PHP-Scripts für Build und KI-Verarbeitung |
| `secret/` | API-Keys (z.B. für OpenAI) |

---

## 🔧 Workflow im Detail

### 1️⃣ Markdown zusammensetzen

Erstelle eine zusammengesetzte Markdown-Datei aus einzelnen Bausteinen,
die Quellen sind auf Deutsch;

```bash
php tools/build.php docs/recipes/produkt-a.md build/produkt_a_de.md
```

**Eingabe:** Recipe-Datei mit Include-Anweisungen  
**Ausgabe:** Vollständige MD-Datei in `build/`

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

#### 📝 Beispiele

**Übersetzen (DE→EN):**
```bash
php tools/mdtool.php build/produkt_a_de.md build/produkt_a_en.md -c tools/translate_de_en.txt
```

**Kompaktieren:**
```bash
php tools/mdtool.php build/test.md build/test_compact.md -i "Compact to small summary"
```

**Direkte Ausgabe (stdout via > in Datei):**
```bash
php tools/mdtool.php build/test.md -i "Translate to English" > build/output.md
```

**'Verzierte' Version mit Spoiler:**
```bash
php tools/mdtool.php docs/testtext.md build/testtext_verziert_spoiler.md -i "Füge am Anfang der Datei eine kurze Zusammenfassung als GitHub-Alert '> [!NOTE] >' ein, füge dann den Originaltext hintenan und verschöndere den gesamten Text mit Emojis"
```

**Anderes Model verwenden:**
```bash
php tools/mdtool.php build/test.md build/test.en.md -m gpt-4.1-nano -c tools/translate_de_en.txt
```

**Datei kopieren (ohne KI-Verarbeitung):**
```bash
php tools/mdtool.php build/test.md build/test_copy.md
```

> [!IMPORTANT]
> KI-Verarbeitung benötigt einen **OpenAI API-Key** in `secret/keys.inc.php`.  
> Ohne Instructions (`-c` oder `-i`) wird die Datei nur kopiert (kein API-Call).

> [!TIP]
> YAML-Frontmatter bleibt immer unverändert – nur der Dokumenten-Body wird verarbeitet.

---

### 3️⃣ PDF erzeugen

Konvertiere Markdown in professionelle PDFs mit **Pandoc**.
Üblicherweise verwendet LaTeX bereits schöne Serifen-Schriftarten, was bei HTML weniger verbreitet ist, da dort eher serifenlose Schriftarten vorherrschen. Dazu kann ggf. eine eigene YAML-Datei die Pandoc-Voreinstellungen ändern. Diese können im Frontmatter oder in einer separaten Datei (Muster in `flavoured/commonpdf.yml`) hinterlegt werden.

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
pandoc build/test.md -f gfm+alerts --lua-filter=flavoured/github-alerts.lua --pdf-engine=lualatex 
  -H flavoured/preamble.tex  -o build/test.pdf
```

Mit separater Meta-Datei z.B.:
```bash
pandoc build/produkt_a_de.md -f gfm+alerts --lua-filter=flavoured/github-alerts.lua --pdf-engine=lualatex --metadata-file=flavoured/commonpdf.yml -H flavoured/preamble.tex  -o build/produkt_a_de.pdf
```


### 4️⃣ HTML erzeugen

Erstelle standalone HTML-Dateien mit CSS-Styling:

```bash
pandoc build/test.md -f gfm+alerts --css=flavoured.css --standalone  -o build/test.html
```

> [!TIP]
> Kopiere `flavoured/flavoured.css` nach `build/` vor dem ersten Aufruf!
> Im Verzeichnis `flavoured/` gibt es zwei CSS-Dateien:
> - **`flavoured_medium.css`** – sofort einsatzbereit mit modernem Design
> - **`flavoured_light.css`** – gute Ausgangsbasis für eigene Anpassungen
> 
> Das CSS ist bereits optisch optimiert für moderne, responsive Darstellung auf Desktop und Mobile.
> `pandoc` selbst bietet wenig Optionen fürs HTML. Daher ist die `.css` gut geeignet.


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
