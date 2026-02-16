# AUTODOC 📚

An automated documentation system by **JoEmbedded**

This repository is designed to automatically create documentation from modular building blocks – with support for **Emojis** and **GitHub-Alerts**. The created Markdown files can be translated, compacted, or otherwise processed with AI support and converted into various formats.

---

## 🎯 Overview

**AUTODOC** enables an efficient workflow for creating multilingual, professional documentation with optional AI integration. **AUTODOC** can also be used for many other purposes: summaries, embellishments, ...,
as it is easily integrated as a command-line tool.

Example workflow:
```bash
docs/recipes/*.md → build/*.md → AI-Processing → HTML/PDF
```

> [!NOTE]
> MD files are optimal for embeddings. You can load the MD files of one language into a vector store, and the AI can use them for assistant systems (like [**JoKnow**](https://joembedded.de/x3/aiplay/sw/jolaunch.html)). One language is sufficient, as the AI can respond in almost any (other) language.

As a help, some of the files mentioned in the documentation are left in the output.

---

## 📁 Project Structure

| Directory | Description |
|-----------|-------------|
| `docs/` | Source files (blocks and recipes) |
| `build/` | All generated output (MD, HTML, PDF) |
| `flavoured/` | Helper files (GitHub-Flavored-Markdown, ...) and conversion tools |
| `tools/` | PHP scripts for build and AI processing |
| `secret/` | API keys (e.g., for OpenAI) |

---

## 🔧 Detailed Workflow

### 1️⃣ Assemble Markdown

Create a composite Markdown file from individual building blocks,
the sources are in German:

```bash
php tools/build.php docs/recipes/produkt-a.md build/produkt_a_de.md
```

**Input:** Recipe file with include directives  
**Output:** Complete MD file in `build/`

> [!TIP]
> MD/TXT files are optimal material for embedding in vector stores.
> A few test MDs were already previously built into [**JoKnow**](https://joembedded.de/x3/aiplay/sw/jolaunch.html). Since the KI can answer in many different languages, only one version in one langugae is sufficient. For testing how well the vector store handles them: Top!


---

### 2️⃣ AI-Supported Document Processing (via OpenAI)

**mdtool.php** is a flexible tool for AI-based processing of Markdown files, e.g., translating, compacting, adding summaries.

#### 📖 Syntax

```bash
php tools/mdtool.php <inputfile.md> [options] [outputfile.md]
```

**Parameters:**
- `inputfile.md` - Input file (mandatory)
- `outputfile.md` - Output file (optional, otherwise stdout)

**Options** (details see PHP source code):
- `-c <file>` - Load instructions from file. Useful e.g., for professional translations where e.g., formatting must be considered. Example here: `tools/translate_de_en.txt`
- `-i "<text>"` - Specify instructions directly. Useful for small tasks, e.g., spell checking for plain text blocks or creating a summary
- `-m <model>` - Override model (default: `gpt-4.1-mini`)
  (Note: `gpt-4.1-mini` is perfect for technical translations, `nano` is sometimes to relaxed, `gpt-5` normally oversized. For prompts: Ask **ChatGPT** )

#### 📝 Examples

**Translate (DE→EN):**
```bash
php tools/mdtool.php build/produkt_a_de.md build/produkt_a_en.md -c tools/translate_de_en.txt
```

**Compact:**
```bash
php tools/mdtool.php build/test.md build/test_compact.md -i "Compact to small summary"
```

**Direct output (stdout via > to file):**
```bash
php tools/mdtool.php build/test.md -i "Translate to English" > build/output.md
```

**'Embellished' version with spoiler:**
```bash
php tools/mdtool.php docs/testtext.md build/testtext_verziert_spoiler.md -i "Add a brief summary as GitHub-Alert '> [!NOTE] >' at the beginning of the file, then add the original text afterward and beautify the entire text with Emojis"
```

**Use different model:**
```bash
php tools/mdtool.php build/test.md build/test.en.md -m gpt-4.1-nano -c tools/translate_de_en.txt
```

**Copy file (without AI processing):**
```bash
php tools/mdtool.php build/test.md build/test_copy.md
```

> [!IMPORTANT]
> AI processing requires an **OpenAI API key** in `secret/keys.inc.php`.  
> Without instructions (`-c` or `-i`), the file is only copied (no API call).

> [!TIP]
> YAML frontmatter always remains unchanged – only the document body is processed.

---

### 3️⃣ Generate PDF

Convert Markdown to professional PDFs with **Pandoc**.
LaTeX typically already uses beautiful serif fonts, which is less common in HTML, where sans-serif fonts predominate. If needed, a custom YAML file can change Pandoc's default settings. These can be stored in the frontmatter or in a separate file (template in `flavoured/commonpdf.yml`).

There are several LaTeX engines for Pandoc ("LuaLaTeX", "XeLaTeX", ...) and not every one can do everything on every system. In case of doubt, unfortunately, only trial and error helps... The engine is set with `--pdf-engine=lualatex` or `--pdf-engine=xelatex`.

**Note:** This is my setup for Windows. For Linux, other emoji fonts can also be used (e.g., `Noto Color Emoji`, as mainfont e.g., "Helvetica", "Liberation Sans", "Comic Sans MS", ...). Missing fonts will be listed. **Pandoc** is usually quite slow for PDFs (often takes several seconds, whereas HTML is usually much faster).


#### 🔹 With LuaLaTeX (recommended for (colored) Emojis)

#### 📝 Frontmatter for colored Emojis

Add a fallback font for emojis (or in the metafile):

```yaml
mainfont: "Arial"
mainfontfallback:
    - "Segoe UI Emoji:mode=harf"
```

**Display font list:**
```bash
fc-list
```

Direct:
```bash
pandoc build/test.md -f gfm+alerts --lua-filter=flavoured/github-alerts.lua --pdf-engine=lualatex 
  -H flavoured/preamble.tex  -o build/test.pdf
```

With separate metadata file (2 examples):
```bash
pandoc build/produkt_a_de.md -f gfm+alerts --lua-filter=flavoured/github-alerts.lua --pdf-engine=lualatex --metadata-file=flavoured/commonpdf.yml -H flavoured/preamble.tex  -o build/produkt_a_de.pdf
```

```bash
pandoc build/testtext_verziert_spoiler.md -f gfm+alerts --lua-filter=flavoured/github-alerts.lua --pdf-engine=lualatex --metadata-file=flavoured/commonpdf.yml -H flavoured/preamble.tex  -o build/testtext_verziert_spoiler.pdf
```

### 4️⃣ Generate HTML

Create standalone HTML files with CSS styling:

```bash
pandoc build/test.md -f gfm+alerts --css=flavoured.css --standalone  -o build/test.html
```

> [!TIP]
> Copy `flavoured/flavoured.css` to `build/` before the first call!
> In the `flavoured/` directory, there are two CSS files:
> - **`flavoured_medium.css`** – ready to use with modern design
> - **`flavoured_light.css`** – good starting point for your own customizations
> 
> The CSS is already visually optimized for modern, responsive display on desktop and mobile.
> `pandoc` itself offers few options for HTML. Therefore, the `.css` is well suited.


**Properties of HTML output:**
- ✅ Fast conversion
- ✅ Native alert support (no Lua filter needed)
- ✅ Responsive design

---

## About 🎨 GitHub-Alerts

**AUTODOC** supports GitHub Flavored Markdown with colored alert boxes:

```markdown
> [!NOTE]
> Informative notes in blue

> [!TIP]
> Practical tips in green

> [!IMPORTANT]
> Important information in purple

> [!WARNING]
> Warnings in orange

> [!CAUTION]
> Critical notices in red
```

---

## 📌 Useful Links

- [Emoji List (Unicode)](https://github.com/Fantantonio/Emoji-List-Unicode)
- [Pandoc Documentation](https://pandoc.org/)
- [GitHub Alerts Syntax](https://docs.github.com/en/get-started/writing-on-github/getting-started-with-writing-and-formatting-on-github/basic-writing-and-formatting-syntax#alerts)
- [JoKnow on GitHub](https://github.com/joembedded/AiPlayground)

---

## 💡 Tips

- **Fast workflow:** HTML for preview, PDF for final version
- **Font problems:** `fc-list` shows available fonts
- **Large documents:** PDF creation can take several seconds
- **Embeddings:** MD files are perfect for AI assistants

---

## 🤝 Support

For questions about GitHub-Alerts, Pandoc filters, HTML, or CSS:
> Ask **Claude Sonnet** – he knows a lot about this! 🤖

---

*Made with ❤️ by JoEmbedded*
