<?php
/**
 * Application-wide configuration
 */

// ── Load .env file (if present) ──────────────────────────────────────────────
(function () {
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile)) return;

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        // Skip comments
        if ($line === '' || $line[0] === '#') continue;
        // Must contain '='
        if (strpos($line, '=') === false) continue;

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Strip surrounding quotes (' or ")
        if (strlen($value) >= 2 &&
            (($value[0] === '"'  && $value[-1] === '"') ||
             ($value[0] === "'"  && $value[-1] === "'"))) {
            $value = substr($value, 1, -1);
        }
        // Only set if not already defined by the real environment
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
})();

// ── OpenAI / compatible LLM ─────────────────────────────────────────────────
define('OPENAI_API_KEY',  getenv('OPENAI_API_KEY') ?: 'sk-REPLACE_WITH_YOUR_KEY');
define('OPENAI_MODEL',    'gpt-4o-mini');
define('OPENAI_ENDPOINT', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MAX_TOKENS', 800);

// ── Company meta ────────────────────────────────────────────────────────────
define('COMPANY_NAME', 'PT Maju Bersama');

// ── Session ─────────────────────────────────────────────────────────────────
define('SESSION_LIFETIME', 3600 * 8);   // 8 hours

// ── Knowledge base retrieval ─────────────────────────────────────────────────
define('KB_MAX_RESULTS', 5);            // max KB articles injected into prompt

// ── Timezone ─────────────────────────────────────────────────────────────────
date_default_timezone_set('Asia/Jakarta');
