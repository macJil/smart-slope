<?php
// classes/AIAnalyzer.php - Multi-provider AI with automatic fallback
// All 8 providers with proper error handling and updated models

class AIAnalyzer
{
    public static function analyze(array $data): array
    {
        $prompt = self::buildPrompt($data);
        
        // Try providers in order of preference
        $providers = [
            // 1. Grok (xAI) - Currently no free tier, but included for completeness
            ['name' => 'Grok (xAI)', 'key' => 'GROK_API_KEY', 'model' => 'GROK_MODEL', 
             'url' => 'https://api.x.ai/v1/chat/completions', 'method' => 'callOpenAI'],
            
            // 2. Google Gemini - Free tier available
            ['name' => 'Gemini (Google)', 'key' => 'GEMINI_API_KEY', 'model' => 'GEMINI_MODEL',
             'url' => null, 'method' => 'callGemini'],
            
            // 3. Groq - Free tier, very fast
            ['name' => 'Groq', 'key' => 'GROQ_API_KEY', 'model' => 'GROQ_MODEL',
             'url' => 'https://api.groq.com/openai/v1/chat/completions', 'method' => 'callOpenAI'],
            
            // 4. Mistral AI - Free tier available
            ['name' => 'Mistral AI', 'key' => 'MISTRAL_API_KEY', 'model' => 'MISTRAL_MODEL',
             'url' => 'https://api.mistral.ai/v1/chat/completions', 'method' => 'callOpenAI'],
            
            // 5. Cerebras - Free tier available
            ['name' => 'Cerebras', 'key' => 'CEREBRAS_API_KEY', 'model' => 'CEREBRAS_MODEL',
             'url' => 'https://api.cerebras.ai/v1/chat/completions', 'method' => 'callOpenAI'],
            
            // 6. Cloudflare AI - Free tier available
            ['name' => 'Cloudflare AI', 'key' => 'CLOUDFLARE_API_TOKEN', 'model' => 'CLOUDFLARE_MODEL',
             'url' => null, 'method' => 'callCloudflare'],
            
            // 7. OpenRouter - Free tier available
            ['name' => 'OpenRouter', 'key' => 'OPENROUTER_API_KEY', 'model' => 'OPENROUTER_MODEL',
             'url' => 'https://openrouter.ai/api/v1/chat/completions', 'method' => 'callOpenAI'],
            
            // 8. Hugging Face - Free tier available
            ['name' => 'Hugging Face', 'key' => 'HF_API_TOKEN', 'model' => 'HF_MODEL',
             'url' => 'https://router.huggingface.co/v1/chat/completions', 'method' => 'callOpenAI'],
        ];
        
        foreach ($providers as $p) {
            $result = self::callProvider($p, $prompt);
            if ($result !== null) {
                return ['success' => true, 'analysis' => $result, 'provider' => $p['name']];
            }
        }
        
        // Fallback to Default Engine
        return [
            'success' => true,
            'analysis' => self::defaultAnalysis($data),
            'provider' => 'Default Engine',
        ];
    }
    
    private static function callProvider(array $provider, string $prompt): ?string
    {
        $key = $provider['key'] ?? null;
        $model = defined($provider['model']) ? constant($provider['model']) : null;
        
        // Skip if no key defined or empty
        if ($key === null || !defined($key) || constant($key) === '') {
            return null;
        }
        
        try {
            return self::{$provider['method']}($provider['url'], constant($key), $model, $prompt);
        } catch (Exception $e) {
            return null;
        }
    }

