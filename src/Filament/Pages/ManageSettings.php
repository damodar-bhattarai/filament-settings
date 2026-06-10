<?php

namespace DamodarBhattarai\FilamentSettings\Filament\Pages;

use Closure;
use DamodarBhattarai\FilamentSettings\FilamentSettingsPlugin;
use DamodarBhattarai\Settings\Models\Setting;
use Filament\Actions\Action as PageAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action as FormAction;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ManageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament-settings::pages.manage-settings';

    public ?array $data = [];

    public bool $modifyMode = false;

    // ─── Navigation (delegated to Plugin) ────────────────────────────

    public static function getNavigationIcon(): ?string
    {
        try {
            return FilamentSettingsPlugin::get()->getNavigationIcon();
        } catch (\Exception) {
            return config('filament-settings.navigation_icon', 'heroicon-o-cog-6-tooth');
        }
    }

    public static function getNavigationGroup(): ?string
    {
        try {
            return FilamentSettingsPlugin::get()->getNavigationGroup();
        } catch (\Exception) {
            return config('filament-settings.navigation_group', 'Settings');
        }
    }

    public static function getNavigationLabel(): string
    {
        try {
            return FilamentSettingsPlugin::get()->getNavigationLabel();
        } catch (\Exception) {
            return config('filament-settings.navigation_label', 'App Settings');
        }
    }

    public static function getNavigationSort(): ?int
    {
        try {
            return FilamentSettingsPlugin::get()->getNavigationSort();
        } catch (\Exception) {
            return (int) config('filament-settings.navigation_sort', 100);
        }
    }

    public function getTitle(): string
    {
        try {
            return FilamentSettingsPlugin::get()->getPageTitle();
        } catch (\Exception) {
            return config('filament-settings.page_title', 'App Settings');
        }
    }

    public static function getSlug(?\Filament\Panel $panel = null): string
    {
        try {
            return FilamentSettingsPlugin::get()->getSlug();
        } catch (\Exception) {
            return config('filament-settings.slug', 'app-settings');
        }
    }

    // ─── Lifecycle ───────────────────────────────────────────────────

    public function mount(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            try {
                $paths = [];

                // 1. Base settings migration (copied to database/migrations)
                $baseMigrations = glob(database_path('migrations/*_create_settings_table.php'));
                if (! empty($baseMigrations)) {
                    $paths = array_merge($paths, $baseMigrations);
                }

                // 2. Our plugin migration (either published or vendor path)
                $pluginMigrations = glob(database_path('migrations/*_add_filament_columns_to_settings_table.php'));
                if (! empty($pluginMigrations)) {
                    $paths = array_merge($paths, $pluginMigrations);
                } else {
                    $localPluginPath = realpath(__DIR__ . '/../../../database/migrations/add_filament_columns_to_settings_table.php');
                    if ($localPluginPath && file_exists($localPluginPath)) {
                        $paths[] = $localPluginPath;
                    }
                }

                if (! empty($paths)) {
                    \Illuminate\Support\Facades\Artisan::call('migrate', [
                        '--path' => $paths,
                        '--force' => true,
                    ]);
                }
            } catch (\Exception $e) {
                // Catch migration failures gracefully
            }
        }

        $this->seedDefaultSettingsIfNeeded();
        $this->fillFormFromDatabase();
    }

    // ─── Authorization ───────────────────────────────────────────────

    /**
     * Check if the current user can modify fields.
     */
    protected function canModifyFields(): bool
    {
        try {
            return FilamentSettingsPlugin::get()->resolveCanModifyFields();
        } catch (\Exception) {
            return (bool) config('filament-settings.can_modify_fields', true);
        }
    }

    // ─── Header Actions ──────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            // Toggle Modify Mode button
            PageAction::make('toggleModifyMode')
                ->label(fn () => $this->modifyMode ? 'Exit Modify Mode' : 'Modify Fields')
                ->icon(fn () => $this->modifyMode ? 'heroicon-o-lock-closed' : 'heroicon-o-pencil-square')
                ->color(fn () => $this->modifyMode ? 'warning' : 'gray')
                ->action(function () {
                    $this->modifyMode = ! $this->modifyMode;
                })
                ->visible(fn () => $this->canModifyFields()),

            // Add New Setting (only in modify mode)
            PageAction::make('addSetting')
                ->label('Add Setting')
                ->icon('heroicon-o-plus-circle')
                ->color('success')
                ->form([
                    TextInput::make('key')
                        ->label('Setting Key')
                        ->required()
                        ->unique('settings', 'key')
                        ->alphaDash()
                        ->helperText('Unique key used to retrieve this setting (e.g., site_name)')
                        ->maxLength(255),

                    TextInput::make('label')
                        ->label('Display Label')
                        ->required()
                        ->maxLength(255),

                    Select::make('type')
                        ->label('Field Type')
                        ->required()
                        ->options([
                            'text' => 'Text Input',
                            'textarea' => 'Textarea',
                            'image' => 'Image Upload',
                            'file' => 'File Upload',
                            'color' => 'Color Picker',
                            'switch' => 'Switch (Toggle)',
                            'checkbox' => 'Checkbox',
                        ])
                        ->default('text'),

                    Select::make('group')
                        ->label('Tab / Group')
                        ->required()
                        ->options(fn () => $this->getGroupOptions())
                        ->default('general'),
                ])
                ->action(function (array $data): void {
                    $maxOrder = Setting::where('group', $data['group'])->max('tab_order') ?? 0;

                    Setting::create([
                        'key' => $data['key'],
                        'label' => $data['label'],
                        'type' => $data['type'],
                        'group' => $data['group'],
                        'value' => json_encode($this->getDefaultValueForType($data['type'])),
                        'tab_order' => $maxOrder + 1,
                    ]);

                    $this->clearSettingsCache();
                    $this->fillFormFromDatabase();

                    Notification::make()
                        ->title('Setting added')
                        ->body("'{$data['label']}' has been added to the '{$this->getGroupLabel($data['group'])}' tab.")
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->modifyMode && $this->canModifyFields()),

            // Add New Tab
            PageAction::make('addTab')
                ->label('Add Tab')
                ->icon('heroicon-o-folder-plus')
                ->color('info')
                ->form([
                    TextInput::make('tab_key')
                        ->label('Tab Key')
                        ->required()
                        ->alphaDash()
                        ->helperText('Internal key for the tab (e.g., custom_section)')
                        ->maxLength(255),

                    TextInput::make('tab_label')
                        ->label('Tab Label')
                        ->required()
                        ->helperText('Display name shown in the UI')
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    // Create a placeholder setting in the new group so the tab appears
                    $key = Str::slug($data['tab_key'], '_') . '_placeholder';

                    Setting::create([
                        'key' => $key,
                        'label' => 'New Setting',
                        'type' => 'text',
                        'group' => Str::slug($data['tab_key'], '_'),
                        'value' => json_encode(''),
                        'tab_order' => 0,
                    ]);

                    $this->clearSettingsCache();
                    $this->fillFormFromDatabase();

                    Notification::make()
                        ->title('Tab created')
                        ->body("'{$data['tab_label']}' tab has been added.")
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->modifyMode && $this->canModifyFields()),
        ];
    }

    // ─── Form Definition ─────────────────────────────────────────────

    public function form($form)
    {
        return $form
            ->schema([
                $this->buildTabs(),
            ])
            ->statePath('data');
    }

    /**
     * Build the tabbed form layout from database settings.
     */
    protected function buildTabs()
    {
        $allSettings = $this->getAllSettingsGrouped();
        $tabConfig = config('filament-settings.default_settings', []);

        $tabs = [];

        // Build tabs in config-defined order first
        foreach ($tabConfig as $groupKey => $group) {
            if ($allSettings->has($groupKey)) {
                $tabs[] = $this->buildTab($groupKey, $group, $allSettings->get($groupKey));
            }
        }

        // Append any custom groups not defined in config
        foreach ($allSettings as $groupKey => $groupSettings) {
            if (! isset($tabConfig[$groupKey])) {
                $tabs[] = $this->buildTab($groupKey, [
                    'label' => Str::title(str_replace('_', ' ', $groupKey)),
                    'icon' => 'heroicon-o-squares-2x2',
                ], $groupSettings);
            }
        }

        return Tabs::make('settings_tabs')
            ->tabs($tabs)
            ->persistTabInQueryString('tab');
    }

    /**
     * Build a single tab with its fields.
     */
    protected function buildTab(string $groupKey, array $config, Collection $settings)
    {
        // Special layout for CSS & Scripts tab
        if ($groupKey === 'css_scripts') {
            $schema = $this->buildCssScriptsSchema($settings);
        } else {
            $schema = $settings
                ->sortBy('tab_order')
                ->map(fn (Setting $setting) => $this->buildFieldForSetting($setting))
                ->values()
                ->all();
        }

        return Tab::make($config['label'] ?? Str::title(str_replace('_', ' ', $groupKey)))
            ->icon($config['icon'] ?? 'heroicon-o-squares-2x2')
            ->schema($schema)
            ->badge($settings->count());
    }

    /**
     * Build the CSS & Scripts tab with side-by-side layout for each placement.
     */
    protected function buildCssScriptsSchema(Collection $settings): array
    {
        $placements = [
            'Header' => ['header_css', 'header_scripts'],
            'Body' => ['body_css', 'body_scripts'],
            'Footer' => ['footer_css', 'footer_scripts'],
        ];

        $schema = [];

        foreach ($placements as $sectionLabel => $keys) {
            $fields = [];

            foreach ($keys as $key) {
                $setting = $settings->firstWhere('key', $key);

                if ($setting) {
                    $fields[] = $this->buildFieldForSetting($setting);
                }
            }

            if (! empty($fields)) {
                $schema[] = Section::make($sectionLabel)
                    ->description("Add custom CSS and Scripts to the {$sectionLabel} section of your site.")
                    ->icon(match ($sectionLabel) {
                        'Header' => 'heroicon-o-arrow-up',
                        'Body' => 'heroicon-o-document-text',
                        'Footer' => 'heroicon-o-arrow-down',
                    })
                    ->schema([
                        Grid::make(2)->schema($fields),
                    ])
                    ->collapsible()
                    ->compact();
            }
        }

        // Include any extra CSS & Scripts settings not in the standard placements
        $standardKeys = collect($placements)->flatten()->all();
        $extraSettings = $settings->filter(fn (Setting $s) => ! in_array($s->key, $standardKeys));

        foreach ($extraSettings as $setting) {
            $schema[] = $this->buildFieldForSetting($setting);
        }

        return $schema;
    }

    /**
     * Build the appropriate Filament form field for a setting based on its type.
     */
    protected function buildFieldForSetting(Setting $setting)
    {
        $fieldName = "settings.{$setting->key}";
        $label = $setting->label ?: Str::title(str_replace('_', ' ', $setting->key));

        $field = match ($setting->type) {
            'textarea' => Textarea::make($fieldName)
                ->label($label)
                ->rows(4)
                ->autosize()
                ->columnSpanFull(),

            'image' => FileUpload::make($fieldName)
                ->label($label)
                ->image()
                ->imagePreviewHeight('100')
                ->disk(config('filament-settings.disk', 'public'))
                ->directory('settings')
                ->visibility('public')
                ->columnSpanFull(),

            'file' => FileUpload::make($fieldName)
                ->label($label)
                ->disk(config('filament-settings.disk', 'public'))
                ->directory('settings')
                ->visibility('public')
                ->columnSpanFull(),

            'color' => ColorPicker::make($fieldName)
                ->label($label),

            'switch' => Toggle::make($fieldName)
                ->label($label)
                ->inline(false),

            'checkbox' => Checkbox::make($fieldName)
                ->label($label),

            default => TextInput::make($fieldName)
                ->label($label),
        };

        // ── Modify Mode: hint action to Move to another tab ──
        $field = $field->hintAction(
            FormAction::make("move_{$setting->key}")
                ->icon('heroicon-m-arrows-right-left')
                ->tooltip('Move to another tab')
                ->visible(fn (): bool => $this->modifyMode && $this->canModifyFields())
                ->form([
                    Select::make('target_group')
                        ->label('Move to Tab')
                        ->options(fn () => $this->getGroupOptions())
                        ->default($setting->group)
                        ->required(),
                ])
                ->action(function (array $data) use ($setting): void {
                    if ($data['target_group'] === $setting->group) {
                        return;
                    }

                    $maxOrder = Setting::where('group', $data['target_group'])->max('tab_order') ?? 0;

                    $setting->update([
                        'group' => $data['target_group'],
                        'tab_order' => $maxOrder + 1,
                    ]);

                    $this->clearSettingsCache();
                    $this->fillFormFromDatabase();

                    Notification::make()
                        ->title('Setting moved')
                        ->body("'{$setting->label}' moved to '{$this->getGroupLabel($data['target_group'])}'.")
                        ->success()
                        ->send();
                }),
        );

        // ── Modify Mode: hint action to Edit Label ──
        $field = $field->hintAction(
            FormAction::make("relabel_{$setting->key}")
                ->icon('heroicon-m-pencil')
                ->tooltip('Edit label')
                ->visible(fn (): bool => $this->modifyMode && $this->canModifyFields())
                ->form([
                    TextInput::make('new_label')
                        ->label('New Label')
                        ->default($setting->label ?: Str::title(str_replace('_', ' ', $setting->key)))
                        ->required()
                        ->maxLength(255),
                ])
                ->action(function (array $data) use ($setting): void {
                    $setting->update(['label' => $data['new_label']]);

                    $this->clearSettingsCache();
                    $this->fillFormFromDatabase();

                    Notification::make()
                        ->title('Label updated')
                        ->success()
                        ->send();
                }),
        );

        // ── Modify Mode: hint action to Delete ──
        $field = $field->hintAction(
            FormAction::make("delete_{$setting->key}")
                ->icon('heroicon-m-trash')
                ->tooltip('Delete setting')
                ->color('danger')
                ->visible(fn (): bool => $this->modifyMode && $this->canModifyFields())
                ->requiresConfirmation()
                ->modalHeading("Delete '{$label}'")
                ->modalDescription('Are you sure you want to permanently delete this setting? This action cannot be undone.')
                ->action(function () use ($setting): void {
                    $setting->delete();

                    $this->clearSettingsCache();
                    $this->fillFormFromDatabase();

                    Notification::make()
                        ->title('Setting deleted')
                        ->body("'{$setting->label}' has been deleted.")
                        ->warning()
                        ->send();
                }),
        );

        // ── Modify Mode: hint action to Reorder (move up/down) ──
        $field = $field->hintAction(
            FormAction::make("order_up_{$setting->key}")
                ->icon('heroicon-m-arrow-up')
                ->tooltip('Move up')
                ->visible(fn (): bool => $this->modifyMode && $this->canModifyFields())
                ->action(function () use ($setting): void {
                    $this->reorderSetting($setting, 'up');
                }),
        );

        $field = $field->hintAction(
            FormAction::make("order_down_{$setting->key}")
                ->icon('heroicon-m-arrow-down')
                ->tooltip('Move down')
                ->visible(fn (): bool => $this->modifyMode && $this->canModifyFields())
                ->action(function () use ($setting): void {
                    $this->reorderSetting($setting, 'down');
                }),
        );

        return $field;
    }

    // ─── Save ────────────────────────────────────────────────────────

    /**
     * Save all settings from the form to the database.
     */
    public function save(): void
    {
        $formData = $this->form->getState();
        $settings = $formData['settings'] ?? [];

        foreach ($settings as $key => $value) {
            $settingModel = Setting::where('key', $key)->first();

            if (! $settingModel) {
                continue;
            }

            // Handle file uploads — Filament stores them as temp paths, we need the stored path
            if (in_array($settingModel->type, ['image', 'file'])) {
                // Filament handles file storage automatically via FileUpload component
                // The value here is already the stored file path
                $storedValue = is_array($value) ? (reset($value) ?: '') : ($value ?? '');
            } elseif (in_array($settingModel->type, ['switch', 'checkbox'])) {
                $storedValue = $value ? true : false;
            } else {
                $storedValue = $value ?? '';
            }

            $settingModel->update([
                'value' => json_encode($storedValue),
            ]);
        }

        $this->clearSettingsCache();

        Notification::make()
            ->title('Settings saved')
            ->body('All settings have been saved successfully.')
            ->success()
            ->send();
    }

    // ─── Helpers ─────────────────────────────────────────────────────

    /**
     * Fill the form with current database values.
     */
    protected function fillFormFromDatabase(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return;
        }

        $settings = Setting::orderBy('tab_order')->get();

        $data = ['settings' => []];

        foreach ($settings as $setting) {
            $value = $setting->value;

            // Decode JSON-encoded values
            if (is_string($value)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $value = $decoded;
                }
            }

            // Convert boolean-like values for switches and checkboxes
            if (in_array($setting->type, ['switch', 'checkbox'])) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }

            $data['settings'][$setting->key] = $value;
        }

        $this->form->fill($data);
    }

    /**
     * Seed default settings into the database if the table is empty.
     */
    protected function seedDefaultSettingsIfNeeded(): void
    {
        if (! config('filament-settings.auto_seed', true)) {
            return;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return;
        }

        if (Setting::count() > 0) {
            return;
        }

        $defaults = config('filament-settings.default_settings', []);

        foreach ($defaults as $groupKey => $group) {
            $order = 0;

            foreach ($group['settings'] ?? [] as $settingDef) {
                Setting::create([
                    'key' => $settingDef['key'],
                    'label' => $settingDef['label'] ?? Str::title(str_replace('_', ' ', $settingDef['key'])),
                    'type' => $settingDef['type'] ?? 'text',
                    'group' => $groupKey,
                    'value' => json_encode($settingDef['value'] ?? ''),
                    'tab_order' => $order++,
                ]);
            }
        }
    }

    /**
     * Get all settings grouped by their group column.
     */
    protected function getAllSettingsGrouped(): Collection
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return collect();
        }

        return Setting::orderBy('tab_order')->get()->groupBy('group');
    }

    /**
     * Get available group options for select dropdowns.
     */
    protected function getGroupOptions(): array
    {
        $tabConfig = config('filament-settings.default_settings', []);
        $options = [];

        // Config-defined groups
        foreach ($tabConfig as $key => $group) {
            $options[$key] = $group['label'] ?? Str::title(str_replace('_', ' ', $key));
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('settings')) {
            return $options;
        }

        // Any additional groups from the database
        $dbGroups = Setting::select('group')->distinct()->pluck('group');

        foreach ($dbGroups as $group) {
            if (! isset($options[$group])) {
                $options[$group] = Str::title(str_replace('_', ' ', $group));
            }
        }

        return $options;
    }

    /**
     * Get the display label for a group key.
     */
    protected function getGroupLabel(string $groupKey): string
    {
        $tabConfig = config('filament-settings.default_settings', []);

        return $tabConfig[$groupKey]['label']
            ?? Str::title(str_replace('_', ' ', $groupKey));
    }

    /**
     * Get the default value for a given setting type.
     */
    protected function getDefaultValueForType(string $type): mixed
    {
        return match ($type) {
            'switch', 'checkbox' => false,
            'color' => '#000000',
            default => '',
        };
    }

    /**
     * Reorder a setting within its group (move up or down).
     */
    protected function reorderSetting(Setting $setting, string $direction): void
    {
        $siblings = Setting::where('group', $setting->group)
            ->orderBy('tab_order')
            ->get();

        $currentIndex = $siblings->search(fn ($s) => $s->id === $setting->id);

        if ($currentIndex === false) {
            return;
        }

        $targetIndex = $direction === 'up'
            ? $currentIndex - 1
            : $currentIndex + 1;

        // Bounds check
        if ($targetIndex < 0 || $targetIndex >= $siblings->count()) {
            return;
        }

        // Swap tab_order values
        $targetSetting = $siblings[$targetIndex];
        $originalOrder = $setting->tab_order;

        $setting->update(['tab_order' => $targetSetting->tab_order]);
        $targetSetting->update(['tab_order' => $originalOrder]);

        $this->clearSettingsCache();
        $this->fillFormFromDatabase();
    }

    /**
     * Clear the base settings package cache.
     */
    protected function clearSettingsCache(): void
    {
        Cache::forget('damodarbhattarai-settings');
    }
}
