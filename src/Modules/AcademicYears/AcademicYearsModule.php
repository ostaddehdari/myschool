<?php
namespace MySchool\Modules\AcademicYears;
use MySchool\Contracts\ModuleInterface;
use MySchool\Support\Jalali;
if(!defined('ABSPATH'))exit;
final class AcademicYearsModule implements ModuleInterface {
 public function id():string{return 'academic-years';}
 public function register():void{
  add_action('admin_menu',[$this,'menu']);add_action('admin_enqueue_scripts',[$this,'assets']);
  add_action('admin_post_myschool_year_save',[$this,'save']);
  add_action('admin_post_myschool_year_status',[$this,'status']);
 }
 private function table():string{global $wpdb;return $wpdb->prefix.'myschool_academic_years';}
 private function school():int{global $wpdb;return (int)$wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'myschool_schools ORDER BY id ASC LIMIT 1');}
 private function gate(string $action):void{if(!current_user_can('myschool_manage_academic_years'))wp_die('Forbidden','',['response'=>403]);check_admin_referer($action,'myschool_nonce');}
 private function go(string $notice):void{wp_safe_redirect(add_query_arg(['page'=>'myschool-years','notice'=>$notice],admin_url('admin.php')));exit;}
 public function menu():void{add_submenu_page('myschool','سال‌های تحصیلی','سال‌های تحصیلی','myschool_manage_academic_years','myschool-years',[$this,'render']);}
 public function assets(string $hook):void{if($hook!=='myschool_page_myschool-years')return;wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',[],MYSCHOOL_VERSION);wp_enqueue_style('myschool-years',MYSCHOOL_URL.'assets/css/jalali-picker.css',[],MYSCHOOL_VERSION);wp_enqueue_script('myschool-years',MYSCHOOL_URL.'assets/js/jalali-picker.js',[],MYSCHOOL_VERSION,true);}
 public static function parseJalali(string $date):string{
  $date=strtr(trim($date),array_combine(preg_split('//u','۰۱۲۳۴۵۶۷۸۹',-1,PREG_SPLIT_NO_EMPTY),range(0,9)));
  if(!preg_match('/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/D',$date,$m))throw new \InvalidArgumentException('Invalid Jalali date');
  return sprintf('%04d-%02d-%02d',...Jalali::toGregorian((int)$m[1],(int)$m[2],(int)$m[3]));
 }
 public function save():void{
  $this->gate('myschool_year_save');global $wpdb;$school=$this->school();if(!$school)$this->go('no-school');
  $table=$this->table();$id=absint($_POST['id']??0);$title=sanitize_text_field(wp_unslash($_POST['title']??''));
  if(!preg_match('/^(\d{4})-(\d{4})$/D',$title,$m)||(int)$m[2] !==(int)$m[1]+1)$this->go('invalid');
  if($id&&!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id=%d AND school_id=%d",$id,$school)))$this->go('invalid');
  try{$start=self::parseJalali(sanitize_text_field(wp_unslash($_POST['starts_on']??'')));$end=self::parseJalali(sanitize_text_field(wp_unslash($_POST['ends_on']??'')));}catch(\InvalidArgumentException $e){$this->go('invalid');}
  if($start>=$end||Jalali::toJalali(...array_map('intval',explode('-',$start)))[0]!==(int)$m[1]||Jalali::toJalali(...array_map('intval',explode('-',$end)))[0]!==(int)$m[2])$this->go('invalid');
  if($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE school_id=%d AND title=%s AND id<>%d",$school,$title,$id)))$this->go('duplicate');
  $data=['title'=>$title,'starts_on'=>$start,'ends_on'=>$end];
  if($id)$ok=$wpdb->update($table,$data,['id'=>$id,'school_id'=>$school]);else{$data['school_id']=$school;$data['status']='draft';$ok=$wpdb->insert($table,$data);$id=(int)$wpdb->insert_id;}
  if($ok===false)$this->go('error');$this->go('saved');
 }
 public function status():void{
  $this->gate('myschool_year_status');global $wpdb;$school=$this->school();$table=$this->table();$id=absint($_POST['id']??0);$new=sanitize_key($_POST['status']??'');
  if(!in_array($new,['active','archived','draft'],true)||!$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id=%d AND school_id=%d",$id,$school)))$this->go('invalid');
  $wpdb->query('START TRANSACTION');
  try{
   if($new==='active'){$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}myschool_schools WHERE id=%d FOR UPDATE",$school));if($wpdb->query($wpdb->prepare("UPDATE {$table} SET status='archived' WHERE school_id=%d AND status='active' AND id<>%d",$school,$id))===false)throw new \RuntimeException('status');}
   if($wpdb->update($table,['status'=>$new],['id'=>$id,'school_id'=>$school])===false)throw new \RuntimeException('status');
   $wpdb->query('COMMIT');
  }catch(\Throwable $e){$wpdb->query('ROLLBACK');$this->go('error');}
  $this->go('saved');
 }
 public function render():void{
  if(!current_user_can('myschool_manage_academic_years'))wp_die('Forbidden');
  global $wpdb;$school=$this->school();echo '<div class="wrap myschool-admin" dir="rtl"><h1>سال‌های تحصیلی</h1>';
  if(!$school){echo '<p>ابتدا مدرسه را ثبت کنید.</p></div>';return;}
  $table=$this->table();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE school_id=%d ORDER BY title DESC",$school),ARRAY_A);
  $edit=absint($_GET['edit']??0);$current=null;foreach($rows as $r)if((int)$r['id']===$edit)$current=$r;
  echo '<div class="myschool-year-layout"><section class="myschool-panel"><h2>ثبت یا ویرایش سال</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('myschool_year_save','myschool_nonce');
  echo '<input type="hidden" name="action" value="myschool_year_save"><input type="hidden" name="id" value="'.(int)($current['id']??0).'"><label>سال تحصیلی <input required pattern="[0-9]{4}-[0-9]{4}" name="title" value="'.esc_attr($current['title']??'').'" placeholder="1405-1406"></label>';
  foreach(['starts_on'=>'تاریخ شروع','ends_on'=>'تاریخ پایان'] as $key=>$label)echo '<label class="myschool-field">'.esc_html($label).'<input required class="myschool-jalali-date" name="'.$key.'" value="'.esc_attr(!empty($current[$key])?Jalali::format($current[$key]):'').'" placeholder="1405/07/01"></label>';
  submit_button('ذخیره');echo '</form></section><section class="myschool-panel"><table class="widefat striped"><thead><tr><th>سال</th><th>شروع</th><th>پایان</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
  foreach($rows as $r){$id=(int)$r['id'];echo '<tr><td>'.esc_html($r['title']).'</td><td>'.esc_html(Jalali::format($r['starts_on'])).'</td><td>'.esc_html(Jalali::format($r['ends_on'])).'</td><td>'.esc_html($r['status']).'</td><td><a class="button" href="'.esc_url(add_query_arg(['page'=>'myschool-years','edit'=>$id],admin_url('admin.php'))).'">ویرایش</a>';
   foreach(['active'=>'فعال','archived'=>'آرشیو'] as $status=>$label)if($status!==$r['status']){echo '<form method="post" class="myschool-inline" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('myschool_year_status','myschool_nonce');echo '<input type="hidden" name="action" value="myschool_year_status"><input type="hidden" name="id" value="'.$id.'"><input type="hidden" name="status" value="'.$status.'"><button class="button">'.esc_html($label).'</button></form>';}echo '</td></tr>';}
  echo '</tbody></table></section></div></div>';
 }
}
