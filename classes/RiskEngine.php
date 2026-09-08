<?php
// classes/RiskEngine.php
// Transparent weighted-rule landslide risk calculator.

class RiskEngine
{
    /**
     * Calculate a 0-100 risk score from weather readings.
     *
     *   Rainfall       max 40 points  (heaviest factor)
     *   Humidity       max 20 points
     *   Soil Moisture  max 20 points  (0 if unavailable)
     *   Pressure       max 10 points  (low pressure = storm risk)
     *   Wind Speed     max 10 points
     *   Total          max 100
     */
    public static function calculate(
        float $rainfall,
        float $humidity,
        ?float $soil_moisture,
        float $pressure,
        float $temperature,
        float $wind_speed
    ): array {
        $score = 0;

        // Rainfall: 0-40 points
        if ($rainfall >= 100) {
            $score += 40;
        } elseif ($rainfall >= 50) {
            $score += 30 + ($rainfall - 50) / 50 * 10;   // 30-40
        } elseif ($rainfall >= 25) {
            $score += 20 + ($rainfall - 25) / 25 * 10;   // 20-30
        } elseif ($rainfall >= 10) {
            $score += 10 + ($rainfall - 10) / 15 * 10;  // 10-20
        } else {
            $score += $rainfall;                           // 0-10
        }

        // Humidity: 0-20 points
        if ($humidity > 90)      $score += 20;
        elseif ($humidity > 80)  $score += 15;
        elseif ($humidity > 70)  $score += 10;
        elseif ($humidity > 60)  $score += 5;

        // Soil Moisture: 0-20 points (skip if null)
        if ($soil_moisture !== null) {
            if ($soil_moisture > 85)      $score += 20;
            elseif ($soil_moisture > 70)  $score += 15;
            elseif ($soil_moisture > 50)  $score += 10;
            elseif ($soil_moisture > 30)  $score += 5;
        }

        // Pressure: 0-10 points (lower = higher risk)
        if ($pressure < 1000)      $score += 10;
        elseif ($pressure < 1005)  $score += 8;
        elseif ($pressure < 1010)  $score += 5;
        elseif ($pressure < 1015)  $score += 2;

        // Wind Speed: 0-10 points
        $score += min($wind_speed / 2, 10);

        // Clamp to 0-100 and round
        $score = (int) round(min(100, max(0, $score)));

        return [
            'score' => $score,
            'level' => self::scoreToLevel($score),
        ];
    }

    /**
     * Calculate risk from an array of data (convenience method)
     */
    public static function calculateRisk(array $data): array
    {
        return self::calculate(
            (float)($data['rainfall_mm'] ?? 0),
            (float)($data['humidity'] ?? 0),
            isset($data['soil_moisture']) ? (float)($data['soil_moisture']) : null,
            (float)($data['pressure'] ?? 1013),
            (float)($data['temperature'] ?? 0),
            (float)($data['wind_speed'] ?? 0)
        );
    }

    /** Map a 0-100 score to a risk level label. */
    public static function scoreToLevel(int $score): string
    {
        if ($score <= 24)  return 'Low';
        if ($score <= 49)  return 'Moderate';
        if ($score <= 74)  return 'High';
        return 'Critical';
    }

    /** Return a Bootstrap color class for a risk level. */
    public static function levelColor(string $level): string
    {
        return match ($level) {
            'Low'       => '#198754', // green
            'Moderate'  => '#ffc107', // yellow
            'High'      => '#fd7e14', // orange
            'Critical'  => '#dc3545', // red
            default     => '#6c757d',
        };
    }

    /** Return a Bootstrap alert class for a risk level. */
    public static function levelAlertClass(string $level): string
    {
        return match ($level) {
            'Low'       => 'success',
            'Moderate'  => 'info',
            'High'      => 'warning',
            'Critical'  => 'danger',
            default     => 'secondary',
        };
    }

    /** Return guidance message for a risk level. */
    public static function levelGuidance(string $level): string
    {
        return match ($level) {
            'Low'       => 'No immediate landslide warning from the latest reading.',
            'Moderate'  => 'Conditions are worth monitoring. Stay alert for changes.',
            'High'      => 'Elevated risk detected. Review conditions and prepare to act.',
            'Critical'  => 'Critical risk detected. Follow local emergency procedures immediately.',
            default     => 'Waiting for the latest reading.',
        };
    }
}