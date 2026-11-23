<?php namespace Webien\Site\Core;

/**
 * Feature switchboard: every feature in this plugin is registered here, can be switched off in
 * wp-admin (Settings > Site features) without deleting anything, and documents itself.
 *
 * A feature is one folder in src/ that a person would name: a widget, an integration, a query
 * family, an admin tool. Register it in index.php:
 *
 *     Features::add('hero-slider', 'Hero slider', function () {
 *         add_action('elementor/widgets/register', function ($widgets) {
 *             $widgets->register(new \Webien\Site\HeroSlider\Widget());
 *         });
 *     }, 'Slider for the start page hero.');
 *
 * The callback runs while the plugin loads, so it should only add hooks. Work done at load time
 * runs on every request, and a fatal there takes the whole site down.
 */
final class Features
{
    const OPTION = 'webien_site_features_off';
    const PAGE   = 'site-features';

    /** @var array<string, array{title: string, description: string}> */
    private static $features = [];

    private static $adminHooked = false;

    /**
     * Register a feature and boot it unless it is switched off.
     */
    public static function add(string $slug, string $title, callable $boot, string $description = ''): void
    {
        self::$features[$slug] = ['title' => $title, 'description' => $description];

        if (self::enabled($slug)) {
            $boot();
        }

        if (!self::$adminHooked) {
            self::$adminHooked = true;
            add_action('admin_menu', [self::class, 'menu']);
            add_action('admin_post_webien_site_features', [self::class, 'save']);
        }
    }

    public static function enabled(string $slug): bool
    {
        return !in_array($slug, (array) get_option(self::OPTION, []), true);
    }

    /** @return array<string, array{title: string, description: string}> */
    public static function all(): array
    {
        return self::$features;
    }

    public static function menu(): void
    {
        add_options_page(__('Site features', 'webien'), __('Site features', 'webien'), 'manage_options', self::PAGE, [self::class, 'render']);
    }

    public static function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        $off = (array) get_option(self::OPTION, []);
        echo '<div class="wrap"><h1>' . esc_html__('Site features', 'webien') . '</h1>';
        echo '<p>' . esc_html__('Everything this site\'s own plugin adds. Switching a feature off stops its code from loading; nothing is deleted.', 'webien') . '</p>';
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Saved.', 'webien') . '</p></div>';
        }
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('webien_site_features');
        echo '<input type="hidden" name="action" value="webien_site_features">';
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('On', 'webien') . '</th><th>' . esc_html__('Feature', 'webien') . '</th><th>' . esc_html__('What it does', 'webien') . '</th></tr></thead><tbody>';
        if (!self::$features) {
            echo '<tr><td colspan="3">' . esc_html__('No features registered yet.', 'webien') . '</td></tr>';
        }
        foreach (self::$features as $slug => $f) {
            $id = 'webien-feature-' . sanitize_html_class($slug);
            echo '<tr><td><input type="checkbox" id="' . esc_attr($id) . '" name="on[]" value="' . esc_attr($slug) . '"' . checked(!in_array($slug, $off, true), true, false) . '></td>';
            echo '<td><label for="' . esc_attr($id) . '"><strong>' . esc_html($f['title']) . '</strong></label><br><code>' . esc_html($slug) . '</code></td>';
            echo '<td>' . esc_html($f['description']) . '</td></tr>';
        }
        echo '</tbody></table>';
        submit_button();
        echo '</form></div>';
    }

    public static function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to change site features.', 'webien'), 403);
        }
        check_admin_referer('webien_site_features');
        $on  = array_map('sanitize_key', (array) ($_POST['on'] ?? []));
        $off = array_values(array_diff(array_keys(self::$features), $on));
        update_option(self::OPTION, $off, false);
        wp_safe_redirect(add_query_arg(['page' => self::PAGE, 'saved' => 1], admin_url('options-general.php')));
        exit;
    }
}
