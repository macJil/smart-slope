<?php
// classes/AIAnalyzer.php - Multi-provider AI with automatic fallback
// Replaces: classes/Gemini.php

class AIAnalyzer
{
    public static function analyze(array $data): array
    {
        $prompt = self::buildPrompt($data);
        
        // 1. Try Grok (xAI) first
        if (defined('GROK_API_KEY') && GROK_API_KEY !== '') {
            $result = self::callOpenAI(
                'https://api.x.ai/v1/chat/completions',
                GROK_API_KEY,
                defined('GROK_MODEL') ? GROK_MODEL : 'grok-2',
                $prompt
            );
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Grok (xAI)'];
            }
        }
        
        // 2. Try Gemini (unique format)
        if (defined('GEMINI_API_KEY') && GEMINI_API_KEY !== '') {
            $result = self::callGemini($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Gemini (Google)'];
            }
        }
        
        // 3. Loop through OpenAI-compatible providers
        $openaiProviders = [
            ['key' => 'GROQ_API_KEY', 'model' => 'GROQ_MODEL', 'name' => 'Groq', 
             'url' => 'https://api.groq.com/openai/v1/chat/completions'],
            ['key' => 'MISTRAL_API_KEY', 'model' => 'MISTRAL_MODEL', 'name' => 'Mistral AI', 
             'url' => 'https://api.mistral.ai/v1/chat/completions'],
            ['key' => 'CEREBRAS_API_KEY', 'model' => 'CEREBRAS_MODEL', 'name' => 'Cerebras', 
             'url' => 'https://api.cerebras.ai/v1/chat/completions'],
            ['key' => 'OPENROUTER_API_KEY', 'model' => 'OPENROUTER_MODEL', 'name' => 'OpenRouter', 
             'url' => 'https://openrouter.ai/api/v1/chat/completions'],
            ['key' => 'HF_API_TOKEN', 'model' => 'HF_MODEL', 'name' => 'Hugging Face', 
             'url' => 'https://router.huggingface.co/v1/chat/completions'],
        ];
        
        foreach ($openaiProviders as $p) {
            $key = constant($p['key']);
            $model = constant($p['model']);
            if (!empty($key)) {
                $result = self::callOpenAI($p['url'], $key, $model, $prompt);
                if ($result !== null) {
                    return ['success' => true, 'analysis' => $result, 'provider' => $p['name']];
                }
            }
        }
        
        // 4. Try Cloudflare (unique format)
        if (defined('CLOUDFLARE_API_TOKEN') && CLOUDFLARE_API_TOKEN !== ''
            && defined('CLOUDFLARE_ACCOUNT_ID') && CLOUDFLARE_ACCOUNT_ID !== '') {
            $result = self::callCloudflare($prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => 'Cloudflare AI'];
            }
        }
        
        // 5. Default Engine
        return [
            'success' => true,
            'analysis' => self::defaultAnalysis($data),
            'provider' => 'Default Engine',
        ];
    }

    // Shared method for OpenAI-compatible providers (6 providers use this)
    private static function callOpenAI(string $url, string $apiKey, string $model, string $prompt): ?string
    {
        $payload = json_encode([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 400,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        return $result['choices'][0]['message']['content'] ?? null;
    }

    // Special method for Gemini
    private static function callGemini(string $prompt): ?string
    {
        $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-3.6-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . GEMINI_API_KEY;

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 400],
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    // Special method for Cloudflare
    private static function callCloudflare(string $prompt): ?string
    {
        $model = defined('CLOUDFLARE_MODEL') ? CLOUDFLARE_MODEL : '@cf/meta/llama-3.1-8b-instruct';
        $accountId = defined('CLOUDFLARE_ACCOUNT_ID') ? CLOUDFLARE_ACCOUNT_ID : '';
        $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$model}";

        $payload = json_encode(['messages' => [['role' => 'user', 'content' => $prompt]]]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer " . CLOUDFLARE_API_TOKEN . "\r\n",
                'content' => $payload,
                'timeout' => 15,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        return $result['result']['response'] ?? null;
    }

    // Default Engine (always works)
    private static function defaultAnalysis(array $d): string
    {
        $rainfall = (float) ($d['rainfall_mm'] ?? 0);
        $humidity = (float) ($d['humidity'] ?? 0);
        $pressure = (float) ($d['pressure'] ?? 1013);
        $wind = (float) ($d['wind_speed'] ?? 0);
        $soil = $d['soil_moisture'] ?? null;
        $score = (int) ($d['risk_score'] ?? 0);
        $level = $d['risk_level'] ?? 'Low';
        $location = $d['location_name'] ?? 'this location';

        $factors = [];
        if ($rainfall >= 50) $factors[] = "heavy rainfall of {$rainfall}mm";
        if ($humidity > 80) $factors[] = "high humidity at {$humidity}%";
        if ($soil && $soil > 70) $factors[] = "saturated soil at {$soil}%";
        if ($pressure < 1000) $factors[] = "low pressure at {$pressure}hPa";
        if ($wind >= 10) $factors[] = "strong winds at {$wind}km/h";

        $factorText = $factors 
            ? "Key factors: " . implode(", ", $factors) . ". " 
            : "No significant risk factors detected. ";

        $recommendation = match ($level) {
            'Low' => "Conditions are stable. Continue monitoring.",
            'Moderate' => "Stay alert. Monitor rainfall trends.",
            'High' => "Review emergency procedures. Prepare to evacuate if conditions worsen.",
            'Critical' => "Immediate action recommended. Follow CDRRMO evacuation protocols.",
            default => "Continue monitoring conditions.",
        };

        return "AI Analysis — {$location}\n\n"
            . "Risk level: {$level} (score: {$score}/100). {$factorText}\n\n"
            . "Recommendation: {$recommendation}\n\n"
            . "Note: This is a prototype analysis, not an official landslide warning.";
    }

    private static function buildPrompt(array $d): string
    {
        $sm = ($d['soil_moisture'] !== null && $d['soil_moisture'] !== '')
            ? $d['soil_moisture'] . '%' : 'N/A';

        return "You are assisting a landslide monitoring system in Baguio City, Philippines.\n\n"
            . "Analyze these readings and explain why the risk is classified as {$d['risk_level']}.\n\n"
            . "Do NOT change the risk score or level. Provide practical recommendations.\n\n"
            . "Location: {$d['location_name']}\n"
            . "Rainfall: {$d['rainfall_mm']}mm, Humidity: {$d['humidity']}%, "
            . "Soil Moisture: {$sm}, Pressure: {$d['pressure']}hPa, Wind: {$d['wind_speed']}km/h\n"
            . "Risk Score: {$d['risk_score']}/100, Risk Level: {$d['risk_level']}\n\n"
            . "Respond in 3-4 sentences. End with: 'Note: This is a prototype, not an official warning.'";
    }
}