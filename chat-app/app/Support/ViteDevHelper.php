<?php

declare(strict_types=1);

namespace App\Support;

class ViteDevHelper
{
    /**
     * Resolve Vite dev server URL if it is actively running and debug mode is enabled.
     * Probes IPv4 (127.0.0.1) first to avoid Windows IPv6 resolution latency.
     */
    public static function getDevServerUrl(): ?string
    {
        if (! (bool) config('app.debug', false)) {
            return null;
        }

        $configured = config('chatify.frontend.dev_server', env('CHATIFY_DEV_SERVER', 'auto'));

        if ($configured === false || $configured === 'false' || $configured === 'off' || $configured === '0') {
            return null;
        }

        $host = '127.0.0.1';
        $port = 5173;

        // Check if public/hot exists
        $hotFile = public_path('hot');
        if (file_exists($hotFile)) {
            $hotContent = trim((string) @file_get_contents($hotFile));
            if ($hotContent !== '') {
                if (preg_match('#^https?://([^:/]+)(?::(\d+))?#', $hotContent, $matches)) {
                    $host = ($matches[1] === 'localhost') ? '127.0.0.1' : $matches[1];
                    $port = ! empty($matches[2]) ? (int) $matches[2] : 5173;
                }
            }
        } elseif (is_string($configured) && $configured !== '' && $configured !== 'auto' && $configured !== 'true' && $configured !== '1') {
            if (preg_match('#^https?://([^:/]+)(?::(\d+))?#', $configured, $matches)) {
                $host = ($matches[1] === 'localhost') ? '127.0.0.1' : $matches[1];
                $port = ! empty($matches[2]) ? (int) $matches[2] : 5173;
            }
        }

        // Fast probe: 127.0.0.1 first (avoids Windows IPv6 DNS stall), then localhost
        $socket = @fsockopen($host, $port, $errno, $errstr, 0.4);
        if (! $socket && $host === '127.0.0.1') {
            $socket = @fsockopen('localhost', $port, $errno, $errstr, 0.4);
        }

        if ($socket) {
            fclose($socket);
            return "http://{$host}:{$port}";
        }

        return null;
    }
}

