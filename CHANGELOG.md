# Changelog

All notable changes to `filament-settings` will be documented in this file.

## v1.1.0 - 2026-06-10

### 🎉 Initial Release

- Tabbed settings management page with 6 default tabs (General, Contact, Social Media, SEO, Appearance, CSS & Scripts)
- Support for 7 field types: text, textarea, image, file, color, switch, checkbox
- Modify Fields mode with add, delete, reorder, relabel, and move capabilities
- Three-level authorization: Plugin callback → Laravel Gate → Config default
- CSS & Scripts tab with side-by-side layout for Header/Body/Footer
- Zero-configuration auto-publishing of base package migrations/seeders during install
- Automatic database migration and default settings seeding on first settings page load
- Automatic cache clearing on save
- Dark theme support
- Compatible with Filament v4 and v5
