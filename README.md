# App Settings

[![Latest Version on Packagist](https://img.shields.io/packagist/v/damodar-bhattarai/filament-settings.svg?style=flat-square)](https://packagist.org/packages/damodar-bhattarai/filament-settings)
[![Total Downloads](https://img.shields.io/packagist/dt/damodar-bhattarai/filament-settings.svg?style=flat-square)](https://packagist.org/packages/damodar-bhattarai/filament-settings)
[![License](https://img.shields.io/packagist/l/damodar-bhattarai/filament-settings.svg?style=flat-square)](https://packagist.org/packages/damodar-bhattarai/filament-settings)

A powerful, tabbed settings management page for your Filament panel that lets you manage all your website settings — general info, social links, SEO, appearance, custom CSS/JS — from one beautiful interface.

![App Settings Screenshot](https://raw.githubusercontent.com/damodar-bhattarai/filament-settings/main/art/screenshot.jpg)

## Features

- **📑 Tabbed Interface** — Settings organized into 6 default tabs: General, Contact, Social Media, SEO, Appearance, CSS & Scripts
- **🎛️ Smart Field Types** — Automatically renders the right input based on setting type: text, textarea, image upload, file upload, color picker, toggle (switch), checkbox
- **🔧 Modify Fields Mode** — Permission-gated mode to add new settings, delete existing ones, reorder fields, edit labels, and move settings between tabs
- **🎨 CSS & Scripts** — Dedicated tab with side-by-side CSS and JavaScript editors for Header, Body, and Footer sections
- **🔐 Flexible Authorization** — Control who can modify fields via plugin callback, Laravel Gate, or config
- **📦 Auto-Seeding** — Default settings are automatically seeded on first page load
- **⚡ Cache-Aware** — Automatically clears the settings cache after every save
- **🌗 Dark Theme Support** — Fully compatible with Filament's dark mode

## Requirements

- PHP 8.2+
- Laravel 10, 11, or 12
- Filament v4 or v5
- [`damodar-bhattarai/settings`](https://github.com/damodar-bhattarai/settings) package

## Installation

### Step 1: Install via Composer

```bash
composer require damodar-bhattarai/filament-settings
```

This will also install the [`damodar-bhattarai/settings`](https://github.com/damodar-bhattarai/settings) base package if not already installed. The plugin automatically copies and runs all required migrations on the first page load!

### Step 2: Register the Plugin

Add the plugin to your Filament panel provider (e.g., `app/Providers/Filament/AdminPanelProvider.php`):

```php
use DamodarBhattarai\FilamentSettings\FilamentSettingsPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ... your other configuration
        ->plugins([
            FilamentSettingsPlugin::make(),
        ]);
}
```

### Step 3: Publish Config (Optional)

```bash
php artisan vendor:publish --tag="filament-settings-config"
```

That's it! Navigate to **Settings → App Settings** in your panel.

## Usage

### Editing Settings

Simply visit the App Settings page, modify the values across any tab, and click **Save Settings**. All changes are persisted to the database and the settings cache is automatically cleared.

### Using Settings in Your Application

Retrieve saved settings anywhere in your application using the base package's helpers:

```php
// Get a single setting value
$siteName = settings('site_name');

// Get with a default fallback
$logo = settings('logo', 'default-logo.png');

// Get all settings as a collection
$all = settings();
```

### Modify Fields Mode

Click the **"Modify Fields"** button in the page header to enter Modify Mode. This mode is permission-gated and allows you to:

| Action | Description |
|--------|-------------|
| **Add Setting** | Create a new setting with a key, label, type, and target tab |
| **Add Tab** | Create a new tab group for organizing settings |
| **Delete Setting** | Remove a setting permanently (with confirmation) |
| **Move Setting** | Move a setting from one tab to another |
| **Edit Label** | Rename a setting's display label |
| **Reorder** | Move settings up or down within a tab |

Click **"Exit Modify Mode"** to return to value-only editing.

## Configuration

### Plugin Options

Customize the plugin behavior using the fluent API:

```php
FilamentSettingsPlugin::make()
    // Navigation
    ->navigationIcon('heroicon-o-cog-6-tooth')
    ->navigationGroup('Settings')
    ->navigationLabel('App Settings')
    ->navigationSort(100)

    // Page
    ->pageTitle('App Settings')
    ->slug('app-settings')

    // Authorization
    ->canModifyFields(true)
```

### Authorization

Control who can access the Modify Fields mode using one of three methods:

#### Option 1: Plugin Callback (Recommended)

Best for role-based access. Works with any permission package (Spatie, Bouncer, etc.):

```php
FilamentSettingsPlugin::make()
    ->canModifyFields(fn () => auth()->user()->hasRole('super_admin'))
```

#### Option 2: Laravel Gate

Define a gate in your `AuthServiceProvider` or any service provider:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('modify-settings-fields', function ($user) {
    return $user->hasRole('admin');
});
```

#### Option 3: Config File

Set a static boolean in `config/filament-settings.php`:

```php
'can_modify_fields' => true,
```

**Resolution priority:** Plugin callback → Laravel Gate → Config default.

#### Programmatic Control from Other Packages

Other packages can control the Modify Fields capability at runtime:

```php
use DamodarBhattarai\FilamentSettings\FilamentSettingsPlugin;

// Disable modify mode
FilamentSettingsPlugin::get()->canModifyFields(false);

// Enable conditionally
FilamentSettingsPlugin::get()->canModifyFields(
    fn () => app('some-package')->allowsSettingsModification()
);
```

### Default Tabs & Settings

The plugin ships with 6 pre-configured tabs. Customize them in `config/filament-settings.php`:

| Tab | Default Settings |
|-----|-----------------|
| **General** | Site Name, Tagline, Logo, Favicon, Footer Text |
| **Contact** | Email, Phone, Address, Map Embed URL |
| **Social Media** | Facebook, Twitter/X, Instagram, YouTube, LinkedIn, TikTok |
| **SEO** | Meta Title, Meta Description, Meta Keywords, Google Analytics ID |
| **Appearance** | Primary Color, Secondary Color |
| **CSS & Scripts** | Header/Body/Footer CSS and Scripts (side-by-side layout) |

You can add, remove, or modify any of these in the config file. Any groups created via the UI are also automatically displayed.

### Supported Field Types

| Type | Component | Description |
|------|-----------|-------------|
| `text` | TextInput | Single-line text input |
| `textarea` | Textarea | Multi-line text area with autosize |
| `image` | FileUpload | Image upload with preview |
| `file` | FileUpload | General file upload |
| `color` | ColorPicker | Color picker with hex output |
| `switch` | Toggle | On/off toggle switch |
| `checkbox` | Checkbox | Checkbox input |

### Custom Config Example

```php
// config/filament-settings.php

'default_settings' => [
    'general' => [
        'label' => 'General',
        'icon' => 'heroicon-o-home',
        'sort' => 1,
        'settings' => [
            [
                'key' => 'site_name',
                'type' => 'text',
                'label' => 'Site Name',
                'value' => 'My Website',
            ],
            [
                'key' => 'maintenance_mode',
                'type' => 'switch',
                'label' => 'Maintenance Mode',
                'value' => false,
            ],
            // Add more settings...
        ],
    ],
    // Add more tabs...
],
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on recent changes.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- [Damodar Bhattarai](https://github.com/damodar-bhattarai)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
