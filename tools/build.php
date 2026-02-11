<?php

declare(strict_types=1);

/**
 * Das Teil hier ist schon relativ nützlich:
 * Erstellt Markdown aus einem Rezept durch Expandieren von {{include: pfad/zur/datei.md}} Direktiven.
 * - Löst Pfade relativ zur aktuellen Datei auf.
 * - Unterstützt verschachtelte Includes.
 * - Erkennt zirkuläre Includes.
 * - Setzt Includes in HTML-Kommentare, damit man sie im Output sehen kann.
 * - siehe notes_jw/howto.txt
 * - Die ganzen Standard-Metavariablen von PANDOC stehen auch noch zur Verfügung!
 * 
 * Test-Aufbau:
---
product_name: TestProdukt
heute: "Heute ist ein schöner Tag!  .     "
---

Fas Produkt: {{product_name}}
Das Datum ist {{heute}}

Die Intro:
{{include: ../blocks/intro.md}}

Zur Garantie:
{{include: ../blocks/warranty.md}}

Das Ende!
 */

// Das ist die Hauptfunktion
function buildMarkdown(string $entryFile): string
{
    $seen = [];
    return expandFile($entryFile, $seen);
}

// Einzelnes File expandieren, rekursiv
function expandFile(string $file, array &$seen): string
{
    $real = realpath($file);
    if ($real === false) {
        throw new RuntimeException("Datei nicht gefunden: $file");
    }
    if (isset($seen[$real])) {
        $chain = implode(" -> ", array_keys($seen));
        throw new RuntimeException("Zirkuläres Include erkannt: $real (Kette: $chain)");
    }
    $seen[$real] = true;

    $dir = dirname($real);
    $content = file_get_contents($real);
    if ($content === false) {
        throw new RuntimeException("Kann Datei nicht lesen: $real");
    }

    // Replace include directives
    $pattern = '/\{\{\s*include\s*:\s*([^}]+)\s*\}\}/i';
    $result = preg_replace_callback($pattern, function ($m) use ($dir, &$seen) {
        $relPath = trim($m[1]);
        $target = $dir . DIRECTORY_SEPARATOR . $relPath;
        $expanded = expandFile($target, $seen);
        return "\n<!-- BEGIN include: $relPath -->\n" . $expanded . "\n<!-- END include: $relPath -->\n";
    }, $content);

    unset($seen[$real]);
    return $result ?? $content;
}

// Die stehen am Anfang des Dokuments stehenden Frontmatter-Variablen durch ihre Werte ersetzen
function replaceFrontmatterVariables(string $content): string
{
    $parts = preg_split('/^---\s*$/m', $content, 3);

    if (count($parts) < 3) {
        return $content;
    }

    $frontmatter = $parts[1];   // Nur die Name: Wert Paare
    $body = $parts[2];  // Aller Rest ohne Frontmatter

    $variables = [];
    foreach (preg_split('/\n/', $frontmatter) as $line) {
        if (preg_match('/^(\w+):\s*(.+)$/', trim($line), $m)) {
            // In jedem Fall trimmen, evtl. begrenzen Anführungszeichen, dann die auch noch weg
            $tm = trim(trim($m[2]), '"'); 
            $variables[$m[1]] = $tm;
        }
    }

    foreach ($variables as $key => $value) { // Achtung: PHP behandelt CurlyBraces speziell. (z.B. {$xx})
        $body = str_replace('{{' . $key . '}}', $value, $body);
    }

    return "---\n" . trim($frontmatter) . "\n---\n" . $body;
}



//********** */ CLI usage: **********
// php tools/build.php docs/recipes/produkt-a.md build/produkt-a.md
if ($argc < 3) {
    fwrite(STDERR, "Usage: php {$argv[0]} <recipe.md> <out.md>\n");
    exit(1);
}

$recipe = $argv[1];
$out = $argv[2];

try {
    $md = buildMarkdown($recipe);
    if (!is_dir(dirname($out))) {
        mkdir(dirname($out), 0777, true);
    }

    // Extract and replace frontmatter variables
    $md = replaceFrontmatterVariables($md);
    file_put_contents($out, $md);
    fwrite(STDOUT, "OK: geschrieben nach $out\n");
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . "\n");
    exit(2);
}
