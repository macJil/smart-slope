<?php
require_once __DIR__ . '/../config.php';

/**
 * Runs the trained Random Forest landslide model for one weather reading.
 *
 * Used by both predict.php (manual endpoint) and fetch_weather.php (ingest).
 * Falls back to the documented rainfall threshold if Python/scikit-learn
 * cannot run (e.g. architecture mismatch), keeping the prototype usable.
 */
class Prediction
{
    /**
     * @return array{prediction:int, fallback:bool, message:string}
     */
    public static function run(
        float $rainfall_mm,
        float $humidity,
        float $pressure,
        float $temperature,
        float $wind_speed
    ): array {
        $python = self::resolvePython();
        $script = __DIR__ . '/../predict.py';
        $model  = __DIR__ . '/../landslide_model.pkl';

        // Skip the process entirely if the model file is missing.
        if (!file_exists($model)) {
            return self::fallback($rainfall_mm);
        }

        $command = escapeshellcmd($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($model);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptors, $pipes, __DIR__ . '/..');

        if (!is_resource($process)) {
            return self::fallback($rainfall_mm);
        }

        fwrite($pipes[0], json_encode([
            'rainfall_mm' => $rainfall_mm,
            'humidity'    => $humidity,
            'pressure'    => $pressure,
            'temperature' => $temperature,
            'wind_speed'  => $wind_speed,
        ]));
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        $error  = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit_code = proc_close($process);

        // Log stderr so failures are diagnosable instead of silently swallowed.
        if (trim($error) !== '') {
            error_log('[smart-slope] predict.py stderr: ' . trim($error));
        }

        if ($exit_code !== 0 || trim($output) === '') {
            return self::fallback($rainfall_mm);
        }

        $result = json_decode($output, true);
        if (!is_array($result) || !array_key_exists('prediction', $result)) {
            return self::fallback($rainfall_mm);
        }

        return [
            'prediction' => (int) $result['prediction'],
            'fallback'   => false,
            'message'    => 'Assessment returned by the trained Random Forest model.',
        ];
    }

    /**
     * Cross-platform Python executable resolver.
     * 1. Preferred absolute Unix path (Linux/macOS).
     * 2. On Windows rely on PATH lookup ("python").
     * 3. Other Unix: bare "python3".
     */
    private static function resolvePython(): string
    {
        if (file_exists('/usr/local/bin/python3')) {
            return '/usr/local/bin/python3';
        }
        return PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3';
    }

    private static function fallback(float $rainfall_mm): array
    {
        return [
            'prediction' => $rainfall_mm > 100 ? 1 : 0,
            'fallback'   => true,
            'message'    => 'Prototype mode: rainfall threshold assessment used.',
        ];
    }
}