<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate 
                           {--save : Save sitemap to file instead of just displaying}';

    protected $description = 'Generate XML sitemap for the portfolio website';

    public function handle(): int
    {
        $this->info('🚀 Generating sitemap...');

        $controller = new SitemapController();
        
        if ($this->option('save')) {
            $response = $controller->generate();
            $data = json_decode($response->getContent(), true);
            
            $this->info("✅ {$data['message']}");
            $this->line("📍 URL: {$data['url']}");
            $this->line("📊 Total URLs: {$data['count']}");
        } else {
            $sitemap = $controller->index();
            $this->line($sitemap->getContent());
        }

        return self::SUCCESS;
    }
}