<?php
// test-ai.php — Smart Slope V2 AI Provider Diagnostics
// Drop this file in your project ROOT (next to config.php) and open it in the browser:
//   http://localhost/smart-slope/test-ai.php
// It tests each AI provider and shows the RAW response + error so you can see
// exactly WHY the Default Engine is being used.
// DELETE this file before deploying to production (it exposes key status).

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/AIAnalyzer.php';

header('Content-Type: text/html; charset=utf-8');

function mask($key) {
    if ($key === '') return '<em style="color:#999">(empty — provider will be SKIPPED)</em>';
    $len = strlen($key);
    if ($len <= 8) return '***';
    return htmlspecialchars(substr($key, 0, 4) . '...' . substr($key, -4)) . " ({$len} chars)";
}

// ── Quick test: call each AIAnalyzer provider method ──────
function testProvider($name, $key, $callable) {
    echo "<div style='border:1px solid #ccc;padding:12px;margin:8px 0;border-radius:6px;background:#fff;'>";
    echo "<h3 style='margin:0 0 8px 0;'>$name</h3>";
    echo "<p style='margin:4px 0;'><b>Key:</b> " . mask($key) . "</p>";

    if ($key === '') {
        echo "<p style='color:#e67e00;margin:4px 0;'>⚠ SKIPPED — no key configured. This provider is never tried.</p>";
        echo "</div>";
        return;
    }

    $result = $callable();
    if ($result !== null) {
        echo "<p style='color:green;margin:4px 0;'>✅ SUCCESS — got a response!</p>";
        echo "<pre style='background:#f0fff0;padding:8px;border-radius:4px;max-height:150px;overflow:auto;margin:4px 0;'>" . htmlspecialchars($result) . "</pre>";
    } else {
        echo "<p style='color:red;margin:4px 0;'>❌ FAILED — returned null. See raw test below.</p>";
    }
    echo "</div>";
}

