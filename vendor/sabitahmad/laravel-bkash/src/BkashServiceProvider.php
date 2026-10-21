<?php

namespace SabitAhmad\Bkash;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SabitAhmad\Bkash\Commands\BkashCommand;
use SabitAhmad\Bkash\Contracts\BkashInterface;
use SabitAhmad\Bkash\Helpers\BkashHelper;

class BkashServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-bkash')
            ->hasConfigFile()
            ->hasCommand(BkashCommand::class)
            ->hasMigration('create_bkash_payments');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(BkashInterface::class, function () {
            return new Bkash;
        });

        $this->app->alias(BkashInterface::class, Bkash::class);

        $this->app->singleton(BkashHelper::class, function ($app) {
            return new BkashHelper($app->make(BkashInterface::class));
        });
    }
}
