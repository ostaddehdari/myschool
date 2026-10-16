<?php
namespace MySchool\Core;
if (!defined('ABSPATH')) { exit; }
final class Installer {
 public const DB_VERSION='1';
 public const VERSION_OPTION='myschool_db_version';
 public static function maybeUpgrade(): void {
   if (!current_user_can('manage_options')) return;
   if ((int)get_option(self::VERSION_OPTION,0)<(int)self::DB_VERSION) self::install();
 }
 public static function install(): void {
   global $wpdb;
   if (!isset($wpdb) || !method_exists($wpdb,'get_charset_collate')) throw new \RuntimeException('WordPress database connection is unavailable.');
   if (!function_exists('dbDelta')) require_once ABSPATH.'wp-admin/includes/upgrade.php';
   $current=(int)get_option(self::VERSION_OPTION,0);
   if ($current>=(int)self::DB_VERSION) return;
   $lock='myschool_schema_lock';
   if (!add_option($lock,time(),'',false)) {
     $held=(int)get_option($lock,0);
     if ($held>0 && $held>time()-300) return;
     delete_option($lock);
     if (!add_option($lock,time(),'',false)) return;
   }
   try {
     foreach(Migrations::all() as $version=>$migration) {
       if ($version<=$current) continue;
       $migration->up($wpdb);
       update_option(self::VERSION_OPTION,(string)$version,false);
       $current=$version;
     }
   } finally { delete_option($lock); }
 }
}