// ── Raw HTTP test with full error visibility ──────────────
// Shows the REAL error instead of silently returning null
function rawTest($name, $url, $headers, $payload, $jsonPath) {
    echo "<div style='border:1px solid #f00;padding:12px;margin:8px 0;border-radius:6px;background:#fff8f8;'>";
    echo "<h4 style='margin:0 0 8px 0;'>🔍 Raw HTTP test: $name</h4>";
    echo "<p style='margin:4px 0;font-family:monospace;font-size:12px;'><b>URL:</b> " . htmlspecialchars($url) . "</p>";

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => $headers,
            'content' => $payload,
            'timeout' => 20,
            'ignore_errors' => true, // ⭐ get the body even on HTTP 401/429
        ],
    ]);

    // NO @ suppression — we WANT to see errors
    $response = file_get_contents($url, false, $ctx);

    if ($response === false) {
        $err = error_get_last();
        echo "<p style='color:red;margin:4px 0;'>❌ file_get_contents FAILED (returned false)</p>";
        echo "<p style='color:red;margin:4px 0;'><b>PHP error:</b> " . htmlspecialchars($err['message'] ?? 'No error message captured') . "</p>";
        echo "<p style='margin:4px 0;'><b>Common causes:</b></p>";
        echo "<ul style='margin:4px 0;color:#c00;'>";
        echo "<li><code>allow_url_fopen = Off</code> in php.ini → file_get_contents can't do HTTP</li>";
        echo "<li>No internet / firewall blocking outbound HTTPS</li>";
        echo "<li>SSL certificate problem (CA bundle not set in php.ini)</li>";
        echo "</ul>";
    } else {
        // Get HTTP response code
        $code = isset($http_response_header[0]) ? $http_response_header[0] : 'Unknown';
        echo "<p style='margin:4px 0;'><b>HTTP status:</b> " . htmlspecialchars($code) . "</p>";

        $json = json_decode($response, true);
        if ($json === null) {
            echo "<p style='color:red;margin:4px 0;'>❌ Response is NOT valid JSON</p>";
            echo "<pre style='background:#fff;padding:8px;border-radius:4px;max-height:100px;overflow:auto;margin:4px 0;'>" . htmlspecialchars($response) . "</pre>";
        } else {
            // Check for error fields common across providers
            if (isset($json['error'])) {
                $errMsg = is_array($json['error']) ? ($json['error']['message'] ?? json_encode($json['error'])) : $json['error'];
                echo "<p style='color:red;margin:4px 0;'><b>API error:</b> " . htmlspecialchars($errMsg) . "</p>";
            }
            // Check for the expected response field
            $content = null;
            eval("\$content = \$json$jsonPath ?? null;");
            if ($content !== null) {
                echo "<p style='color:green;margin:4px 0;'>✅ Found response text at <code>\$json$jsonPath</code></p>";
                echo "<pre style='background:#f0fff0;padding:8px;border-radius:4px;max-height:100px;overflow:auto;margin:4px 0;'>" . htmlspecialchars($content) . "</pre>";
            } else {
                echo "<p style='color:red;margin:4px 0;'>❌ Response field not found at <code>\$json$jsonPath</code></p>";
                echo "<p style='margin:4px 0;'>Full JSON response (look for the text here):</p>";
                echo "<pre style='background:#fff3f3;padding:8px;border-radius:4px;max-height:200px;overflow:auto;margin:4px 0;'>" . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . "</pre>";
            }
        }
    }
    echo "</div>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Smart Slope V2 — AI Diagnostics</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; background: #f5f5f5; line-height: 1.5; }
        h1 { color: #333; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 4px; margin-top: 30px; }
        .warn { background: #fff3cd; padding: 10px; border-radius: 6px; border-left: 4px solid #ffc107; margin: 10px 0; }
        .ok { background: #d4edda; padding: 10px; border-radius: 6px; border-left: 4px solid #28a745; margin: 10px 0; }
        .bad { background: #f8d7da; padding: 10px; border-radius: 6px; border-left: 4px solid #dc3545; margin: 10px 0; }
    </style>
</head>
<body>

<h1>🔧 Smart Slope V2 — AI Provider Diagnostics</h1>
<p style="color:#999;font-size:13px;">Delete this file after testing. It shows key status (masked).</p>

<!-- ── 1. PHP Environment Checks ── -->
<h2>1. PHP Environment Checks</h2>

<?php
$allowUrl = ini_get('allow_url_fopen');
echo $allowUrl
    ? "<div class='ok'>✅ <b>allow_url_fopen = On</b> — file_get_contents CAN make HTTP requests.</div>"
    : "<div class='bad'>❌ <b>allow_url_fopen = Off</b> — THIS IS WHY EVERYTHING FAILS.<br>
       Fix: open <code>xampp/php/php.ini</code>, find <code>allow_url_fopen</code>, set to <code>On</code>, restart Apache.</div>";

if (extension_loaded('openssl')) {
    $caFile = ini_get('openssl.cafile') ?: ini_get('curl.cainfo') ?: '';
    echo "<div class='ok'>✅ <b>OpenSSL extension loaded</b> — HTTPS is available.</div>";
    if ($caFile) {
        echo "<div class='ok'>✅ CA bundle set: <code>" . htmlspecialchars($caFile) . "</code></div>";
    } else {
        echo "<div class='warn'>⚠ <b>No CA bundle configured</b> (openssl.cafile / curl.cainfo empty).<br>
           HTTPS requests MAY fail with SSL certificate verification errors.<br>
           Fix: download <a href='https://curl.se/docs/caextract.html'>cacert.pem</a>, put it in
           <code>xampp/php/extras/ssl/cacert.pem</code>, then in php.ini add:<br>
           <code>curl.cainfo = \"C:\\xampp\\php\\extras\\ssl\\cacert.pem\"</code><br>
           <code>openssl.cafile = \"C:\\xampp\\php\\extras\\ssl\\cacert.pem\"</code><br>
           then restart Apache.</div>";
    }
} else {
    echo "<div class='bad'>❌ <b>OpenSSL extension NOT loaded</b> — HTTPS impossible. Enable <code>extension=openssl</code> in php.ini.</div>";
}

echo "<p><b>PHP version:</b> " . PHP_VERSION . " (need 8.2+)</p>";
echo "<p><b>Current time:</b> " . date('Y-m-d H:i:s') . "</p>";
?>

<!-- ── 2. API Key Status ── -->
<h2>2. API Key Status</h2>
<p>These are the keys loaded from <code>config.php</code>. Empty = provider is skipped entirely.</p>

<?php
$keys = [
    'Grok (xAI)'      => defined('GROK_API_KEY') ? GROK_API_KEY : '',
    'Gemini'          => defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '',
    'Groq'            => defined('GROQ_API_KEY') ? GROQ_API_KEY : '',
    'Mistral AI'      => defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : '',
    'Cerebras'        => defined('CEREBRAS_API_KEY') ? CEREBRAS_API_KEY : '',
    'Cloudflare'      => defined('CLOUDFLARE_API_TOKEN') ? CLOUDFLARE_API_TOKEN : '',
    'OpenRouter'      => defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '',
    'Hugging Face'    => defined('HF_API_TOKEN') ? HF_API_TOKEN : '',
];

echo "<table style='border-collapse:collapse;width:100%;'><tr style='background:#333;color:#fff;'>
      <th style='padding:8px;text-align:left;'>Provider</th>
      <th style='padding:8px;text-align:left;'>Key status</th></tr>";
$row = 0;
foreach ($keys as $name => $key) {
    $bg = $row++ % 2 ? '#f9f9f9' : '#fff';
    echo "<tr style='background:$bg;'><td style='padding:8px;'>$name</td>
          <td style='padding:8px;'>" . mask($key) . "</td></tr>";
}
echo "</table>";

if (defined('CLOUDFLARE_ACCOUNT_ID')) {
    echo "<p><b>Cloudflare Account ID:</b> " . mask(CLOUDFLARE_ACCOUNT_ID) . "</p>";
}

$configuredCount = count(array_filter($keys));
echo "<p><b>$configuredCount of 8 providers</b> have a key configured.</p>";
if ($configuredCount === 0) {
    echo "<div class='bad'>❌ <b>NO KEYS AT ALL</b> — that's why only the Default Engine works.
          Add at least one key in config.php.</div>";
}
?>

<!-- ── 3. Quick Test via AIAnalyzer ── -->
<h2>3. Quick Test — Each Provider via AIAnalyzer</h2>
<p>Using a simple test prompt. Green = working, Red = failed (see raw test below for the reason).</p>

<?php
// The AIAnalyzer methods are private static, so we use Reflection to access them
$testPrompt = "Say 'Hello' in one sentence. Nothing else.";

// Helper to invoke a private static method
function callPrivate($class, $method, $arg) {
    if (!method_exists($class, $method)) return null;
    $ref = new ReflectionMethod($class, $method);
    $ref->setAccessible(true);
    return $ref->invoke(null, $arg);
}

testProvider('Grok (xAI)',
    defined('GROK_API_KEY') ? GROK_API_KEY : '',
    fn() => callPrivate('AIAnalyzer', 'callGrok', $testPrompt));

testProvider('Gemini (Google)',
    defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '',
    fn() => callPrivate('AIAnalyzer', 'callGemini', $testPrompt));

testProvider('Groq',
    defined('GROQ_API_KEY') ? GROQ_API_KEY : '',
    fn() => callPrivate('AIAnalyzer', 'callGroq', $testPrompt));

testProvider('Mistral AI',
    defined('MISTRAL_API_KEY') ? MISTRAL_API_KEY : '',
    fn() => callPrivate('AIAnalyzer', 'callMistral', $testPrompt));

testProvider('Cerebras',
    defined('CEREBRAS_API_KEY') ? CEREBRAS_API_KEY : '',
    fn() => callPrivate('AIAnalyzer', 'callCerebras', $testPrompt));

testProvider('Cloudflare AI',
    (defined('CLOUDFLARE_API_TOKEN') ? CLOUDFLARE_API_TOKEN : '') &&
    (defined('CLOUDFLARE_ACCOUNT_ID') ? CLOUDFLARE_ACCOUNT_ID : ''),
    fn() => callPrivate('AIAnalyzer', 'callCloudflare', $testPrompt));

testProvider('OpenRouter',
    defined('OPENROUTER_API_KEY') ? OPENROUTER_API_KEY : '',
    fn() => callPrivate('AIAnalyzer', 'callOpenRouter', $testPrompt));

testProvider('Hugging Face',
    defined('HF_API_TOKEN') ? HF_API_TOKEN : '',
    fn() => callPrivate('AIAnalyzer', 'callHuggingFace', $testPrompt));
?>

<!-- ── 4. Raw HTTP Tests ── -->
<h2>4. Raw HTTP Tests — See the Real Error</h2>
<p>These send a real request and show the <b>full response body and HTTP status code</b>,
so you can see exactly why a provider failed (wrong key, rate limit, model not found, SSL error, etc.).</p>

<?php
// ── Groq ──
if (defined('GROQ_API_KEY') && GROQ_API_KEY !== '') {
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    $h = "Content-Type: application/json\r\nAuthorization: Bearer " . GROQ_API_KEY . "\r\n";
    $p = json_encode(['model' => defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile',
        'messages' => [['role' => 'user', 'content' => 'Say hello.']], 'max_tokens' => 10]);
    rawTest('Groq', $url, $h, $p, "['choices'][0]['message']['content']");
}

// ── Gemini ──
if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.0-flash';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . GEMINI_API_KEY;
    $h = "Content-Type: application/json\r\n";
    $p = json_encode(['contents' => [['parts' => [['text' => 'Say hello.']]]]]);
    rawTest('Gemini', $url, $h, $p, "['candidates'][0]['content']['parts'][0]['text']");
}

// ── Mistral ──
if (defined('MISTRAL_API_KEY') && MISTRAL_API_KEY !== '') {
    $url = 'https://api.mistral.ai/v1/chat/completions';
    $h = "Content-Type: application/json\r\nAuthorization: Bearer " . MISTRAL_API_KEY . "\r\n";
    $p = json_encode(['model' => defined('MISTRAL_MODEL') ? MISTRAL_MODEL : 'mistral-small-latest',
        'messages' => [['role' => 'user', 'content' => 'Say hello.']], 'max_tokens' => 10]);
    rawTest('Mistral AI', $url, $h, $p, "['choices'][0]['message']['content']");
}

// ── Cerebras ──
if (defined('CEREBRAS_API_KEY') && CEREBRAS_API_KEY !== '') {
    $url = 'https://api.cerebras.ai/v1/chat/completions';
    $h = "Content-Type: application/json\r\nAuthorization: Bearer " . CEREBRAS_API_KEY . "\r\n";
    $p = json_encode(['model' => defined('CEREBRAS_MODEL') ? CEREBRAS_MODEL : 'llama-3.1-8b-instant',
        'messages' => [['role' => 'user', 'content' => 'Say hello.']], 'max_tokens' => 10]);
    rawTest('Cerebras', $url, $h, $p, "['choices'][0]['message']['content']");
}

// ── Hugging Face ──
if (defined('HF_API_TOKEN') && HF_API_TOKEN !== '') {
    $url = 'https://router.huggingface.co/v1/chat/completions';
    $h = "Content-Type: application/json\r\nAuthorization: Bearer " . HF_API_TOKEN . "\r\n";
    $p = json_encode(['model' => defined('HF_MODEL') ? HF_MODEL : 'meta-llama/Meta-Llama-3.1-8B-Instruct',
        'messages' => [['role' => 'user', 'content' => 'Say hello.']], 'max_tokens' => 10]);
    rawTest('Hugging Face', $url, $h, $p, "['choices'][0]['message']['content']");
}

// ── OpenRouter ──
if (defined('OPENROUTER_API_KEY') && OPENROUTER_API_KEY !== '') {
    $url = 'https://openrouter.ai/api/v1/chat/completions';
    $h = "Content-Type: application/json\r\nAuthorization: Bearer " . OPENROUTER_API_KEY . "\r\n";
    $p = json_encode(['model' => defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'meta-llama/llama-3.3-70b-instruct:free',
        'messages' => [['role' => 'user', 'content' => 'Say hello.']], 'max_tokens' => 10]);
    rawTest('OpenRouter', $url, $h, $p, "['choices'][0]['message']['content']");
}
?>

<!-- ── 5. Diagnosis Summary ── -->
<h2>5. Diagnosis & Fixes</h2>
<div style="background:#eef;padding:12px;border-radius:6px;">
<h3>Why you're seeing the Default Engine:</h3>
<ol>
<li><b>No API keys set</b> — keys are empty in <code>config.php</code>. The
<code>getenv('...') ?: ''</code> returns empty if the env var isn't set on XAMPP.
Fix: paste your key directly as the fallback:
<code>getenv('GROQ_API_KEY') ?: 'gsk-your-key'</code></li>
<li><b><code>allow_url_fopen = Off</code></b> — THE #1 XAMPP issue.
<code>file_get_contents()</code> can't make HTTP requests at all. Check section 1 above.
Fix in <code>php.ini</code>, restart Apache.</li>
<li><b>SSL certificate error</b> — No CA bundle configured, so HTTPS fails silently.
Check section 1. Fix: download <code>cacert.pem</code> and set it in <code>php.ini</code>.</li>
<li><b>Wrong / expired API key</b> — The API returns HTTP 401 with an error.
Check the raw test in section 4 to see the exact error message.</li>
<li><b>Wrong model name</b> — The API returns "model not found".
Check the raw test response.</li>
<li><b>Rate limited (HTTP 429)</b> — You hit the free tier limit. Wait a minute and retry.</li>
<li><b>Network blocked</b> — Windows firewall or no internet. The raw test will show
"file_get_contents FAILED".</li>
</ol>
<h3>After fixing:</h3>
<p>Re-open <code>test-ai.php</code> in the browser. When a provider shows
<b>✅ SUCCESS</b>, it will be used on the dashboard instead of the Default Engine.</p>
</div>

</body>
</html>