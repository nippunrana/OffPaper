<?php
// Hand-rolled .env reader. No parse_ini_file() — it mangles '#', '$', and bare yes/no/null.

function env(string $key, ?string $default = null): ?string
{
    static $values = null;

    if ($values === null) {
        $path = APP_ROOT . '/.env';
        if (!is_file($path)) {
            die('Missing .env file. Copy .env.example to .env and fill in your values.');
        }

        $values = [];
        foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
            $k = trim($k);
            $v = trim($v);

            if (strlen($v) >= 2 && $v[0] === $v[-1] && ($v[0] === '"' || $v[0] === "'")) {
                $v = substr($v, 1, -1);
            }

            $values[$k] = $v;
        }
    }

    return $values[$key] ?? $default;
}
