<?php

namespace mjisoton\FastEnv;

/**
 * FastEnv - Ultra-Fast Compiled Environment Loader with Auto-Invalidation
 * Optimized to minimize upfront disk access and remove slow error-suppression operators.
 */
class FastEnv {

    /**
     * Bootstraps the application environment.
     * Tries to load a compiled PHP cache first, fallback/recompile if out of date.
     */
    public static function boot(string $envPath, string $cachePath): void {
        // 1. Try to load the cache file immediately.
        $envVariables = file_exists($cachePath) ? require $cachePath : false;

        // 2. Determine if we need to recompile.
        $needsRecompile = ($envVariables === false);

        if (!$needsRecompile && file_exists($envPath)) {
            $needsRecompile = (filemtime($envPath) > filemtime($cachePath));
        }

        if ($needsRecompile) {
            if (!file_exists($envPath)) {
                return; // No .env and no cache? Nothing to load.
            }

            $buffer = file_get_contents($envPath);
            $variables = [];

            if ($buffer !== false) {
                $line = strtok($buffer, "\r\n");
                while ($line !== false) {
                    $line = trim($line);
                    if ($line !== '' && isset($line[0]) && $line[0] !== '#') {
                        $pos = strpos($line, '=');
                        if ($pos !== false) {
                            $key = rtrim(substr($line, 0, $pos));
                            $value = ltrim(substr($line, $pos + 1));

                            if (($value[0] === '"' || $value[0] === "'") && $value[0] === $value[strlen($value) - 1]) {
                                $value = substr($value, 1, -1);
                            }
                            $variables[$key] = $value;
                        }
                    }
                    $line = strtok("\r\n");
                }
            }

            // Write the compiled variables as a native executable PHP array
            $export = var_export($variables, true);
            $code = "<?php\n// Generated automatically by FastEnv. Do not edit directly.\nreturn {$export};";

            file_put_contents($cachePath, $code, LOCK_EX);

            $envVariables = $variables;

            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($cachePath, true);
            }
        }

        // 3. Populate env environments directly out of the compiled array
        if (is_array($envVariables)) {
            foreach ($envVariables as $key => $value) {
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}
