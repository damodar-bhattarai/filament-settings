<?php

namespace DamodarBhattarai\FilamentSettings;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentSettingsServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-settings';

    public function configurePackage(Package $package): void
    {
        $package
            ->name(static::$name)
            ->hasConfigFile()
            ->hasMigration('add_filament_columns_to_settings_table')
            ->hasViews();
    }
}
