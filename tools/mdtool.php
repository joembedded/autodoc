<?php

/**
 * mdtool.php 
 *
 * This tool is an AI text file/markdown processor. For example, to translate files,
 * summarize them, or transform them in other ways without destroying the possibly
 * existing Markdown structure.
 * Instructions can be specified either directly as a string (option '-i') or via a
 * file (option '-c', for more complex instructions).
 * Without instructions, the input file is copied unchanged (no API call).
 * Output is written either to a file or to stdout (standard output).
 *
 * made by JW via ChatGPT 
 * 
 * Process Markdown via OpenAI Responses API.
 * - Keeps YAML frontmatter untouched
 * - Processes only the markdown body
 *
 *  
 * Usage:
 *   php mdtool.php <inputfile.md> [options] [outputfile.md]
 * 
 * Arguments:
 *   inputfile.md       (mandatory) Input markdown file
 *   outputfile.md      (optional) Output file. If omitted, writes to stdout
 * 
 * Options:
 *   -c <file>          Load instructions from file
 *   -m <model>         Override model name (e.g., 'gpt-4.1-nano', 'gpt-5-mini', 'gpt-5-nano', default: 'gpt-4.1-mini')
 *   -i <string>        Set instructions directly as string
 * 
 * Examples:
 *   php tools/mdtool.php docs/testtext.md build/test.en.md -c tools/translate_de_en.txt
 *   php tools/mdtool.php docs/testtext.md -i "Translate into English" build/test.en.md
 *   php tools/mdtool.php docs/testtext.md -i "Translate into English"
 *   php tools/mdtool.php docs/testtext.md -i "Compact to small summary" 
 *   php tools/mdtool.php docs/testtext.md -m gpt-4.1-nano -c tools/translate_de_en.txt build/test.en.md
 *   php tools/mdtool.php docs/testtext.md build/copy.md
 *   php tools/mdtool.php docs/testtext.md
 *   php tools/mdtool.php docs/testtext.md > output.md
 *
 * Note: If instructions are empty, the input file is copied unchanged to output (no API call).
 *
 * Exit codes:
 *   0 success
 *   2 usage error
 *   3 missing key
 *   4 IO error
 *   5 API error
 */

declare(strict_types=1);

function fail(string $msg, int $code): void
{
    fwrite(STDERR, $msg . PHP_EOL);
    exit($code);
}

/**
 * Call OpenAI Responses API
 * Model suggestion: gpt-4.1-mini (fast/cheap + good instruction following).
 */
$model = 'gpt-4.1-mini';
$instructions = '';

// Parse arguments
if ($argc < 2) {
    fail("Usage: php mdtool.php <inputfile.md> [options] [outputfile.md]", 2);
}

$inPath = $argv[1];
$outPath = null;
$outputToStdout = true;

// Parse options and output file
$i = 2;
while ($i < $argc) {
    $arg = $argv[$i];

    if ($arg === '-c') {
        // Load instructions from file
        if (!isset($argv[$i + 1])) {
            fail("Option -c requires a filename argument", 2);
        }
        $configFile = $argv[$i + 1];
        if (!is_file($configFile)) {
            fail("Config file not found: {$configFile}", 4);
        }
        $instructions = file_get_contents($configFile);
        if ($instructions === false) {
            fail("Failed to read config file: {$configFile}", 4);
        }
        $instructions = trim($instructions);
        $i += 2;
    } elseif ($arg === '-m') {
        // Override model name
        if (!isset($argv[$i + 1])) {
            fail("Option -m requires a model name argument", 2);
        }
        $model = $argv[$i + 1];
        $i += 2;
    } elseif ($arg === '-i') {
        // Set instructions directly
        if (!isset($argv[$i + 1])) {
            fail("Option -i requires an instruction string argument", 2);
        }
        $instructions = $argv[$i + 1];
        $i += 2;
    } else {
        // This must be the output file
        if ($outPath !== null) {
            fail("Multiple output files specified: {$outPath} and {$arg}", 2);
        }
        $outPath = $arg;
        $outputToStdout = false;
        $i++;
    }
}

if (!is_file($inPath)) {
    fail("Input file not found: {$inPath}", 4);
}

$md = file_get_contents($inPath);
if ($md === false) {
    fail("Failed to read input: {$inPath}", 4);
}

/**
 * Split YAML frontmatter (--- ... ---) ONLY if it is at the very top.
 * Keep it untouched.
 */
$frontmatter = '';
$body = $md;

if (preg_match('/\A---\R.*?\R---\R/s', $md, $m)) {
    $frontmatter = $m[0];
    $body = substr($md, strlen($frontmatter));
}


if (trim($instructions) === '') {
    // If instructions are empty, just copy the input to output without API call
    $outText = $body;
} else {
    /**
     * Else: Process via API
     * Load API key from keys.inc.php (adjust path if needed)
     * Expect keys.inc.php defines constant: OPENAI_API_KEY
     */
    include_once __DIR__ . '/../secret/keys.inc.php';
    $apiKey = OPENAI_API_KEY;

    if (!is_string($apiKey) || trim($apiKey) === '') {
        fail("OPENAI_API_KEY missing/empty in secret/keys.inc.php", 3);
    }

    $payload = [
        'model' => $model,
        'instructions' => $instructions,
        'input' => $body, // will turn into $outText if OK
    ];

    $ch = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);

    $resp = curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    // curl_close($ch); // deprecated

    if ($resp === false) {
        fail("cURL error: {$err}", 5);
    }

    $data = json_decode($resp, true);
    if (!is_array($data)) {
        fail("API returned non-JSON (HTTP {$http}):\n{$resp}", 5);
    }

    if ($http < 200 || $http >= 300) {
        $msg = $data['error']['message'] ?? 'Unknown API error';
        fail("OpenAI API error (HTTP {$http}): {$msg}", 5);
    }

    /** 
     * EXTRACTOR: $data -> $outText
     * 
     * Extract text from the response.
     * Responses API returns an array 'output' with content items; we collect output_text chunks.
     */
    $outText = '';

    if (isset($data['output']) && is_array($data['output'])) {
        foreach ($data['output'] as $item) {
            if (!is_array($item)) continue;
            if (($item['type'] ?? '') !== 'message') continue;
            $content = $item['content'] ?? [];
            if (!is_array($content)) continue;

            foreach ($content as $c) {
                if (!is_array($c)) continue;
                if (($c['type'] ?? '') === 'output_text' && isset($c['text']) && is_string($c['text'])) {
                    $outText .= $c['text'];
                }
            }
        }
    }

    $outText = trim($outText);

    if ($outText === '') {
        fail("No output_text found in API response.\nRaw response:\n{$resp}", 5);
    }
}

// Re-assemble file: untouched frontmatter + processed body
$result = $frontmatter . $outText;

if ($outputToStdout) {
    echo $result;
} else {
    if (file_put_contents($outPath, $result) === false) {
        fail("Failed to write output: {$outPath}", 4);
    }
    fwrite(STDERR, "OK: {$outPath} (Total: In: " . strlen($md) . " bytes, Out: " . strlen($result) . " bytes)\n");
}

exit(0);
