<?php
// =============================================================================
// Smart Slope V2 - Configuration File
// =============================================================================
// IMPORTANT: Add this file to .gitignore to protect your API keys!
// =============================================================================

// -----------------------------------------------------------------------------
// DATABASE CONFIGURATION
// -----------------------------------------------------------------------------
// For XAMPP/MySQL:
//   Default: host=localhost, user=root, password=(empty), dbname=landslide_db
// For production: Use environment variables or secure config management

define('DB_HOST', 'localhost');
define('DB_NAME', 'landslide_db');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $conn = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die('Unable to connect to the database. Please contact the administrator.');
}

// -----------------------------------------------------------------------------
// AI PROVIDER CONFIGURATION
// Get your FREE API keys from these providers:
// -----------------------------------------------------------------------------

// 1. Grok (xAI) - https://console.groq.com
// Note: Grok has no free tier currently, but we keep it for future
function configEnv(string $name, string $default = ''): string
{
    $value = getenv($name);
    if ($value === false || trim($value) === '') {
        $value = $_SERVER[$name] ?? '';
    }
    return trim((string) $value) !== '' ? trim((string) $value) : $default;
}

define('GROK_API_KEY', configEnv('GROK_API_KEY'));
define('GROK_MODEL', configEnv('GROK_MODEL', 'grok-2'));

// 2. Google Gemini - https://aistudio.google.com/apikey
define('GEMINI_API_KEY', configEnv('GEMINI_API_KEY'));
define('GEMINI_MODEL', configEnv('GEMINI_MODEL', 'gemini-3.6-flash'));

// 3. Groq - https://console.groq.com
define('GROQ_API_KEY', configEnv('GROQ_API_KEY'));
define('GROQ_MODEL', configEnv('GROQ_MODEL', 'openai/gpt-oss-120b'));

// 4. Mistral AI - https://console.mistral.ai
define('MISTRAL_API_KEY', configEnv('MISTRAL_API_KEY'));
define('MISTRAL_MODEL', configEnv('MISTRAL_MODEL', 'mistral-small-latest'));

// 5. Cerebras - https://cloud.cerebras.ai
define('CEREBRAS_API_KEY', configEnv('CEREBRAS_API_KEY'));
define('CEREBRAS_MODEL', configEnv('CEREBRAS_MODEL', 'llama-3.1-8b-instant'));

// 6. Cloudflare AI - https://dash.cloudflare.com
define('CLOUDFLARE_API_TOKEN', configEnv('CLOUDFLARE_API_TOKEN'));
define('CLOUDFLARE_ACCOUNT_ID', configEnv('CLOUDFLARE_ACCOUNT_ID'));
define('CLOUDFLARE_MODEL', configEnv('CLOUDFLARE_MODEL', '@cf/meta/llama-3.1-8b-instruct'));

// 7. OpenRouter - https://openrouter.ai/keys
define('OPENROUTER_API_KEY', configEnv('OPENROUTER_API_KEY'));
define('OPENROUTER_MODEL', configEnv('OPENROUTER_MODEL', 'meta-llama/llama-3.1-8b-instruct'));

// 8. Hugging Face - https://huggingface.co/settings/tokens
define('HF_API_TOKEN', configEnv('HF_API_TOKEN'));
define('HF_MODEL', configEnv('HF_MODEL', 'meta-llama/Llama-3.1-8B-Instruct'));

// -----------------------------------------------------------------------------
// APPLICATION SETTINGS
// -----------------------------------------------------------------------------
define('APP_NAME', 'Smart Slope V2');
define('APP_VERSION', '2.0.0');
define('DEBUG_MODE', true);  // Set to false in production

// Error reporting (development only)
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
