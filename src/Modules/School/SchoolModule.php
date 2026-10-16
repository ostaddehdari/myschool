<?php
namespace MySchool\Modules\School;
use MySchool\Contracts\ModuleInterface;
use MySchool\Core\AuditLog;
if (!defined('ABSPATH')) { exit; }
final class SchoolModule implements ModuleInterface {
 public function id(): string { return 'school'; }
 public function register(): void {
  add_action('admin_menu', [$this,'menu']);
  add_action('admin_post_myschool_save_school', [$this,'save']);
  add_action('admin_enqueue_scripts', [$this,'assets']);
 }
 public function menu(): void { add_submenu_page('myschool','مشخصات مدرسه','مشخصات مدرسه','myschool_manage_school','myschool-school',[$this,'render']); }
 public function assets(string $hook): void {
  if ($hook !== 'myschool_page_myschool-school') return;
  wp_enqueue_media();
  wp_enqueue_script('myschool-school', MYSCHOOL_URL.'assets/js/school.js',['jquery'],MYSCHOOL_VERSION,true);
  wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',[],MYSCHOOL_VERSION);
 }
 public function current(): array {
  global $wpdb;
  $table=$wpdb->prefix.'myschool_schools';
  $row=$wpdb->get_row("SELECT * FROM {$table} ORDER BY id ASC LIMIT 1",ARRAY_A);
  return is_array($row)?$row:[];
 }
 public function save(): void {
  if (!current_user_can('myschool_manage_school')) wp_die(esc_html__('Access denied','myschool'),'', ['response'=>403]);
  check_admin_referer('myschool_save_school','myschool_school_nonce');
  global $wpdb;
  $raw=isset($_POST['school']) && is_array($_POST['school'])?wp_unslash($_POST['school']):[];
  $data=[];
  foreach (['name','code','province','city','postal_code','phone','email','website','principal_name'] as $field) {
   $data[$field]=sanitize_text_field($raw[$field]??'');
  }
  $data['address']=sanitize_textarea_field($raw['address']??'');
  $data['email']=sanitize_email($raw['email']??'');
  $data['website']=esc_url_raw($raw['website']??'');
  $data['logo_attachment_id']=absint($raw['logo_attachment_id']??0);
  if ($data['logo_attachment_id'] && (!wp_attachment_is_image($data['logo_attachment_id']) || !current_user_can('read_post',$data['logo_attachment_id']))) {
   $data['logo_attachment_id']=0;
  }
  if ($data['name']==='' || (isset($raw['email']) && $raw['email']!=='' && !is_email($data['email']))) {
   wp_safe_redirect(add_query_arg(['page'=>'myschool-school','myschool_notice'=>'invalid'],admin_url('admin.php')));exit;
  }
  $table=$wpdb->prefix.'myschool_schools';
  $current=$this->current();
  $now=current_time('mysql',true);
  $data['updated_at']=$now;
  if (!empty($current['id'])) { $result=$wpdb->update($table,$data,['id'=>(int)$current['id']]); }
  else { $data['status']='active';$data['created_at']=$now;$result=$wpdb->insert($table,$data); }
  if ($result===false) { wp_safe_redirect(add_query_arg(['page'=>'myschool-school','myschool_notice'=>'error'],admin_url('admin.php')));exit; }
  try { AuditLog::record('school.profile_saved','school',!empty($current['id'])?(int)$current['id']:(int)$wpdb->insert_id,['source'=>'admin']); } catch (\Throwable $e) { error_log('[MySchool] Could not write profile audit event'); }
  wp_safe_redirect(add_query_arg(['page'=>'myschool-school','myschool_notice'=>'saved'],admin_url('admin.php')));exit;
 }
 public function render(): void {
  if (!current_user_can('myschool_manage_school')) wp_die(esc_html__('Access denied','myschool'));
  $school=$this->current();
  $fields=['name'=>'نام مدرسه','code'=>'کد مدرسه','principal_name'=>'نام مدیر','province'=>'استان','city'=>'شهر','postal_code'=>'کد پستی','phone'=>'تلفن','email'=>'ایمیل','website'=>'وب‌سایت'];
  $logo_id=absint($school['logo_attachment_id']??0);
  $logo=$logo_id?wp_get_attachment_image_url($logo_id,'thumbnail'):'';
  $notice=isset($_GET['myschool_notice'])?sanitize_key(wp_unslash($_GET['myschool_notice'])):'';
  ?>
  <div class="wrap myschool-admin" dir="rtl"><h1>مشخصات مدرسه</h1>
  <?php if ($notice==='saved') echo '<div class="notice notice-success is-dismissible"><p>اطلاعات مدرسه ذخیره شد.</p></div>';
  elseif ($notice==='invalid') echo '<div class="notice notice-error"><p>نام مدرسه الزامی است و ایمیل باید معتبر باشد.</p></div>';
  elseif ($notice==='error') echo '<div class="notice notice-error"><p>خطا در ذخیره اطلاعات. لطفاً مجدداً تلاش کنید.</p></div>'; ?>
  <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="myschool-panel">
  <input type="hidden" name="action" value="myschool_save_school">
  <?php wp_nonce_field('myschool_save_school','myschool_school_nonce'); ?>
  <h2>هویت و اطلاعات تماس</h2><div class="myschool-form-grid">
  <?php foreach($fields as $key=>$label): ?>
  <label class="myschool-field"><span><?php echo esc_html($label); ?><?php if ($key==='name') echo ' *'; ?></span><input <?php echo $key==='name'?'required ':''; ?>type="<?php echo $key==='email'?'email':($key==='website'?'url':'text'); ?>" name="school[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($school[$key]??''); ?>"></label>
  <?php endforeach; ?>
  <label class="myschool-field myschool-wide"><span>نشانی مدرسه</span><textarea rows="3" name="school[address]"><?php echo esc_textarea($school['address']??''); ?></textarea></label>
  <div class="myschool-field myschool-wide"><span>لوگوی مدرسه</span><input id="myschool-logo-id" name="school[logo_attachment_id]" type="hidden" value="<?php echo esc_attr((string)$logo_id); ?>"><img id="myschool-logo-preview" src="<?php echo esc_url($logo); ?>" alt="" style="max-width:140px;max-height:140px;<?php echo $logo?'':'display:none;'; ?>"><div><button type="button" class="button" id="myschool-logo-choose">انتخاب از رسانه وردپرس</button> <button type="button" class="button" id="myschool-logo-remove">حذف لوگو</button></div></div>
  </div><?php submit_button('ذخیره مشخصات مدرسه'); ?></form></div>
  <?php
 }
}
