<?php

namespace SabitAhmad\Bkash\Commands;

use Illuminate\Console\Command;
use SabitAhmad\Bkash\Facades\Bkash;

class BkashCommand extends Command
{
    public $signature = 'bkash:status';

    public $description = 'Check bKash API connection and display current configuration';

    public function handle(): int
    {
        $this->info('bKash Package Configuration:');

        $config = Bkash::getConfig();
        $isSandbox = Bkash::isSandbox();

        $this->table(
            ['Setting', 'Value'],
            [
                ['Environment', $isSandbox ? 'Sandbox' : 'Production'],
                ['Logging Enabled', Bkash::isLoggingEnabled() ? 'Yes' : 'No'],
                ['Timeout (seconds)', $config['timeout'] ?? 30],
                ['Retry Attempts', $config['retry_attempts'] ?? 3],
                ['Token Cache TTL', $config['token_cache_ttl'] ?? 3300],
                ['App Key', $this->maskSecret($config['credentials']['app_key'] ?? '')],
            ]
        );

        return self::SUCCESS;
    }

    protected function maskSecret(string $secret): string
    {
        $length = strlen($secret);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($secret, 0, 4) . str_repeat('*', $length - 8) . substr($secret, -4);
    }
}
