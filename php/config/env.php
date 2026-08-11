<?php
// php/config/env.php

if (!function_exists('getEnvVar')) {
    function getEnvVar($key, $default = null) {
        static $env = null;
        if ($env === null) {
            $env = [];
            $envPath = __DIR__ . '/../../.env';
            if (file_exists($envPath)) {
                $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line) || strpos($line, '#') === 0) {
                        continue;
                    }
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $envKey = trim($parts[0]);
                        $envVal = trim($parts[1]);
                        // Strip enclosing quotes if present
                        if (preg_match('/^"?(.*?)"?$/', $envVal, $matches)) {
                            $envVal = $matches[1];
                        }
                        $env[$envKey] = $envVal;
                    }
                }
            }
        }
        return isset($env[$key]) ? $env[$key] : $default;
    }
}
