<?php

namespace DamodarBhattarai\FilamentSettings;

use Closure;
use DamodarBhattarai\FilamentSettings\Filament\Pages\ManageSettings;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;

class FilamentSettingsPlugin implements Plugin
{
    protected bool|Closure|null $canModifyFields = null;

    protected ?string $navigationIcon = null;

    protected ?string $navigationGroup = null;

    protected ?string $navigationLabel = null;

    protected ?int $navigationSort = null;

    protected ?string $pageTitle = null;

    protected ?string $slug = null;

    /**
     * Create a new plugin instance.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Retrieve the plugin instance from the current panel.
     */
    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(static::class);

        return $plugin;
    }

    /**
     * Get the unique plugin identifier.
     */
    public function getId(): string
    {
        return 'filament-settings';
    }

    /**
     * Register plugin components with the panel.
     */
    public function register(Panel $panel): void
    {
        $panel->pages([
            ManageSettings::class,
        ]);
    }

    /**
     * Boot the plugin (runs after all plugins are registered).
     */
    public function boot(Panel $panel): void
    {
        //
    }

    // ─── Fluent Configuration API ────────────────────────────────────

    /**
     * Set whether the current user can modify fields (add, delete, reorder, relabel, move).
     *
     * Accepts a boolean or a Closure that receives the authenticated user.
     *
     * Usage:
     *   ->canModifyFields(true)
     *   ->canModifyFields(fn () => auth()->user()->hasRole('super_admin'))
     *   ->canModifyFields(fn (User $user) => $user->isAdmin())
     */
    public function canModifyFields(bool|Closure $condition = true): static
    {
        $this->canModifyFields = $condition;

        return $this;
    }

    /**
     * Resolve whether the current user can modify fields.
     *
     * Priority:
     * 1. Callback/boolean set via canModifyFields()
     * 2. Laravel Gate 'modify-settings-fields'
     * 3. Config default
     */
    public function resolveCanModifyFields(): bool
    {
        // 1. Plugin-level callback or boolean
        if ($this->canModifyFields !== null) {
            if ($this->canModifyFields instanceof Closure) {
                return (bool) call_user_func($this->canModifyFields, auth()->user());
            }

            return (bool) $this->canModifyFields;
        }

        // 2. Laravel Gate
        try {
            if (Gate::has('modify-settings-fields') && auth()->check()) {
                return Gate::allows('modify-settings-fields');
            }
        } catch (\Exception) {
            // Gate not defined or auth not available
        }

        // 3. Config default
        return (bool) config('filament-settings.can_modify_fields', true);
    }

    /**
     * Set the navigation icon.
     */
    public function navigationIcon(string $icon): static
    {
        $this->navigationIcon = $icon;

        return $this;
    }

    /**
     * Set the navigation group.
     */
    public function navigationGroup(?string $group): static
    {
        $this->navigationGroup = $group;

        return $this;
    }

    /**
     * Set the navigation label.
     */
    public function navigationLabel(string $label): static
    {
        $this->navigationLabel = $label;

        return $this;
    }

    /**
     * Set the navigation sort order.
     */
    public function navigationSort(int $sort): static
    {
        $this->navigationSort = $sort;

        return $this;
    }

    /**
     * Set the page title.
     */
    public function pageTitle(string $title): static
    {
        $this->pageTitle = $title;

        return $this;
    }

    /**
     * Set the page slug.
     */
    public function slug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    // ─── Getters ─────────────────────────────────────────────────────

    public function getNavigationIcon(): string
    {
        return $this->navigationIcon ?? config('filament-settings.navigation_icon', 'heroicon-o-cog-6-tooth');
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup ?? config('filament-settings.navigation_group', 'Settings');
    }

    public function getNavigationLabel(): string
    {
        return $this->navigationLabel ?? config('filament-settings.navigation_label', 'App Settings');
    }

    public function getNavigationSort(): int
    {
        return $this->navigationSort ?? (int) config('filament-settings.navigation_sort', 100);
    }

    public function getPageTitle(): string
    {
        return $this->pageTitle ?? config('filament-settings.page_title', 'App Settings');
    }

    public function getSlug(): string
    {
        return $this->slug ?? config('filament-settings.slug', 'app-settings');
    }
}
