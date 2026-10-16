<?php
namespace MySchool\Core;
use MySchool\Modules\Admin\AdminModule;
use MySchool\Modules\Api\ApiModule;
use MySchool\Modules\Settings\SettingsModule;
use MySchool\Modules\School\SchoolModule;
use MySchool\Modules\AcademicYears\AcademicYearsModule;
use MySchool\Modules\Shifts\ShiftsModule;
if (!defined('ABSPATH')) { exit; }
final class Plugin {
 private static ?self $instance=null;
 private ModuleRegistry $registry;
 private bool $booted=false;
 private function __construct(){ $this->registry=new ModuleRegistry(); }
 public static function instance():self{return self::$instance??=new self();}
 public function boot():void{
  if($this->booted)return;
  $this->booted=true;
  add_action('admin_init',array(Installer::class,'maybeUpgrade'));
  add_action('admin_init',array(Capabilities::class,'maybeUpgrade'));
  load_plugin_textdomain('myschool',false,dirname(plugin_basename(MYSCHOOL_FILE)).'/languages');
  $this->registry->add(new AdminModule());
  $this->registry->add(new ApiModule());
  $this->registry->add(new SettingsModule());
  $this->registry->add(new SchoolModule());
  $this->registry->add(new AcademicYearsModule());
  $this->registry->add(new ShiftsModule());
  $this->registry->boot();do_action('myschool/booted',$this);
 }
 public function modules():ModuleRegistry{return $this->registry;}
}