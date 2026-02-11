<?php
/**
 * Gebaut von JW via ChatGPT 
 * 
 * Translate Markdown (DE->EN) via OpenAI Responses API.
 * - Keeps YAML frontmatter untouched
 * - Translates only the markdown body
 *
 * Funktioniert anscheinend einwandfrei.
 * Interessant: EXTRACTOR am Ende
 *  
 * Usage:
 *   php tools/translate_md.php input.md output.en.md
 *
 * Exit codes:
 *   0 success
 *   2 usage error
 *   3 missing key
 *   4 IO error
 *   5 API error
 */

declare(strict_types=1);

function fail(string $msg, int $code): void {
    fwrite(STDERR, $msg . PHP_EOL);
    exit($code);
}

if ($argc < 3) {
    fail("Usage: php tools/translate_md.php input.md output.en.md", 2);
}

$inPath  = $argv[1];
$outPath = $argv[2];

if (!is_file($inPath)) {
    fail("Input file not found: {$inPath}", 4);
}

$md = file_get_contents($inPath);
if ($md === false) {
    fail("Failed to read input: {$inPath}", 4);
}

/**
 * Load API key from keys.php (adjust path if needed)
 * Expect keys.php returns array: ['OPENAI_API_KEY' => '...']
 */
// Load API keys
include_once __DIR__ . '/../secret/keys.inc.php';
$apiKey = OPENAI_API_KEY;

if (!is_string($apiKey) || trim($apiKey) === '') {
    fail("OPENAI_API_KEY missing/empty in secret/keys.inc.php", 3);
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

/**
 * Call OpenAI Responses API
 * Model suggestion: gpt-4.1-mini (fast/cheap + good instruction following). :contentReference[oaicite:1]{index=1}
 */
$model = 'gpt-4.1-mini';

$instructions = <<<TXT
You are a professional technical translator.

Task:
- Translate the provided Markdown BODY from German to English.
- Keep Markdown structure exactly (headings, lists, tables, links).
- Do NOT translate code blocks (fenced ``` or ~~~) and do NOT translate inline code (`like this`).
- Do NOT translate URLs.
- Preserve product names, variable names, filenames, identifiers exactly.
- Do not add commentary. Output ONLY the translated Markdown body.
TXT;

$payload = [
    'model' => $model,
    'instructions' => $instructions,
    'input' => $body,
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
 * Responses API returns an array 'output' with content items; we collect output_text chunks. :contentReference[oaicite:2]{index=2}
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

// Re-assemble file: untouched frontmatter + translated body
$result = $frontmatter . $outText;
if (file_put_contents($outPath, $result) === false) {
    fail("Failed to write output: {$outPath}", 4);
}

fwrite(STDERR, "OK: {$outPath} (Total: In: " . strlen($md) . " bytes, Out: " . strlen($result) . " bytes)\n");
exit(0);
