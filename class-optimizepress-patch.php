<?php
/**
 * OptimizePress Tools.
 *
 * @package   Op_Tools
 * @author    OptimizePress <info@optimizepress.com>
 * @license   GPL-2.0+
 * @link      http://optimizepress.com
 * @copyright 2013 OptimizePress
 */

class Op_Patch {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since   1.0.0
     *
     * @const   string
     */
    const VERSION = '1.0.0';

    /**
     * Unique identifier for your plugin.
     *
     * Use this value (not the variable name) as the text domain when internationalizing strings of text. It should
     * match the Text Domain file header in the main plugin file.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_slug = 'optimizepress-patch';

    /**
     * Instance of this class.
     *
     * @since    1.0.0
     *
     * @var      object
     */
    protected static $instance = null;

    /**
     * Slug of the plugin screen.
     *
     * @since    1.0.0
     *
     * @var      string
     */
    protected $plugin_screen_hook_suffix = null;

    protected $layout_dir = 'inc/install_templates/';

    /**
     * Initialize the plugin by setting localization, filters, and administration functions.
     *
     * @since     1.0.0
     */
    private function __construct()
    {
        add_action('init', array($this, 'load_plugin_textdomain'));
        add_action('admin_menu', array($this, 'add_plugin_admin_menu'));

        add_filter('http_request_args', array($this, 'filter_http_request'),  100, 2);
        add_filter('http_request_timeout', array($this, 'adjust_http_timeout'), 100, 1);
    }

    public function adjust_http_timeout($timeout)
    {
        return 10;
    }

    public function filter_http_request($args, $url)
    {
        if (false !== strpos($url, 'download/switch') || false !== strpos($url, 'patch/download')) {
            $args['reject_unsafe_urls'] = false;
            $args['headers'] = array(
                'Op-Api-Key' => op_sl_get_key(),
                'Op-Installation-Url' => op_sl_get_url(),
            );
        }

        return $args;
    }

