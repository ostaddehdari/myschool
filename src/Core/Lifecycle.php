<?php
namespace MySchool\Core;
if(!defined('ABSPATH')){exit;}
final class Lifecycle {
 public static function activate():void {
  if(version_compare(PHP_VERSION,'8.0','<'))wp_die(esc_html__('MySchool requires PHP 8.0 or later.','myschool'));
  Installer::install();
  Capabilities::install();
  update_option('myschool_plugin_version',MYSCHOOL_VERSION,false);
 }
 public static function deactivate():void {/* Preserve all data and role permissions. */}
}
