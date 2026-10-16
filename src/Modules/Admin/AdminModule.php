<?php
namespace MySchool\Modules\Admin;
use MySchool\Contracts\ModuleInterface;
use MySchool\Support\Jalali;
if (!defined('ABSPATH')) { exit; }
final class AdminModule implements ModuleInterface {
 public function id():string {return 'admin';}
 public function register():void {add_action('admin_menu',[$this,'registerMenu']);add_action('admin_enqueue_scripts',[$this,'enqueueAssets']);}
 public function registerMenu():void {add_menu_page(__('مدرسه من','myschool'),__('مدرسه من','myschool'),'myschool_manage_school','myschool',[$this,'renderDashboard'],'dashicons-welcome-learn-more',26);}
 public function enqueueAssets(string $hook):void {
  if($hook!=='toplevel_page_myschool')return;
  wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',[],MYSCHOOL_VERSION);
 }
 public function renderDashboard():void {
  if(!current_user_can('myschool_manage_school'))wp_die(esc_html__('Access denied','myschool'));
  $today=Jalali::format(wp_date('Y-m-d'));
  ?>
  <div class="wrap myschool-admin" dir="rtl">
   <div class="myschool-header"><div><div class="myschool-kicker">MYSCHOOL · FOUNDATION</div><h1>مدرسه من</h1><p>هسته مدیریت مدرسه، آماده توسعه ماژول‌ها</p></div><span class="myschool-version"><?php echo esc_html('v'.MYSCHOOL_VERSION); ?></span></div>
   <div class="myschool-grid">
    <section class="myschool-panel"><h2>زیرساخت</h2><p>بارگذار ماژول‌ها، نقش‌ها و جداول پایه نصب شده‌اند.</p><span class="myschool-tag">S00/W00.04</span></section>
    <section class="myschool-panel"><h2>تاریخ امروز</h2><p class="myschool-date"><?php echo esc_html($today); ?></p><p>تقویم شمسی، ذخیره‌سازی میلادی</p></section>
    <section class="myschool-panel"><h2>REST API</h2><p><code>myschool/v1/status</code></p><p>دسترسی با مجوز مدیر مدرسه</p></section>
   </div>
   <p class="myschool-footnote">تنظیمات مدرسه و ابزارهای دانش‌آموزان در استیج‌های آینده فعال می‌شوند.</p>
  </div><?php
 }
}