    /**
     * Return an instance of this class.
     *
     * @since     1.0.0
     *
     * @return    object    A single instance of this class.
     */
    public static function get_instance()
    {
        // If the single instance hasn't been set, set it now.
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain()
    {
        $domain = $this->plugin_slug;
        $locale = apply_filters('plugin_locale', get_locale(), $domain);

        load_textdomain($domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo');
        load_plugin_textdomain($domain, FALSE, basename(dirname(__FILE__)) . '/lang/');
    }

    /**
     * Register and enqueue admin-specific style sheet.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_styles()
    {
        if (! isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($screen->id == $this->plugin_screen_hook_suffix) {
            wp_enqueue_style($this->plugin_slug .'-admin-styles', plugins_url('css/admin.css', __FILE__), array(), $this->version);
        }

    }

    /**
     * Register and enqueue admin-specific JavaScript.
     *
     * @since     1.0.0
     *
     * @return    null    Return early if no settings page is registered.
     */
    public function enqueue_admin_scripts()
    {
        if (! isset($this->plugin_screen_hook_suffix)) {
            return;
        }

        $screen = get_current_screen();
        if ($screen->id == $this->plugin_screen_hook_suffix) {
            wp_enqueue_script($this->plugin_slug . '-admin-script', plugins_url('js/admin.js', __FILE__), array('jquery'), $this->version);
        }

    }

    /**
     * Register and enqueue public-facing style sheet.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_slug . '-plugin-styles', plugins_url('css/public.css', __FILE__), array(), $this->version);
    }

    /**
     * Register and enqueues public-facing JavaScript files.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_slug . '-plugin-script', plugins_url('js/public.js', __FILE__), array('jquery'), $this->version);
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu()
    {
        $this->plugin_screen_hook_suffix = add_management_page(
            __('OP Patches', $this->plugin_slug),
            __('OP Patches', $this->plugin_slug),
            'update_core',
            $this->plugin_slug,
            array($this, 'display_plugin_admin_page')
       );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_page()
    {
        $tabs = array(
            'patches'   => __('Patches', $this->plugin_slug),
            'soon'      => __('Coming soon...', $this->plugin_slug),
       );

        $types = $this->get_installed_op_plugins();
        if (OP_TYPE === 'theme') {
            array_unshift($types, 'theme');
        } else {
            array_unshift($types, 'plugin');
        }

        if (isset($_GET['patch_id'])) {
            $this->install_patch(sanitize_text_field($_GET['patch_id']), sanitize_text_field($_GET['patch_name']), sanitize_text_field($_GET['type']));
        } else {
            $patches = array();

            foreach ($types as $type) {
                $patches[$type] = $this->get_available_patches($type);
            }
        }

        include_once('views/admin.php');
    }

    

    /**
     * Return plugin slug.
     *
     * @return string
     */
    public function slug()
    {
        return $this->plugin_slug;
    }

    /**
     * Return available patches.
     *
     * @param $type
     *
     * @return array|null
     */
    public function get_available_patches($type)
    {
        $data = wp_remote_get(base64_decode(OptimizePress_Sl_Api::OP_SL_BASE_URL) . 'patch/list/' . $type . '/' . $this->get_package_version($type), array('headers' => array('Op-Api-Key' => op_sl_get_key(), 'Op-Installation-Url' => op_sl_get_url())));

        if (is_wp_error($data)) {
            $data = null;
        } else {
            $data = json_decode($data['body']);
        }

        return $data;
    }
    
    /**
     * Install patch.
     *
     * @param  string $id
     * @param  string $name
     * @param  string $type
     * @return void
     */
    public function install_patch($id, $name, $type)
    {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once plugin_dir_path(__FILE__) . 'inc/class-op-patches-skins.php';

        $skin = new Op_Patches_Skin(array(
            'title' => __('Install patch: ' . $name, $this->slug())
        ));

        $upgrader   = new WP_Upgrader($skin);

        $skin->set_slug($this->slug());
        $skin->add_strings();

        $skin->header();

        $result     = $upgrader->fs_connect(array(WP_CONTENT_DIR));
        $package    = $upgrader->download_package(base64_decode(OptimizePress_Sl_Api::OP_SL_BASE_URL) . 'patch/download/' . $id . '?secret=77qxyQ2yzEfhyPMfHMFcDta64u8PyrZrDxpy49fJ6L3GBX');
        $directory  = $upgrader->unpack_package($package);
        $status     = $upgrader->install_package(array(
            'source'            => trailingslashit($directory),
            'destination'       => trailingslashit($this->get_destination_dir($type)),
            'clear_destination' => false,
            'clear_working'     => true,
            'abort_if_destination_exists' => false,
        ));

        $skin->success(__('Patch installed successfully.', $this->slug()));

        $skin->feedback(__('Done.', $this->slug()));
        $skin->feedback(sprintf(__('<a href="%s">Return to previous screen</a>', $this->slug()), admin_url('tools.php?page=' . $this->slug() . '&tab=patches')));

        $skin->footer();

        exit();
    }

    /**
     * Return package (zip archive) dir name
     * @param  string $type
     * @return string
     */
    protected function get_package_dir($type)
    {
        switch ($type) {
            case 'theme': return 'optimizePressTheme';
            case 'plugin': return 'optimizePressPlugin';
        }

        wp_die("Unsupported type: $type");
    }

    /**
     * Return plugin name from package type
     * @param  string $type
     * @return string
     */
    protected function get_plugin_name($type)
    {
        switch ($type) {
            case 'plugin': return 'OptimizePress';
        }

        wp_die("Plugin $type not supported!");
    }

    /**
     * Return plugin dir.
     * @param  string $type
     * @return string
     */
    protected function get_plugin_dir($type)
    {
        $plugins    = get_plugins();
        $name       = $this->get_plugin_name($type);

        foreach ($plugins as $id => $plugin) {
            if ($plugin['Name'] === $name) {
                return dirname($id);
            }
        }

        wp_die("Plugin $type not matched with available ones!");
    }

    /**
     * Return all supported installed OP plugins
     * @return array
     */
    protected function get_installed_op_plugins()
    {
        $opPlugins = array();

        return $opPlugins;
    }

    /**
     * Return packages versions.
     *
     * @return array
     */
    protected function get_package_versions()
    {
        return array(
            'theme'     => OP_VERSION,
            'plugin'    => OP_VERSION
        );
    }

    /**
     * Return package version.
     *
     * @param  string $type
     * @return string
     */
    protected function get_package_version($type)
    {
        $versions = $this->get_package_versions();

        if (isset($versions[$type])) {
            return $versions[$type];
        }

        //wp_die("Version for package $type not found!");
    }

    /**
     * Return full plugin dir
     * @param  string $type
     * @return string
     */
    protected function get_destination_dir($type)
    {
        if ($type === 'theme') {
            return OP_DIR;
        } else {
            return WP_PLUGIN_DIR . '/' . $this->get_plugin_dir($type);
        }
    }
}
