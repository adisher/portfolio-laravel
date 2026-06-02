<?php

namespace App\Console\Commands;

use App\Services\AiContentService;
use Illuminate\Console\Command;

class DiagnoseEnv extends Command
{
    protected $signature = 'diagnose:env';
    protected $description = 'Diagnose environment variable loading';

    public function handle(): int
    {
        $this->info('=== Environment Diagnosis ===');
        $this->newLine();

        $this->info('1. Reading .env file directly:');
        $envContent = file_get_contents(base_path('.env'));
        preg_match('/ANTHROPIC_API_KEY=(.*)/', $envContent, $matches);
        $this->line('   From file: ' . ($matches[1] ?? 'NOT FOUND'));

        $this->newLine();
        $this->info('2. Laravel env() function:');
        $this->line('   ANTHROPIC_API_KEY: ' . (env('ANTHROPIC_API_KEY') ?: '(empty)'));
        $this->line('   AI_BUDGET_ALERT_EMAIL: ' . (env('AI_BUDGET_ALERT_EMAIL') ?: '(empty)'));
        $this->line('   MAIL_ADMIN_EMAIL: ' . (env('MAIL_ADMIN_EMAIL') ?: '(empty)'));

        $this->newLine();
        $this->info('3. Config values:');
        $this->line('   blog_automation.ai.api_key: ' . (config('blog_automation.ai.api_key') ?: '(empty)'));
        $this->line('   blog_automation.ai.enabled: ' . (config('blog_automation.ai.enabled') ? 'true' : 'false'));

        $this->newLine();
        $this->info('4. Superglobals:');
        $this->line('   $_SERVER[ANTHROPIC_API_KEY]: ' . ($_SERVER['ANTHROPIC_API_KEY'] ?? '(not set)'));
        $this->line('   $_ENV[ANTHROPIC_API_KEY]: ' . ($_ENV['ANTHROPIC_API_KEY'] ?? '(not set)'));
        $this->line('   getenv(): ' . (getenv('ANTHROPIC_API_KEY') ?: '(empty)'));

        $this->newLine();
        $this->info('5. AiContentService::isEnabled():');
        try {
            $service = app(AiContentService::class);
            $this->line('   isEnabled: ' . ($service->isEnabled() ? 'true' : 'false'));
        } catch (\Exception $e) {
            $this->error('   Error: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
