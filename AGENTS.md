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
- Before building assets, inspect the project scripts and build config to identify the active asset pipeline.
- Prefer npm scripts from `package.json` when they exist, for example `npm run build`, `npm run dev`, or `npm run watch`.
- If the project still uses Laravel Mix and has no npm scripts for assets, use the existing Mix commands from that project, such as `npx mix --production` or `npx mix watch`.

# Code Standards

- Use the existing plugin structure before introducing new abstractions.
- Keep feature code grouped by module in `src/<FeatureName>/`.
- Place Elementor widget classes in `Widget.php` when that pattern already exists for the feature.
- Keep templates in feature-local `template/` or `templates/` directories.
- Keep frontend behavior in feature-local JS files and styling in feature-local SCSS files when the feature already has that structure.
- Use SCSS for generated styles. Do not add plain CSS source files unless the existing feature explicitly requires CSS.
- If generating JS or SCSS code with AI, add `AI generated code` as a top-of-file comment in the generated JS or SCSS source file.
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

- Asset compilation may be handled by Vite, Laravel Mix, or another existing project pipeline. Inspect `package.json`, `vite.config.js`, `webpack.mix.js`, and related docs before changing or running build commands.
- If the project uses Vite, prefer `vite.config.js`, npm scripts, and `rollupOptions.input` for JS and SCSS entrypoints.
- If the project uses Vite with Vue, use `@vitejs/plugin-vue` for Vue 3+ support. Do not add Vue 2 handling unless the existing project explicitly requires Vue 2.
- In Vite projects, the Vue plugin does not bundle Vue by itself; Vue is only included in compiled scripts when an entrypoint imports Vue.
- If the project uses Laravel Mix, preserve the existing `webpack.mix.js` behavior unless the task specifically asks for a migration.
- Keep output filenames stable and hash-free when WordPress enqueue calls point at fixed `dist/` files.
- Source files live in `assets/` and feature-local `src/**/js` or `src/**/style` paths.
- Rebuild `dist/` after changing JS or SCSS that is shipped by the plugin.
- Do not introduce a dev server workflow by default for WordPress plugins. Use the project's existing watch/build workflow unless the task asks otherwise.

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
