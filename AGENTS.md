# Project Overview

WordPress plugin with site-specific functionality such as Elementor widgets, custom integrations, utility classes, and frontend assets.  
The plugin follows a modular structure under `src/`, where each feature or widget typically owns its PHP class, JS, SCSS, and template files.

# Key Directories

- `index.php`: plugin bootstrap and registrations
- `src/`: PHP modules, Elementor widgets, templates, feature-specific JS/SCSS
- `assets/`: shared frontend/admin assets
- `dist/`: compiled build output
- `vendor/`: Composer dependencies

# Build & Test

- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Build production assets: `npx mix --production`
- Watch assets during development: `npx mix watch`

# Code Standards

- Use the existing plugin structure before introducing new abstractions.
- Keep feature code grouped by module in `src/<FeatureName>/`.
- Place Elementor widget classes in `Widget.php` when that pattern already exists for the feature.
- Keep templates in feature-local `template/` or `templates/` directories.
- Keep frontend behavior in feature-local JS files and styling in feature-local SCSS files when the feature already has that structure.
- Prefer small, targeted edits over broad refactors.
- Follow existing naming, namespace, and registration patterns in the plugin.
- Use 4 spaces for indentation in PHP files.
- Do not edit compiled files in `dist/` manually unless the task specifically requires it.

# WordPress / Elementor Guidance

- Prefer WordPress core APIs for posts, terms, metadata, options, queries, hooks, escaping, and localization.
- When working with Elementor widgets, preserve the current control structure and existing editor UX unless the task requires changing it.
- Reuse existing render patterns for Elementor, JetEngine, and template output instead of inventing parallel flows.
- Keep site-specific business logic inside this plugin rather than scattering it into theme code.

# Asset Pipeline

- Asset compilation is handled with Laravel Mix via `webpack.mix.js`.
- Source files live in `assets/` and feature-local `src/**/js` or `src/**/style` paths.
- Rebuild `dist/` after changing JS or SCSS that is shipped by the plugin.

# Testing Requirements

- Run `php -l` on edited PHP files after making PHP changes.
- Rebuild frontend assets after JS/SCSS changes and verify that the expected compiled files are updated.
- Verify WordPress-facing changes in context when possible, especially:
  - Elementor widget rendering
  - admin controls
  - template output
  - asset loading
  - dynamic data integrations

# Notes for Agents

- This plugin is a reusable site-plugin pattern, so keep documentation and structure generic enough to apply to similar sibling plugins.
- Avoid assumptions that every site plugin contains the same widgets or integrations.
- When adding new code, match the local feature layout instead of imposing a new global pattern.
