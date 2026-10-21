<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clear Laravel/software cache (config, view, route, application cache, compiled)
Artisan::command('cache:clear-software', function () {
    Artisan::call('config:clear');
    $this->info('Configuration cache cleared.');
    Artisan::call('view:clear');
    $this->info('View cache cleared.');
    Artisan::call('route:clear');
    $this->info('Route cache cleared.');
    Artisan::call('cache:clear');
    $this->info('Application cache cleared.');
    if (class_exists(\Illuminate\Foundation\Console\ClearCompiledCommand::class)) {
        Artisan::call('clear-compiled');
        $this->info('Compiled files cleared.');
    }
    $this->newLine();
    $this->info('Software cache cleared successfully.');
})->purpose('Clear all Laravel/software cache (config, view, route, app cache)');

// Bump PWA service worker cache version so browser clears old PWA cache on next load
Artisan::command('cache:clear-pwa', function () {
    $path = public_path('pwa/serviceworker.js');
    if (!is_file($path)) {
        $this->error('PWA service worker not found: ' . $path);
        return 1;
    }
    $content = file_get_contents($path);
    $pattern = "/const CACHE_NAME = 'pos-cache-v(\d+)';/";
    if (!preg_match($pattern, $content, $m)) {
        $this->error('Could not find CACHE_NAME in service worker.');
        return 1;
    }
    $oldVersion = (int) $m[1];
    $newVersion = $oldVersion + 1;
    $newContent = preg_replace($pattern, "const CACHE_NAME = 'pos-cache-v{$newVersion}';", $content);
    file_put_contents($path, $newContent, LOCK_EX);
    $this->info("PWA cache version bumped: v{$oldVersion} → v{$newVersion}");
    $this->info('Users will get fresh assets on next page load. Close all app tabs and reopen, or hard refresh (Ctrl+Shift+R).');
    return 0;
})->purpose('Bump PWA service worker cache version to force browser cache clear');

// Generate application key and write to .env
Artisan::command('app:generate-key', function () {
    Artisan::call('key:generate', ['--force' => true]);
    $this->info(Artisan::output());
    $this->info('Application key generated and written to .env');
})->purpose('Generate application key and write to .env file');
