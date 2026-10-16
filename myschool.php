<?php
/**
 * Plugin Name: MySchool – مدرسه من
 * Description: Modular school management system foundation for WordPress.
 * Version: 0.1.4
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: MySchool Team
 * Text Domain: myschool
 * Domain Path: /languages
 */
if (!defined('ABSPATH')) { exit; }
if (!defined('MYSCHOOL_VERSION')) { define('MYSCHOOL_VERSION', '0.1.4'); }
if (!defined('MYSCHOOL_FILE')) { define('MYSCHOOL_FILE', __FILE__); }
if (!defined('MYSCHOOL_PATH')) { define('MYSCHOOL_PATH', plugin_dir_path(__FILE__)); }
if (!defined('MYSCHOOL_URL')) { define('MYSCHOOL_URL', plugin_dir_url(__FILE__)); }
require_once MYSCHOOL_PATH . 'src/Core/Autoloader.php';
\MySchool\Core\Autoloader::register();
register_activation_hook(__FILE__, array(\MySchool\Core\Lifecycle::class, 'activate'));
register_deactivation_hook(__FILE__, array(\MySchool\Core\Lifecycle::class, 'deactivate'));
add_action('plugins_loaded', static function (): void { \MySchool\Core\Plugin::instance()->boot(); }, 10);
