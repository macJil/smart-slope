<?php
// classes/AIAnalyzer.php
// Multi-provider AI analysis with automatic fallback.
//
// Fallback chain (8 providers + default):
//   Grok → Gemini → Groq → Mistral → Cerebras → Cloudflare
//   → OpenRouter → Hugging Face → Default Engine
//
// Each provider is tried in order. If a key is empty or the API call
// fails, the next provider is tried. If all fail, the built-in default
// engine generates a scripted analysis from the weather data.
//
// The AI does NOT determine the risk — it only explains what
// RiskEngine already calculated.

class AIAnalyzer
{
    /**
     * Analyze weather + risk data using the first available AI provider.
     *
     * @param array $d  Keys: location_name, rainfall_mm, humidity,
     *                  temperature, pressure, wind_speed, soil_moisture,
     *                  risk_score, risk_level
     * @return array  ['success' => bool, 'analysis' => string, 'provider' => string]
     */
    public static function analyze(array $d): array
    {
        $prompt = self::buildPrompt($d);

        // ── Try each provider in fallback order ──────────
        // Each call*() returns the analysis text, or null on failure.

        // 1. Grok (xAI) — Primary
        if (defined('GROK_API_KEY') && GROK_API_KEY !== '') {
            $result = self::callGrok($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Grok (xAI)'];
            }
        }

        // 2. Gemini (Google) — Fallback 1
        if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
            $result = self::callGemini($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Gemini (Google)'];
            }
        }

        // 3. Groq — Fallback 2
        if (defined('GROQ_API_KEY') && GROQ_API_KEY !== '') {
            $result = self::callGroq($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Groq'];
            }
        }

        // 4. Mistral AI — Fallback 3
        if (defined('MISTRAL_API_KEY') && MISTRAL_API_KEY !== '') {
            $result = self::callMistral($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Mistral AI'];
            }
        }

        // 5. Cerebras — Fallback 4
        if (defined('CEREBRAS_API_KEY') && CEREBRAS_API_KEY !== '') {
            $result = self::callCerebras($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Cerebras'];
            }
        }

        // 6. Cloudflare Workers AI — Fallback 5
        if (defined('CLOUDFLARE_API_TOKEN') && CLOUDFLARE_API_TOKEN !== ''
            && defined('CLOUDFLARE_ACCOUNT_ID') && CLOUDFLARE_ACCOUNT_ID !== '') {
            $result = self::callCloudflare($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Cloudflare AI'];
            }
        }

