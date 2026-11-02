<?php
/**
 * Application-wide configuration
 */

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