    // OpenAI-compatible providers (Grok, Groq, Mistral, Cerebras, OpenRouter)
    private static function callOpenAI(?string $url, ?string $apiKey, ?string $model, string $prompt): ?string
    {
        if (!$url || !$apiKey || !$model) return null;
        
        $payload = json_encode([
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 120,
            'temperature' => 0.7,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        
        // Handle errors
        if (isset($result['error'])) return null;
        
        $content = $result['choices'][0]['message']['content'] ?? null;
        return is_string($content) && trim($content) !== '' ? trim($content) : null;
    }

    // Special method for Gemini
    private static function callGemini(?string $url, ?string $apiKey, ?string $model, string $prompt): ?string
    {
        if (!$apiKey) return null;
        
        $model = $model ?? 'gemini-2.0-flash';
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=$apiKey";

        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 120,
            ],
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        
        // Handle errors
        if (isset($result['error'])) return null;
        
        return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

    // Special method for Cloudflare
    private static function callCloudflare(?string $url, ?string $apiKey, ?string $model, string $prompt): ?string
    {
        if (!$apiKey || !defined('CLOUDFLARE_ACCOUNT_ID') || CLOUDFLARE_ACCOUNT_ID === '') return null;
        
        $model = $model ?? '@cf/meta/llama-3.1-8b-instruct';
        $accountId = CLOUDFLARE_ACCOUNT_ID;
        $url = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$model}";

        $payload = json_encode([
            'messages' => [['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 120,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        
        // Handle errors
        if (isset($result['errors'])) return null;
        
        return $result['result']['response'] ?? null;
    }

    // Special method for Hugging Face
    private static function callHuggingFace(?string $url, ?string $apiKey, ?string $model, string $prompt): ?string
    {
        if (!$url || !$apiKey) return null;
        
        $payload = json_encode([
            'inputs' => [
                'role' => 'user',
                'content' => $prompt
            ],
            'parameters' => [
                'max_tokens' => 120,
                'temperature' => 0.7,
            ],
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer $apiKey\r\n",
                'content' => $payload,
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) return null;

        $result = json_decode($response, true);
        
        // Handle errors
        if (isset($result['error'])) return null;
        
        // Hugging Face returns different format
        return $result[0]['generated_text'] ?? null;
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
        if ($rainfall >= 25 && $rainfall < 50) $factors[] = "moderate rainfall of {$rainfall}mm";
        if ($humidity > 80) $factors[] = "high humidity at {$humidity}%";
        if ($soil && $soil > 70) $factors[] = "saturated soil at {$soil}%";
        if ($pressure < 1000) $factors[] = "low atmospheric pressure at {$pressure}hPa";
        if ($wind >= 10) $factors[] = "strong winds at {$wind}km/h";

        $factorText = $factors 
            ? "Key contributing factors: " . implode(", ", $factors) . ". " 
            : "No significant risk factors detected. ";

        $recommendation = match ($level) {
            'Low' => "Conditions are stable. Continue normal monitoring.",
            'Moderate' => "Stay alert. Monitor rainfall trends closely.",
            'High' => "Elevated risk detected. Review emergency procedures and prepare to act if conditions worsen.",
            'Critical' => "Critical risk detected. Immediate action recommended. Follow local CDRRMO evacuation protocols.",
            default => "Continue monitoring conditions.",
        };

        return "{$level} risk ({$score}/100) at {$location}. {$factorText}"
            . "Recommendation: {$recommendation}";
    }

    private static function buildPrompt(array $d): string
    {
        $soilMoisture = $d['soil_moisture'] ?? null;
        $sm = ($soilMoisture !== null && $soilMoisture !== '')
            ? $soilMoisture . '%' : 'N/A';

        return "You are assisting a landslide monitoring system in Baguio City, Philippines.\n\n"
            . "Analyze these readings and explain in simple terms why the risk is classified as {$d['risk_level']}.\n\n"
            . "Do NOT change the risk score or level. Provide practical, actionable recommendations.\n\n"
            . "Location: {$d['location_name']}\n"
            . "Coordinates: (" . ($d['latitude'] ?? 'N/A') . ", " . ($d['longitude'] ?? 'N/A') . ")\n"
            . "Rainfall: {$d['rainfall_mm']}mm, Humidity: {$d['humidity']}%, "
            . "Soil Moisture: {$sm}, Pressure: {$d['pressure']}hPa, Wind: {$d['wind_speed']}km/h\n"
            . "Risk Score: {$d['risk_score']}/100, Risk Level: {$d['risk_level']}\n\n"
            . "Respond in no more than 2 short sentences: state the main risk factor and one practical recommendation. Do not add headings or extra notes.";
    }
}