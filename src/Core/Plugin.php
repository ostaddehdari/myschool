<?php
namespace MySchool\Core;
use MySchool\Modules\Admin\AdminModule;
if (!defined('ABSPATH')) { exit; }
final class Plugin {
 private static ?self $instance=null;
 private ModuleRegistry $registry;
 private bool $booted=false;
 private function __construct(){ $this->registry=new ModuleRegistry(); }
 public static function instance(): self { return self::$instance ??= new self(); }
 public function boot(): void {
   if ($this->booted) return;
   $this->booted=true;
   load_plugin_textdomain('myschool',false,dirname(plugin_basename(MYSCHOOL_FILE)).'/languages');
   $this->registry->add(new AdminModule());
   $this->registry->boot();
   do_action('myschool/booted',$this);
 }
 public function modules(): ModuleRegistry { return $this->registry; }
}
