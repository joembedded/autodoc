# AUTODOC 📚

Ein automatisiertes Dokumentationssystem von **JoEmbedded**

Dieses Repository dient dazu, Dokumentation automatisiert aus Bausteinen zu erstellen – mit Unterstützung für **Emojis** und **GitHub-Alerts**. Die erstellten Markdown-Dateien können automatisch übersetzt und in verschiedene Formate konvertiert werden.

---

## 🎯 Übersicht

**AUTODOC** ermöglicht einen effizienten Workflow für die Erstellung mehrsprachiger, professioneller Dokumentation:

```
docs/recipes/*.md → build/*.md → Übersetzung → HTML/PDF
```

> [!NOTE]
> MD-Dateien sind optimal für Embeddings. Man kann die MD-Dateien einer Sprache in einen Vector-Store laden, und die KI kann sie z.B. für ein Assistenz-System (wie **JoKnow**) verwenden. Dazu reicht EINE Sprache, da die KI in jeder Sprache antworten kann.

---

## 📁 Projektstruktur

| Verzeichnis | Beschreibung |
|-------------|--------------|
| `docs/` | Quell-Dateien (Blöcke und Rezepte) |
| `build/` | Aller generierter Output (MD, HTML, PDF) |
| `flavoured/` | Hilfs-Dateien und Tools für Konvertierung |
| `tools/` | PHP-Scripts für Build und Übersetzung |
| `secret/` | API-Keys (z.B. für OpenAI) |

---

## 🔧 Workflow

### 1️⃣ Markdown zusammensetzen

Erstelle eine zusammengesetzte Markdown-Datei aus einzelnen Bausteinen:

```bash
php tools/build.php docs/recipes/produkt-a.md build/test.md
```

**Eingabe:** Recipe-Datei mit Include-Anweisungen  
**Ausgabe:** Vollständige MD-Datei in `build/`

---

### 2️⃣ Automatisiert übersetzen (via OpenAI)

Übersetze die Dokumentation automatisch:

```bash
php tools/translate_md.php build/test.md build/test.en.md
```

> [!IMPORTANT]
> Benötigt einen **OpenAI API-Key** in `secret/keys.inc.php`  
> Aktuell: DE→EN (weitere Sprachen: *Todo*)

---

### 3️⃣ PDF erzeugen

Konvertiere Markdown in professionelle PDFs mit **Pandoc**.

#### 🔹 Mit LuaLaTeX (empfohlen für Emojis)

```bash
pandoc build/test.md -f gfm+alerts \
  --lua-filter=flavoured/github-alerts.lua \
  --pdf-engine=lualatex \
  -H flavoured/preamble.tex \
  -o build/test.pdf
```

**Eigenschaften:**
- ✅ **Emoji-Unterstützung** (farbig)
- ✅ **GitHub-Alerts** als farbige Boxen
- ⏱️ Langsamer als XeLaTeX

#### 🔹 Mit XeLaTeX (schneller, ohne Emoji-Farbe)

```bash
pandoc build/test.md -f gfm --pdf-engine=xelatex -o build/test.pdf
```

**Emojis in Monochrom:**
```bash
pandoc build/test.md --pdf-engine=xelatex -V mainfont="Segoe UI Emoji" -o build/test.pdf
```

#### 📝 Frontmatter für Emojis

Füge im Markdown-Header einen Fallback-Font hinzu:

```yaml
---
mainfont: "Times New Roman"
mainfontfallback:
  - "Segoe UI Emoji:mode=harf"
title: Mein Produkt 🚀
---
```

**Tipp:** Font-Liste anzeigen:
```bash
fc-list
```

---

### 4️⃣ HTML erzeugen

Erstelle standalone HTML-Dateien mit CSS-Styling:

```bash
pandoc build/test.md -f gfm+alerts \
  --css=github-alerts.css \
  --standalone \
  -o build/test.html
```

> [!TIP]
> Kopiere `flavoured/github-alerts.css` nach `build/` vor dem ersten Aufruf!

**Eigenschaften:**
- ✅ Schnelle Konvertierung
- ✅ Native Alert-Unterstützung (kein Lua-Filter nötig)
- ✅ Responsive Design

---

## 🎨 GitHub-Alerts

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

### Technische Details

- **HTML:** Native Unterstützung seit Pandoc v3
- **PDF:** Lua-Filter konvertiert Alerts → LaTeX `tcolorbox`
- **Styling:** 
  - HTML: `github-alerts.css`
  - PDF: `preamble.tex` (tcolorbox-Definitionen)

---

## 🛠️ Hilfs-Dateien

### Für PDF (LaTeX)
- `flavoured/preamble.tex` - LaTeX Alert-Boxen Definitionen
- `flavoured/github-alerts.lua` - Pandoc Lua-Filter

### Für HTML
- `flavoured/github-alerts.css` - CSS-Styles für Alerts

---

## 📌 Nützliche Links

- [Emoji-Liste (Unicode)](https://github.com/Fantantonio/Emoji-List-Unicode)
- [Pandoc Dokumentation](https://pandoc.org/)
- [GitHub Alerts Syntax](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax#alerts)

---

## 💡 Tipps

- **Schneller Workflow:** HTML für Vorschau, PDF für finale Version
- **Font-Probleme:** `fc-list` zeigt verfügbare Schriften
- **Große Dokumente:** PDF-Erstellung kann mehrere Sekunden dauern
- **Embeddings:** MD-Dateien eignen sich perfekt für KI-Assistenten

---

## 🤝 Support

Bei Fragen zu GitHub-Alerts oder Pandoc-Filtern:
> Frag **Claude Sonnet** – er kennt sich gut aus! 🤖

---

*Made with ❤️ by JoEmbedded*
