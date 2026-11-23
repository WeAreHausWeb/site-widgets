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
- Before building assets, inspect the project's package scripts, build configuration, lockfiles, README, and existing `dist/` output to identify the active asset pipeline.
- Prefer the commands already defined by the project, especially package scripts, instead of assuming a specific bundler or invoking tool binaries directly.
- If the project has no package scripts for assets, infer the correct build and watch commands from the local configuration files and documentation before running anything.
- If the active pipeline is unclear, ask for confirmation rather than introducing or migrating to a different build tool.

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
- Place any new Elementor widgets under the existing `webien` widget category.
- When working with Elementor widgets, preserve the current control structure and existing editor UX unless the task requires changing it.
- Reuse existing render patterns for Elementor, JetEngine, and template output instead of inventing parallel flows.
- Keep site-specific business logic inside this plugin rather than scattering it into theme code.

# Asset Pipeline

- Asset compilation varies between projects. Inspect the local package manifest, build config files, source imports, enqueue paths, README, and existing compiled output before changing entrypoints or running builds.
- Use the project's current asset pipeline as-is unless the task specifically asks for a migration.
- When adding JS or SCSS entrypoints, follow the existing local build configuration and naming conventions.
- When framework-specific support is needed, verify the framework version and plugin requirements from the project's installed packages and config before adding dependencies or changing build behavior.
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

# Haus WP protocol (Claude sessions)

This repo is the only source of a site's own code. The rules below apply to people and Claude alike.

- **One repo per site:** `WeAreHausWeb/<domain>`, created by Simon from this template. `haus-site.json`
  says which site this checkout belongs to; in the template its domain is `TEMPLATE`, which means
  "not set up". Never create a site repo yourself.
- **Code never reaches the site through MCP** (Novamira or any other bridge). It is committed here and
  deployed by `.github/workflows/deploy.yml` on push to `master`. No child theme, no files edited on the
  server, no code in `novamira-sandbox/`.
- **Publish with the protocol script**, which lints, commits only the given paths with a `Task:` trailer,
  rebases on others' work (never force), pushes and waits until the site's `release.txt` shows the commit:
  `python3 -I <haus-wp-protocol>/skills/wp-operations/scripts/haus_site.py publish --task <id> --message "..." --paths <files>`.
  `committed`, `pushed` and `live` are separate results; only `live` means published.
- **Roll back** with `haus_site.py rollback --task <id>` (a revert commit, published the same way).
- **Features:** one folder per feature in `src/<Feature>/`, registered once in `index.php` with
  `Features::add('<slug>', '<Title>', fn () => ..., '<what it does>')`. Slugs are lowercase. The boot
  callback only adds hooks. Every feature can be switched off in Settings > Site features.
- **Public names are permanent:** widget names (`get_name()`), shortcodes, dynamic tags, option names,
  CSS classes and hooks are stored in the database and markup. Never rename one; add a new one.
- **Guard dependencies:** wrap calls into other plugins (ACF, Elementor Pro, Webien Suite, Haus MCP Manager)
  in `function_exists()` / `class_exists()`. A fatal at load takes the site down, and the only way back is
  a rollback commit.
- **Not deployed:** `haus-site.json`, `AGENTS.md`, `CLAUDE.md`, `.claude/`, `.haus/`, build config.
