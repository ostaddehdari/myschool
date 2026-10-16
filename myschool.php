<?php
/**
 * Plugin Name: MySchool – مدرسه من
 * Description: Modular school management plugin.
 * Version: 0.1.8
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Text Domain: myschool
 */
if (!defined('ABSPATH')) exit;
define('MYSCHOOL_VERSION','0.1.8');
define('MYSCHOOL_FILE',__FILE__);
define('MYSCHOOL_PATH',plugin_dir_path(__FILE__));
define('MYSCHOOL_URL',plugin_dir_url(__FILE__));
require_once MYSCHOOL_PATH.'src/Core/Autoloader.php';
\MySchool\Core\Autoloader::register();
register_activation_hook(__FILE__,[\MySchool\Core\Lifecycle::class,'activate']);
register_deactivation_hook(__FILE__,[\MySchool\Core\Lifecycle::class,'deactivate']);
add_action('plugins_loaded',static function(){\MySchool\Core\Plugin::instance()->boot();});
