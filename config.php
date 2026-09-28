<?php
/**
 * Veridon Technologies — Configuration & Environment Loader
 * Securely loads environment variables from .env file into getenv() and $_ENV.
 */

if (!function_exists('load_env')) {
    /**
     * Parse and load key-value pairs from an .env file
     *
     * @param string $filePath Absolute path to the .env file
     * @return bool True if loaded, false otherwise
     */
    function load_env($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return false;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Skip comments and empty lines
            if ($line === '' || substr($line, 0, 1) === '#') {
                continue;
            }

            // Split by the first '=' character
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);

                // Remove surrounding single or double quotes
                $len = strlen($val);
                if ($len >= 2) {
                    $first = substr($val, 0, 1);
                    $last  = substr($val, -1);
                    if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                        $val = substr($val, 1, -1);
                    }
                }

                // Populate into getenv, $_ENV, and $_SERVER if not already set
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$val}");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }

        return true;
    }
}

if (!function_exists('env')) {
    /**
     * Retrieve an environment variable with an optional fallback
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? ($_SERVER[$key] ?? $default);
        }
        return $val;
    }
}

// Automatically load .env if present in root
load_env(__DIR__ . '/.env');

if (!function_exists('get_mail_recipients')) {
    /**
     * Parse recipient list from environment into structured array
     * Format: "email1:Name1,email2:Name2" or "email1,email2"
     *
     * @return array
     */
    function get_mail_recipients() {
        $raw = env('MAIL_RECIPIENTS', '');
        $recipients = [];

        if (!empty($raw)) {
            $entries = explode(',', $raw);
            foreach ($entries as $entry) {
                $entry = trim($entry);
                if (empty($entry)) {
                    continue;
                }
                if (strpos($entry, ':') !== false) {
                    list($email, $name) = explode(':', $entry, 2);
                    $recipients[] = [
                        'email' => trim($email),
                        'name'  => trim($name)
                    ];
                } else {
                    $recipients[] = [
                        'email' => $entry,
                        'name'  => $entry
                    ];
                }
            }
        }

        // Safe fallback if not defined in .env
        if (empty($recipients)) {
            $recipients = [
                ['email' => 'info@veridontechnologies.com',        'name' => 'Veridon Info'],
                ['email' => 'ceo@veridontechnologies.com',         'name' => 'Veridon CEO'],
                ['email' => 'tuoyo.ikomi@veridontechnologies.com', 'name' => 'Tuoyo Ikomi'],
                ['email' => 'cto@veridontechnologies.com',         'name' => 'Veridon CTO'],
            ];
        }

        return $recipients;
    }
}
