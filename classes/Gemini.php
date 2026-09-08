<?php
// classes/Gemini.php
// Google Gemini API integration with a built-in default fallback.
//
// If GEMINI_API_KEY is configured → calls the real Gemini API.
// If no key → generates a default analysis from a template script
//   so the dashboard ALWAYS shows an AI analysis section.
//
// Gemini does NOT determine the risk — it only explains what
// RiskEngine already calculated.

class Gemini
{
    /**
     * Analyze weather + risk data.
     *
     * If API key is set → calls Gemini for a dynamic response.
     * If no key → returns a default scripted analysis based on the data.
     * If API call fails → falls back to the default analysis.
     *
     * @param array $d  Keys: location_name, rainfall_mm, humidity,
     *                  temperature, pressure, wind_speed, soil_moisture,
     *                  risk_score, risk_level
     * @return array  ['success' => bool, 'analysis' => string, 'source' => 'gemini'|'default']
     */
    public static function analyze(array $d): array
    {
        // ── No API key → use the default scripted analysis ──
        if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '') {
            return [
                'success'  => true,
                'analysis' => self::defaultAnalysis($d),
                'source'   => 'default',
            ];
        }

        // ── API key is set → call the real Gemini API ────────
        $model  = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.0-flash';
        $prompt = self::buildPrompt($d);

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
             . $model . ':generateContent?key=' . GEMINI_API_KEY;

        $payload = json_encode([
            'contents' => [
                ['parts' => [['text' => $prompt]]],
            ],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens'  => 400,
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

        $response = @file_get_contents($url, false, $context);

        // ── API call failed → fall back to default analysis ─
        if ($response === false) {
            return [
                'success'  => true,
                'analysis' => self::defaultAnalysis($d),
                'source'   => 'default',
            ];
        }

        $result = json_decode($response, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if (!$text) {
            // API returned an error → fall back to default
            return [
                'success'  => true,
                'analysis' => self::defaultAnalysis($d),
                'source'   => 'default',
            ];
        }

        return [
            'success'  => true,
            'analysis' => trim($text),
            'source'   => 'gemini',
        ];
    }

    /**
     * Generate a default analysis from a built-in template.
     * This uses the actual weather data and risk level to produce
     * a meaningful, scripted explanation — no API call needed.
     *
     * The template explains which factors contributed to the score
     * and gives a practical recommendation, just like Gemini would.
     */
    public static function defaultAnalysis(array $d): string
    {
        $rainfall   = (float) ($d['rainfall_mm'] ?? 0);
        $humidity   = (float) ($d['humidity'] ?? 0);
        $pressure   = (float) ($d['pressure'] ?? 1013);
        $wind       = (float) ($d['wind_speed'] ?? 0);
        $soil       = $d['soil_moisture'] ?? null;
        $score      = (int) ($d['risk_score'] ?? 0);
        $level      = $d['risk_level'] ?? 'Low';
        $location   = $d['location_name'] ?? 'this location';

        // ── Build the factor explanation ──────────────────
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

        // ── Build the recommendation ──────────────────────
        $recommendation = match ($level) {
            'Low'       => "Conditions are stable. Continue routine monitoring of weather updates.",
            'Moderate'  => "Stay alert. Monitor rainfall trends and prepare for possible escalation.",
            'High'      => "Review local emergency procedures. Prepare to evacuate if conditions worsen. Coordinate with barangay officials.",
            'Critical'  => "Immediate action recommended. Follow CDRRMO evacuation protocols and alert residents in vulnerable areas.",
            default     => "Continue monitoring conditions.",
        };

        // ── Assemble the analysis ─────────────────────────
        $factorText = count($factors) > 0
            ? "Key contributing factors: " . implode(", ", $factors) . "."
            : "No significant risk factors detected in the current readings.";

        return "AI Analysis — {$location}\n\n"
             . "The system classified the current condition as {$level} risk "
             . "with a score of {$score}/100. {$factorText}\n\n"
             . "Recommendation: {$recommendation}\n\n"
             . "Note: This analysis is generated by the Smart Slope V2 default engine"
             . " and is not an official landslide warning. "
             . "Configure a Gemini API key in config.php for dynamic AI-generated analysis.";
    }

    /** Build the prompt sent to the real Gemini API. */
    private static function buildPrompt(array $d): string
    {
        $sm = ($d['soil_moisture'] !== null && $d['soil_moisture'] !== '')
            ? $d['soil_moisture'] . '%'
            : 'N/A (no soil sensor — API data)';

        return "You are assisting a prototype landslide monitoring system in Baguio City, Philippines.

Analyze the environmental readings below and explain why the system classified the current condition as the given risk level. Do NOT change the risk score or risk level. Do NOT invent measurements that are not listed. Provide practical monitoring recommendations.

Important: This is an educational prototype and NOT an official emergency warning system.

Location: {$d['location_name']}
Rainfall: {$d['rainfall_mm']} mm
Humidity: {$d['humidity']}%
Temperature: {$d['temperature']}C
Pressure: {$d['pressure']} hPa
Wind Speed: {$d['wind_speed']} km/h
Soil Moisture: {$sm}
Risk Score: {$d['risk_score']}/100
Risk Level: {$d['risk_level']}

Respond in 3-4 sentences. Explain which factors contributed to the risk score, then give one practical recommendation. End with a note that this is a prototype, not an official warning.";
    }
}