<?php
namespace MySchool\Modules\AcademicYears;
use MySchool\Contracts\ModuleInterface;
use MySchool\Support\Jalali;
use MySchool\Core\AuditLog;
if(!defined('ABSPATH'))exit;
final class AcademicYearsModule implements ModuleInterface {
 public function id():string{return 'academic-years';}
 public function register():void{add_action('admin_menu',[$this,'menu']);add_action('admin_enqueue_scripts',[$this,'assets']);add_action('admin_post_myschool_year_save',[$this,'save']);add_action('admin_post_myschool_year_status',[$this,'status']);}
 private function table():string{global $wpdb;return $wpdb->prefix.'myschool_academic_years';}
 private function school():int{global $wpdb;return (int)$wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'myschool_schools ORDER BY id ASC LIMIT 1');}
 private function go(string $notice):void{wp_safe_redirect(add_query_arg(['page'=>'myschool-years','notice'=>$notice],admin_url('admin.php')));exit;}
 private function guard(string $action):void{if(!current_user_can('myschool_manage_academic_years'))wp_die('Forbidden','',['response'=>403]);check_admin_referer($action,'myschool_nonce');}
 public function menu():void{add_submenu_page('myschool','سال‌های تحصیلی','سال‌های تحصیلی','myschool_manage_academic_years','myschool-years',[$this,'render']);}
 public function assets(string $hook):void{if($hook!=='myschool_page_myschool-years')return;wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',[],MYSCHOOL_VERSION);wp_enqueue_style('myschool-year-calendar',MYSCHOOL_URL.'assets/css/jalali-picker.css',[],MYSCHOOL_VERSION);wp_enqueue_script('myschool-year-calendar',MYSCHOOL_URL.'assets/js/jalali-picker.js',[],MYSCHOOL_VERSION,true);}
 public static function parseJalali(string $date):string{
  $date=strtr(trim($date),['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  if(!preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/D',$date,$m))throw new \InvalidArgumentException('Invalid Jalali date');
  return sprintf('%04d-%02d-%02d',...Jalali::toGregorian((int)$m[1],(int)$m[2],(int)$m[3]));
 }
 public function save():void{
  $this->guard('myschool_year_save');global $wpdb;$school=$this->school();if(!$school)$this->go('no-school');
  $id=absint($_POST['id']??0);$table=$this->table();
  if($id&&!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id=%d AND school_id=%d",$id,$school)))$this->go('invalid');
  $title=sanitize_text_field(wp_unslash($_POST['title']??''));
  if(!preg_match('/^(\d{4})-(\d{4})$/D',$title,$m)||(int)$m[2] !== (int)$m[1]+1)$this->go('invalid');
  try{$start=self::parseJalali(sanitize_text_field(wp_unslash($_POST['starts_on']??'')));$end=self::parseJalali(sanitize_text_field(wp_unslash($_POST['ends_on']??'')));}catch(\InvalidArgumentException $e){$this->go('invalid');}
  if($start>=$end)$this->go('invalid');
  $sy=Jalali::toJalali(...array_map('intval',explode('-',$start)))[0];
  $ey=Jalali::toJalali(...array_map('intval',explode('-',$end)))[0];
  if($sy!==(int)$m[1]||$ey!==(int)$m[2])$this->go('invalid');
  if($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE school_id=%d AND title=%s AND id<>%d LIMIT 1",$school,$title,$id)))$this->go('duplicate');
  $data=['title'=>$title,'starts_on'=>$start,'ends_on'=>$end];
  if($id)$result=$wpdb->update($table,$data,['id'=>$id,'school_id'=>$school]);
  else{$data['school_id']=$school;$data['status']='draft';$result=$wpdb->insert($table,$data);$id=(int)$wpdb->insert_id;}
  if($result===false)$this->go('error');
  AuditLog::record('academic_year.saved','academic_year',$id,['source'=>'admin']);$this->go('saved');
 }
 public function status():void{
  $this->guard('myschool_year_status');global $wpdb;
  $id=absint($_POST['id']??0);$new=sanitize_key($_POST['status']??'');$school=$this->school();$table=$this->table();
  if(!$id||!$school||!in_array($new,['active','archived','draft'],true))$this->go('invalid');
  $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND school_id=%d",$id,$school),ARRAY_A);
  if(!$row)$this->go('invalid');
  if($new==='active'){
   $wpdb->query('START TRANSACTION');
   try{
    $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}myschool_schools WHERE id=%d FOR UPDATE",$school));
    $r=$wpdb->query($wpdb->prepare("UPDATE {$table} SET status='archived' WHERE school_id=%d AND status='active' AND id<>%d",$school,$id));
    if($r===false)throw new \RuntimeException('Previous active year update failed');
    if($wpdb->update($table,['status'=>'active'],['id'=>$id,'school_id'=>$school])===false)throw new \RuntimeException('Activation failed');
    $wpdb->query('COMMIT');
   }catch(\Throwable $e){$wpdb->query('ROLLBACK');$this->go('error');}
  } elseif($wpdb->update($table,['status'=>$new],['id'=>$id,'school_id'=>$school])===false)$this->go('error');
  AuditLog::record('academic_year.status_changed','academic_year',$id,['old_status'=>$row['status'],'new_status'=>$new]);$this->go('saved');
 }
 public function render():void{
  if(!current_user_can('myschool_manage_academic_years'))wp_die('Forbidden');
  global $wpdb;$school=$this->school();$table=$this->table();
  $rows=$school?$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE school_id=%d ORDER BY title DESC",$school),ARRAY_A):[];
  $edit=absint($_GET['edit']??0);$current=null;foreach($rows as $row)if((int)$row['id']===$edit)$current=$row;
  $notice=sanitize_key($_GET['notice']??'');
  echo '<div class="wrap myschool-admin" dir="rtl"><h1>مدیریت سال‌های تحصیلی</h1>';
  if(!$school){echo '<div class="notice notice-warning"><p>ابتدا مشخصات مدرسه را ثبت کنید.</p></div></div>';return;}
  $messages=['saved'=>'تغییرات ذخیره شد.','invalid'=>'عنوان یا تاریخ نامعتبر است.','duplicate'=>'سال تحصیلی تکراری است.','error'=>'خطا در ذخیره‌سازی.'];
  if(isset($messages[$notice]))echo '<div class="notice notice-info"><p>'.esc_html($messages[$notice]).'</p></div>';
  echo '<div class="myschool-year-layout"><section class="myschool-panel"><h2>'.($current?'ویرایش':'افزودن').' سال تحصیلی</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
  wp_nonce_field('myschool_year_save','myschool_nonce');
  echo '<input type="hidden" name="action" value="myschool_year_save"><input type="hidden" name="id" value="'.esc_attr((string)($current['id']??0)).'">';
  echo '<label class="myschool-field"><span>سال تحصیلی</span><input name="title" pattern="[0-9]{4}-[0-9]{4}" placeholder="1405-1406" required value="'.esc_attr($current['title']??'').'"></label>';
  foreach(['starts_on'=>'تاریخ شروع','ends_on'=>'تاریخ پایان'] as $key=>$label){$value=!empty($current[$key])?Jalali::format($current[$key]):'';echo '<label class="myschool-field"><span>'.esc_html($label).'</span><input class="myschool-jalali-date" name="'.esc_attr($key).'" type="text" inputmode="none" autocomplete="off" placeholder="1405/07/01" required value="'.esc_attr($value).'"></label>';}
  submit_button($current?'ذخیره تغییرات':'افزودن سال');echo '</form></section><section class="myschool-panel"><h2>سال‌های ثبت‌شده</h2><div class="myschool-table-wrap"><table class="widefat striped"><thead><tr><th>سال</th><th>شروع</th><th>پایان</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
  foreach($rows as $row){$id=(int)$row['id'];$status=$row['status'];$label=['active'=>'فعال','archived'=>'آرشیو','draft'=>'پیش‌نویس'][$status]??$status;
   echo '<tr><td>'.esc_html($row['title']).'</td><td>'.esc_html(Jalali::format($row['starts_on'])).'</td><td>'.esc_html(Jalali::format($row['ends_on'])).'</td><td>'.esc_html($label).'</td><td><a href="'.esc_url(add_query_arg(['page'=>'myschool-years','edit'=>$id],admin_url('admin.php'))).'">ویرایش</a> ';
   foreach(['active'=>'فعال‌سازی','archived'=>'آرشیو'] as $s=>$action){if($s===$status)continue;echo '<form class="myschool-inline" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('myschool_year_status','myschool_nonce');echo '<input type="hidden" name="action" value="myschool_year_status"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="status" value="'.esc_attr($s).'"><button type="submit" class="button button-small">'.esc_html($action).'</button></form>';}
   echo '</td></tr>';
  }
  if(!$rows)echo '<tr><td colspan="5">هنوز سال تحصیلی ثبت نشده است.</td></tr>';
  echo '</tbody></table></div></section></div></div>';
 }
}
