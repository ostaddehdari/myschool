<?php
namespace MySchool\Modules\Settings;
use MySchool\Contracts\ModuleInterface;
use MySchool\Core\AuditLog;
if(!defined('ABSPATH')){exit;}
final class SettingsModule implements ModuleInterface {
 public function id():string{return 'settings';}
 public function register():void {
  add_action('admin_menu',[$this,'menu']);
  add_action('admin_init',[$this,'settings']);
  add_action('update_option_myschool_settings',[$this,'updated'],10,2);
 }
 public function defaults():array {return ['school_name'=>'','school_phone'=>'','school_email'=>'','timezone'=>'Asia/Tehran','sms_enabled'=>0];}
 public function sanitize($input):array {
  $input=is_array($input)?$input:[];
  $out=$this->defaults();
  $out['school_name']=sanitize_text_field($input['school_name']??'');
  $out['school_phone']=sanitize_text_field($input['school_phone']??'');
  $out['school_email']=sanitize_email($input['school_email']??'');
  $zone=(string)($input['timezone']??'Asia/Tehran');
  $out['timezone']=in_array($zone,['Asia/Tehran','Asia/Baku','UTC'],true)?$zone:'Asia/Tehran';
  $out['sms_enabled']=!empty($input['sms_enabled'])?1:0;
  return $out;
 }
 public function settings():void {register_setting('myschool_settings_group','myschool_settings',['type'=>'array','sanitize_callback'=>[$this,'sanitize'],'default'=>$this->defaults()]);}
 public function updated($old,$new):void {
  if(!is_array($old)||!is_array($new))return;
  $keys=array_keys(array_diff_assoc($new,$old));
  if($keys)AuditLog::record('settings.updated','school',0,['setting_keys'=>implode(',',$keys)]);
 }
 public function menu():void {add_submenu_page('myschool','تنظیمات مدرسه','تنظیمات','myschool_manage_school','myschool-settings',[$this,'render']);}
 public function render():void {
  if(!current_user_can('myschool_manage_school'))wp_die(esc_html__('Access denied','myschool'));
  $settings=array_merge($this->defaults(),(array)get_option('myschool_settings',[])); ?>
  <div class="wrap myschool-admin" dir="rtl"><h1>تنظیمات مدرسه</h1><form method="post" action="options.php" class="myschool-panel">
  <?php settings_fields('myschool_settings_group'); ?>
  <table class="form-table" role="presentation">
   <tr><th><label for="myschool-school-name">نام مدرسه</label></th><td><input class="regular-text" id="myschool-school-name" name="myschool_settings[school_name]" value="<?php echo esc_attr($settings['school_name']); ?>"></td></tr>
   <tr><th><label for="myschool-phone">تلفن</label></th><td><input class="regular-text" id="myschool-phone" name="myschool_settings[school_phone]" value="<?php echo esc_attr($settings['school_phone']); ?>"></td></tr>
   <tr><th><label for="myschool-email">ایمیل مدرسه</label></th><td><input type="email" class="regular-text" id="myschool-email" name="myschool_settings[school_email]" value="<?php echo esc_attr($settings['school_email']); ?>"></td></tr>
   <tr><th><label for="myschool-zone">منطقه زمانی</label></th><td><select id="myschool-zone" name="myschool_settings[timezone]">
   <?php foreach(['Asia/Tehran'=>'تهران','Asia/Baku'=>'باکو','UTC'=>'UTC'] as $zone=>$label):?><option value="<?php echo esc_attr($zone); ?>" <?php selected($settings['timezone'],$zone); ?>><?php echo esc_html($label); ?></option><?php endforeach; ?></select></td></tr>
   <tr><th>فعال‌سازی پیامک</th><td><label><input type="checkbox" name="myschool_settings[sms_enabled]" value="1" <?php checked($settings['sms_enabled'],1); ?>> فعال (پس از اتصال در استیج پیامک)</label></td></tr>
  </table><?php submit_button('ذخیره تنظیمات'); ?></form></div><?php
 }
}
