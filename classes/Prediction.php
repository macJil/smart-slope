<?php
// Prediction.php — AI landslide risk assessment
// Calls the Python Random Forest model. Falls back to rainfall threshold
// if Python is not available (e.g. Windows without Python installed).

class Prediction
{
    /**
     * Run the AI model on weather data.
     * Returns: ['prediction' => 0 or 1, 'fallback' => true/false]
     *
     * @return array{prediction:int, fallback:bool}
     */
    public static function run($rainfall_mm, $humidity, $pressure, $temperature, $wind_speed)
    {
        $python = self::findPython();
        $script = __DIR__ . '/../predict.py';
        $model  = __DIR__ . '/../landslide_model.pkl';

        // If Python or model file is missing, use the fallback
        if ($python === null || !file_exists($model)) {
            return self::fallback($rainfall_mm);
        }

        // Build the command safely
        $command = escapeshellcmd($python) . ' ' . escapeshellarg($script) . ' ' . escapeshellarg($model);

        // Open the Python process and send data via stdin
        $descriptors = [
            0 => ['pipe', 'r'],  // stdin (we write to this)
            1 => ['pipe', 'w'],  // stdout (we read from this)
            2 => ['pipe', 'w'],   // stderr (we read errors from this)
        ];
        $process = proc_open($command, $descriptors, $pipes, __DIR__ . '/..');

        if (!is_resource($process)) {
            return self::fallback($rainfall_mm);
        }

        // Send weather data as JSON to Python
        fwrite($pipes[0], json_encode([
            'rainfall_mm' => (float) $rainfall_mm,
            'humidity'    => (float) $humidity,
            'pressure'    => (float) $pressure,
            'temperature' => (float) $temperature,
            'wind_speed'  => (float) $wind_speed,
        ]));
        fclose($pipes[0]);

        // Read the prediction result
        $output = stream_get_contents($pipes[1]);
        $error  = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        // Log errors for debugging (won't show to users)
        if (trim($error) !== '') {
            error_log('[Smart Slope] predict.py error: ' . $error);
        }

        // Parse the JSON response from Python
        $result = json_decode($output, true);
        if (!is_array($result) || !isset($result['prediction'])) {
            return self::fallback($rainfall_mm);
        }

        // Model worked — return the AI prediction
        return ['prediction' => (int) $result['prediction'], 'fallback' => false];
    }

    /**
     * Find the Python executable.
     * Works on Mac, Linux, and Windows.
     */
    private static function findPython()
    {
        // Mac/Linux: check the common path
        if (file_exists('/usr/local/bin/python3')) {
            return '/usr/local/bin/python3';
        }

        // Windows: use 'python' (relies on PATH)
        if (PHP_OS_FAMILY === 'Windows') {
            return 'python';
        }

        // Other Linux: try 'python3'
        return 'python3';
    }

    /**
     * Fallback: use the documented rainfall threshold.
     * Baguio City's critical rainfall threshold is 100mm.
     */
    private static function fallback($rainfall_mm)
    {
        return [
            'prediction' => $rainfall_mm > 100 ? 1 : 0,
            'fallback'   => true,
        ];
    }
}