<?php
namespace MySchool\Modules\Admin;
use MySchool\Contracts\ModuleInterface;
if (!defined('ABSPATH')) { exit; }
final class AdminModule implements ModuleInterface {
 public function id(): string {return 'admin';}
 public function register(): void {
   add_action('admin_menu',array($this,'registerMenu'));
   add_action('admin_enqueue_scripts',array($this,'enqueueAssets'));
 }
 public function registerMenu(): void {
   add_menu_page(__('مدرسه من','myschool'),__('مدرسه من','myschool'),'manage_options','myschool',array($this,'renderDashboard'),'dashicons-welcome-learn-more',26);
 }
 public function enqueueAssets(string $hook): void {
   if ($hook!=='toplevel_page_myschool') return;
   wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',array(),MYSCHOOL_VERSION);
 }
 public function renderDashboard(): void {
   if (!current_user_can('manage_options')) wp_die(esc_html__('You do not have permission to view this page.','myschool'));
   echo '<div class="wrap myschool-admin" dir="rtl"><h1>'.esc_html__('مدرسه من','myschool').'</h1><div class="myschool-panel"><div class="myschool-kicker">MySchool · Foundation</div><h2>'.esc_html__('هسته اولیه افزونه با موفقیت بارگذاری شد','myschool').'</h2><p>'.esc_html__('مرحله S00/W00.01: ساختار افزونه، بارگذار کلاس‌ها و ماژول‌ها آماده است.','myschool').'</p><span class="myschool-version">'.esc_html('v'.MYSCHOOL_VERSION).'</span></div></div>';
 }
}
