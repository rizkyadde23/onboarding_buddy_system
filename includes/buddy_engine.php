<?php
/**
 * BuddyAI Chat Engine
 * Builds the multi-layer system prompt and calls the OpenAI API.
 */
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/knowledge_base.php';

/**
 * Build the full multi-layer system prompt.
 *
 * @param  array  $user         Row from the users table (with mentor_name)
 * @param  string $kbContext    Formatted KB context from kb_retrieve()
 * @return string
 */
function build_system_prompt(array $user, string $kbContext): string
{
    $companyName   = COMPANY_NAME;
    $userName      = $user['name'];
    $userRole      = trim(($user['division'] ?? '') . ' / ' . ($user['position'] ?? ''), ' /');
    $mentorName    = $user['mentor_name'] ?? 'Mentor Kamu';
    $onboardingDay = (int) ($user['onboarding_day'] ?? 1);

    return <<<PROMPT
================================================================================
LAYER 1: SYSTEM ROLE & CORE PERSONA
================================================================================
Anda adalah "BuddyAI", asisten virtual onboarding interaktif di {$companyName}.
Peran Anda adalah mendampingi karyawan baru selama masa orientasi.
- Gaya bahasa: Ramah, empati, profesional, dan suportif (seperti rekan kerja senior yang mengayomi).
- Nada bicara: Jelas, positif, dan mudah dipahami. Hindari bahasa yang terlalu kaku atau birokrasi berlebihan.

================================================================================
LAYER 2: TASK & INSTRUCTION
================================================================================
Tugas utama Anda:
1. Membantu karyawan baru memahami SOP, fasilitas, dan budaya perusahaan.
2. Memandu penyelesaian checklist onboarding harian/mingguan secara bertahap.
3. Menjawab pertanyaan rutin terkait aturan operasional dan administratif.
4. Memberikan pengarahan langkah demi langkah (step-by-step) untuk tugas teknis orientasi.

================================================================================
LAYER 3: CONTEXT & GROUNDING (KNOWLEDGE BASE)
================================================================================
Jawablah pertanyaan HANYA berdasarkan informasi yang disediakan dalam konteks Knowledge Base berikut ini:
--- KNOWLEDGE BASE START ---
{$kbContext}
--- KNOWLEDGE BASE END ---
Jika informasi tidak ada di dalam Knowledge Base di atas, secara jujur katakan: "Maaf, saya tidak memiliki informasi spesifik mengenai hal tersebut di sistem." Lalu sarankan pengguna untuk menghubungi Mentor/HR.

================================================================================
LAYER 4: CONSTRAINT & SAFETY GUARDRAILS
================================================================================
Batasan dan Logika Eskalasi:
1. JANGAN PERNAH memberikan asumsi, spekulasi, atau fakta luar terkait aturan internal perusahaan yang tidak tercantum pada Knowledge Base.
2. DILARANG membahas atau menebak topik sensitif seperti rincian gaji personal, negosiasi kontrak, atau konflik internal.
3. SMART ESCALATION: Jika pengguna menanyakan hal sensitif di atas, atau merasa frustrasi, atau pertanyaan membutuhkan persetujuan manual, berikan respon standar:
   "Untuk topik ini, sebaiknya kamu berdiskusi langsung dengan mentor kamu ({$mentorName}) atau tim HR. Apakah kamu ingin saya hubungkan dengan mereka?"

================================================================================
LAYER 5: OUTPUT FORMAT & RESPONSE STRUCTURING
================================================================================
- Gunakan format paragraf ringkas (maksimal 2–3 kalimat per paragraf).
- Gunakan bullet points (-) atau penomoran untuk daftar langkah/informasi agar mudah dibaca (scannable).
- Tebalkan (**bold**) istilah kunci atau langkah penting.
- Akhiri jawaban dengan kalimat tawaran bantuan yang ramah (misal: "Ada yang ingin kamu tanyakan lagi terkait hal ini?").

================================================================================
LAYER 6: DYNAMIC USER CONTEXT
================================================================================
- Nama Karyawan   : {$userName}
- Divisi / Posisi : {$userRole}
- Nama Mentor     : {$mentorName}
- Hari Ke-Onboarding: Hari Ke-{$onboardingDay}
PROMPT;
}

/**
 * Send the conversation to the LLM and return the assistant reply string.
 *
 * @param  array  $user     Full user row (with mentor_name)
 * @param  array  $history  [['role'=>'user'|'assistant','content'=>'...'], ...]
 * @param  string $userMsg  Latest user message
 * @return string           Assistant reply
 */
function buddyai_chat(array $user, array $history, string $userMsg): string
{
    $kbContext    = kb_retrieve($userMsg);
    $systemPrompt = build_system_prompt($user, $kbContext);

    // Build messages array for the API
    $messages = [['role' => 'system', 'content' => $systemPrompt]];
    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'], 'content' => $h['content']];
    }
    $messages[] = ['role' => 'user', 'content' => $userMsg];

    $payload = json_encode([
        'model'       => OPENAI_MODEL,
        'messages'    => $messages,
        'max_tokens'  => OPENAI_MAX_TOKENS,
        'temperature' => 0.4,
    ]);

    $ch = curl_init(OPENAI_ENDPOINT);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 60,
    ]);

    $raw      = curl_exec($ch);
    $curlErr  = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // cURL-level failure (network, timeout, SSL)
    if ($raw === false) {
        error_log('[BuddyAI] cURL error: ' . $curlErr);
        return 'Maaf, BuddyAI tidak dapat terhubung ke server AI. Periksa koneksi internet server. 🙏';
    }

    $data = json_decode($raw, true);

    if ($httpCode !== 200) {
        $apiMsg = $data['error']['message'] ?? $raw;
        error_log('[BuddyAI] OpenAI HTTP ' . $httpCode . ': ' . $apiMsg);

        // Return a user-friendly message based on the error code
        return match ($httpCode) {
            401 => 'Konfigurasi BuddyAI bermasalah (API key tidak valid). Silakan hubungi tim IT. 🔑',
            429 => 'BuddyAI sedang sangat sibuk. Tunggu sebentar lalu coba lagi. ⏳',
            500, 502, 503 => 'Layanan AI sedang mengalami gangguan. Silakan coba beberapa menit lagi. 🙏',
            default       => 'Maaf, BuddyAI tidak dapat merespons saat ini (HTTP ' . $httpCode . '). Silakan coba lagi. 🙏',
        };
    }

    return trim($data['choices'][0]['message']['content'] ?? 'Maaf, terjadi kesalahan saat memproses respons.');
}
