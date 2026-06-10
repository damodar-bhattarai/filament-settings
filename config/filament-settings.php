<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Navigation
    |--------------------------------------------------------------------------
    |
    | Configure how the settings page appears in the Filament navigation.
    |
    */

    'navigation_icon' => 'heroicon-o-cog-6-tooth',
    'navigation_group' => 'Settings',
    'navigation_label' => 'App Settings',
    'navigation_sort' => 100,
    'page_title' => 'App Settings',
    'slug' => 'app-settings',

    /*
    |--------------------------------------------------------------------------
    | File Upload Disk
    |--------------------------------------------------------------------------
    |
    | The filesystem disk used for image and file uploads.
    | This defaults to the base settings package's disk.
    |
    */

    'disk' => 'public',

    /*
    |--------------------------------------------------------------------------
    | Modify Fields Authorization
    |--------------------------------------------------------------------------
    |
    | Controls whether the "Modify Fields" mode is available.
    | Set to true to allow all users, false to deny all,
    | or use a gate/callback via the Plugin class for dynamic control.
    |
    */

    'can_modify_fields' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-Seed Default Settings
    |--------------------------------------------------------------------------
    |
    | When true, default settings will be seeded into the database
    | on first page load if the settings table is empty.
    |
    */

    'auto_seed' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | Define the default tabs and their settings. These are seeded into
    | the database on first load if auto_seed is enabled.
    |
    | Supported types: text, textarea, image, file, color, switch, checkbox
    |
    */

    'default_settings' => [

        'general' => [
            'label' => 'General',
            'icon' => 'heroicon-o-home',
            'sort' => 1,
            'settings' => [
                ['key' => 'site_name', 'type' => 'text', 'label' => 'Site Name', 'value' => ''],
                ['key' => 'site_tagline', 'type' => 'text', 'label' => 'Tagline', 'value' => ''],
                ['key' => 'logo', 'type' => 'image', 'label' => 'Logo', 'value' => ''],
                ['key' => 'favicon', 'type' => 'image', 'label' => 'Favicon', 'value' => ''],
                ['key' => 'footer_text', 'type' => 'textarea', 'label' => 'Footer Text', 'value' => ''],
            ],
        ],

        'contact' => [
            'label' => 'Contact',
            'icon' => 'heroicon-o-phone',
            'sort' => 2,
            'settings' => [
                ['key' => 'email', 'type' => 'text', 'label' => 'Email Address', 'value' => ''],
                ['key' => 'phone', 'type' => 'text', 'label' => 'Phone Number', 'value' => ''],
                ['key' => 'address', 'type' => 'textarea', 'label' => 'Address', 'value' => ''],
                ['key' => 'map_embed_url', 'type' => 'text', 'label' => 'Google Map Embed URL', 'value' => ''],
            ],
        ],

        'social' => [
            'label' => 'Social Media',
            'icon' => 'heroicon-o-share',
            'sort' => 3,
            'settings' => [
                ['key' => 'facebook_url', 'type' => 'text', 'label' => 'Facebook URL', 'value' => ''],
                ['key' => 'twitter_url', 'type' => 'text', 'label' => 'Twitter / X URL', 'value' => ''],
                ['key' => 'instagram_url', 'type' => 'text', 'label' => 'Instagram URL', 'value' => ''],
                ['key' => 'youtube_url', 'type' => 'text', 'label' => 'YouTube URL', 'value' => ''],
                ['key' => 'linkedin_url', 'type' => 'text', 'label' => 'LinkedIn URL', 'value' => ''],
                ['key' => 'tiktok_url', 'type' => 'text', 'label' => 'TikTok URL', 'value' => ''],
            ],
        ],

        'seo' => [
            'label' => 'SEO',
            'icon' => 'heroicon-o-magnifying-glass',
            'sort' => 4,
            'settings' => [
                ['key' => 'meta_title', 'type' => 'text', 'label' => 'Default Meta Title', 'value' => ''],
                ['key' => 'meta_description', 'type' => 'textarea', 'label' => 'Default Meta Description', 'value' => ''],
                ['key' => 'meta_keywords', 'type' => 'text', 'label' => 'Meta Keywords', 'value' => ''],
                ['key' => 'google_analytics_id', 'type' => 'text', 'label' => 'Google Analytics ID', 'value' => ''],
            ],
        ],

        'appearance' => [
            'label' => 'Appearance',
            'icon' => 'heroicon-o-paint-brush',
            'sort' => 5,
            'settings' => [
                ['key' => 'primary_color', 'type' => 'color', 'label' => 'Primary Color', 'value' => '#3B82F6'],
                ['key' => 'secondary_color', 'type' => 'color', 'label' => 'Secondary Color', 'value' => '#8B5CF6'],
            ],
        ],

        'css_scripts' => [
            'label' => 'CSS & Scripts',
            'icon' => 'heroicon-o-code-bracket',
            'sort' => 6,
            'settings' => [
                ['key' => 'header_css', 'type' => 'textarea', 'label' => 'Header CSS', 'value' => ''],
                ['key' => 'header_scripts', 'type' => 'textarea', 'label' => 'Header Scripts', 'value' => ''],
                ['key' => 'body_css', 'type' => 'textarea', 'label' => 'Body CSS', 'value' => ''],
                ['key' => 'body_scripts', 'type' => 'textarea', 'label' => 'Body Scripts', 'value' => ''],
                ['key' => 'footer_css', 'type' => 'textarea', 'label' => 'Footer CSS', 'value' => ''],
                ['key' => 'footer_scripts', 'type' => 'textarea', 'label' => 'Footer Scripts', 'value' => ''],
            ],
        ],

    ],

];