        // 7. OpenRouter — Fallback 6
        if (defined('OPENROUTER_API_KEY') && OPENROUTER_API_KEY !== '') {
            $result = self::callOpenRouter($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'OpenRouter'];
            }
        }

        // 8. Hugging Face — Fallback 7
        if (defined('HF_API_TOKEN') && HF_API_TOKEN !== '') {
            $result = self::callHuggingFace($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Hugging Face'];
            }
        }

        // 9. Default Engine — always works (no API key needed)
        return [
            'success'  => true,
            'analysis' => self::defaultAnalysis($d),
            'provider' => 'Default Engine',
        ];
    }

    // ════════════════════════════════════════════════════════
    //  SHARED HELPER — OpenAI-compatible chat completions
    //  Used by: Grok, Groq, Mistral, Cerebras, OpenRouter,
    //           Hugging Face (all share the same API format)
    // ════════════════════════════════════════════════════════

    /**
     * Call any OpenAI-compatible chat completions endpoint.
     *
     * @param string $url      Full API endpoint URL
     * @param string $apiKey    Bearer token
     * @param string $model     Model name
     * @param string $prompt    The user prompt
     * @return string|null      Response text, or null on failure
     */
    private static function callOpenAICompatible(string $url, string $apiKey, string $model, string $prompt): ?string
    {
        $payload = json_encode([
            'model'    => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 400,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n"
                          . "Authorization: Bearer " . $apiKey . "\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n"
                              . "Authorization: Bearer " . $apiKey . "\r\n",
                'content'       => $payload,
                'timeout'       => 15,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? null;
        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 1: Grok (xAI) — OpenAI-compatible
    // ════════════════════════════════════════════════════════

    private static function callGrok(string $prompt): ?string
    {
        return self::callOpenAICompatible(
            'https://api.x.ai/v1/chat/completions',
            GROK_API_KEY,
            defined('GROK_MODEL') ? GROK_MODEL : 'grok-2',
            $prompt
        );
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 2: Gemini (Google) — unique format
    // ════════════════════════════════════════════════════════

    private static function callGemini(string $prompt): ?string
    {
        $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3.6-flash';
        $url   = 'https://generativelanguage.googleapis.com/v1beta/models/'
               . $model . ':generateContent?key=' . GEMINI_API_KEY;

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'    => 0.7,
                'maxOutputTokens' => 400,
            ],
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\n",
                'content'       => $payload,
                'timeout'       => 15,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 3: Groq — OpenAI-compatible  ★ NEW
    // ════════════════════════════════════════════════════════

    private static function callGroq(string $prompt): ?string
    {
        return self::callOpenAICompatible(
            'https://api.groq.com/openai/v1/chat/completions',
            GROQ_API_KEY,
            defined('GROQ_MODEL') ? GROQ_MODEL : 'llama-3.3-70b-versatile',
            $prompt
        );
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 4: Mistral AI — OpenAI-compatible  ★ NEW
    // ════════════════════════════════════════════════════════

    private static function callMistral(string $prompt): ?string
    {
        return self::callOpenAICompatible(
            'https://api.mistral.ai/v1/chat/completions',
            MISTRAL_API_KEY,
            defined('MISTRAL_MODEL') ? MISTRAL_MODEL : 'mistral-small-latest',
            $prompt
        );
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 5: Cerebras — OpenAI-compatible  ★ NEW
    // ════════════════════════════════════════════════════════

    private static function callCerebras(string $prompt): ?string
    {
        return self::callOpenAICompatible(
            'https://api.cerebras.ai/v1/chat/completions',
            CEREBRAS_API_KEY,
            defined('CEREBRAS_MODEL') ? CEREBRAS_MODEL : 'llama-3.1-8b-instant',
            $prompt
        );
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 6: Cloudflare Workers AI — unique format
    // ════════════════════════════════════════════════════════

    private static function callCloudflare(string $prompt): ?string
    {
        $model  = defined('CLOUDFLARE_MODEL') ? CLOUDFLARE_MODEL : '@cf/meta/llama-3.1-8b-instruct';
        $acctId = defined('CLOUDFLARE_ACCOUNT_ID') ? CLOUDFLARE_ACCOUNT_ID : '';
        $url    = "https://api.cloudflare.com/client/v4/accounts/{$acctId}/ai/run/{$model}";

        $payload = json_encode([
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n"
                          . "Authorization: Bearer " . CLOUDFLARE_API_TOKEN . "\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);

        if (isset($result['result']['response'])) {
            return $result['result']['response'];
        }
        if (isset($result['result']['response']['content'])) {
            return $result['result']['response']['content'];
        }
        return null;
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 7: OpenRouter — OpenAI-compatible
    // ════════════════════════════════════════════════════════

    private static function callOpenRouter(string $prompt): ?string
    {
        return self::callOpenAICompatible(
            'https://openrouter.ai/api/v1/chat/completions',
            OPENROUTER_API_KEY,
            defined('OPENROUTER_MODEL') ? OPENROUTER_MODEL : 'meta-llama/llama-3.1-8b-instruct',
            $prompt
        );
    }

    // ════════════════════════════════════════════════════════
    //  PROVIDER 8: Hugging Face — OpenAI-compatible  ★ NEW
    // ════════════════════════════════════════════════════════

    private static function callHuggingFace(string $prompt): ?string
    {
        return self::callOpenAICompatible(
            'https://router.huggingface.co/v1/chat/completions',
            HF_API_TOKEN,
            defined('HF_MODEL') ? HF_MODEL : 'meta-llama/Llama-3.1-8B-Instruct',
            $prompt
        );
    }

    // ════════════════════════════════════════════════════════
    //  DEFAULT ENGINE (always works — no API needed)
    // ════════════════════════════════════════════════════════

    private static function defaultAnalysis(array $d): string
    {
        $rainfall = (float) ($d['rainfall_mm'] ?? 0);
        $humidity = (float) ($d['humidity'] ?? 0);
        $pressure = (float) ($d['pressure'] ?? 1013);
        $wind     = (float) ($d['wind_speed'] ?? 0);
        $soil     = $d['soil_moisture'] ?? null;
        $score    = (int) ($d['risk_score'] ?? 0);
        $level    = $d['risk_level'] ?? 'Low';
        $location = $d['location_name'] ?? 'this location';

        $factors = [];

        if ($rainfall >= 100) {
            $factors[] = "very heavy rainfall of {$rainfall} mm (contributing 40 points)";
        } elseif ($rainfall >= 50) {
            $factors[] = "significant rainfall of {$rainfall} mm (contributing up to 40 points)";
        } elseif ($rainfall >= 25) {
            $factors[] = "moderate rainfall of {$rainfall} mm (contributing up to 30 points)";
        } elseif ($rainfall >= 10) {
            $factors[] = "light rainfall of {$rainfall} mm (contributing up to 20 points)";
        }

        if ($humidity > 90) {
            $factors[] = "high humidity at {$humidity}% (contributing 20 points)";
        } elseif ($humidity > 80) {
            $factors[] = "elevated humidity at {$humidity}% (contributing 15 points)";
        } elseif ($humidity > 70) {
            $factors[] = "moderate humidity at {$humidity}% (contributing 10 points)";
        }

        if ($soil !== null && $soil !== '') {
            $soilF = (float) $soil;
            if ($soilF > 85) {
                $factors[] = "saturated soil moisture at {$soilF}% (contributing 20 points)";
            } elseif ($soilF > 70) {
                $factors[] = "high soil moisture at {$soilF}% (contributing 15 points)";
            } elseif ($soilF > 50) {
                $factors[] = "moderate soil moisture at {$soilF}% (contributing 10 points)";
            }
        }

        if ($pressure < 1000) {
            $factors[] = "low atmospheric pressure at {$pressure} hPa indicating storm conditions (contributing 10 points)";
        } elseif ($pressure < 1010) {
            $factors[] = "below-normal pressure at {$pressure} hPa (contributing up to 8 points)";
        }

        if ($wind >= 10) {
            $factors[] = "strong winds at {$wind} km/h (contributing up to 10 points)";
        } elseif ($wind >= 5) {
            $factors[] = "moderate winds at {$wind} km/h (contributing up to 10 points)";
        }

        $recommendation = match ($level) {
            'Low'       => "Conditions are stable. Continue routine monitoring of weather updates.",
            'Moderate'  => "Stay alert. Monitor rainfall trends and prepare for possible escalation.",
            'High'      => "Review local emergency procedures. Prepare to evacuate if conditions worsen. Coordinate with barangay officials.",
            'Critical'  => "Immediate action recommended. Follow CDRRMO evacuation protocols and alert residents in vulnerable areas.",
            default     => "Continue monitoring conditions.",
        };

        $factorText = count($factors) > 0
            ? "Key contributing factors: " . implode(", ", $factors) . "."
            : "No significant risk factors detected in the current readings.";

        return "AI Analysis — {$location}\n\n"
             . "The system classified the current condition as {$level} risk "
             . "with a score of {$score}/100. {$factorText}\n\n"
             . "Recommendation: {$recommendation}\n\n"
             . "Note: This analysis is generated by the Smart Slope V2 default engine"
             . " and is not an official landslide warning.";
    }

    // ════════════════════════════════════════════════════════
    //  PROMPT BUILDER (shared by all providers)
    // ════════════════════════════════════════════════════════

    private static function buildPrompt(array $d): string
    {
        $soilMoisture = $d['soil_moisture'] ?? null;
        $sm = ($soilMoisture !== null && $soilMoisture !== '')
            ? $soilMoisture . '%'
            : 'N/A (no soil sensor — API data)';

        return "You are assisting a prototype landslide monitoring system in Baguio City, Philippines.\n\nAnalyze the environmental readings below and explain why the system classified the current condition as the given risk level. Do NOT change the risk score or risk level. Do NOT invent measurements that are not listed. Provide practical monitoring recommendations.\n\nImportant: This is an educational prototype and NOT an official emergency warning system.\n\nLocation: {$d['location_name']}\nRainfall: {$d['rainfall_mm']} mm\nHumidity: {$d['humidity']}%\nTemperature: {$d['temperature']}C\nPressure: {$d['pressure']} hPa\nWind Speed: {$d['wind_speed']} km/h\nSoil Moisture: {$sm}\nRisk Score: {$d['risk_score']}/100\nRisk Level: {$d['risk_level']}\n\nRespond in 3-4 sentences. Explain which factors contributed to the risk score, then give one practical recommendation. End with a note that this is a prototype, not an official warning.";
    }
}