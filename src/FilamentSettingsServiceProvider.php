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

    public function packageRegistered(): void
    {
        parent::packageRegistered();
    }

    public function packageBooted(): void
    {
        parent::packageBooted();

        if ($this->app->runningInConsole()) {
            // Auto-publish base package migration if it doesn't exist in the project
            $baseMigrationStub = base_path('vendor/damodar-bhattarai/settings/database/migrations/create_settings_table.php.stub');
            if (file_exists($baseMigrationStub)) {
                $migrationExists = collect(glob(database_path('migrations/*_create_settings_table.php')))->isNotEmpty();
                if (! $migrationExists) {
                    $timestamp = date('Y_m_d_His');
                    $targetPath = database_path("migrations/{$timestamp}_create_settings_table.php");
                    @copy($baseMigrationStub, $targetPath);
                }
            }

            // Auto-publish base package seeder if it doesn't exist
            $baseSeederStub = base_path('vendor/damodar-bhattarai/settings/database/seeders/SettingsSeeder.php.stub');
            if (file_exists($baseSeederStub)) {
                $targetSeeder = database_path('seeders/SettingsSeeder.php');
                if (! file_exists($targetSeeder)) {
                    @copy($baseSeederStub, $targetSeeder);
                }
            }
        }
    }
}
