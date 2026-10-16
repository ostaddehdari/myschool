<?php
namespace MySchool\Modules\Shifts;
use MySchool\Contracts\ModuleInterface;
use MySchool\Core\AuditLog;
if(!defined('ABSPATH'))exit;
final class ShiftsModule implements ModuleInterface {
 private const DAYS=['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
 public function id():string{return 'shifts';}
 public function register():void{add_action('admin_menu',[$this,'menu']);add_action('admin_enqueue_scripts',[$this,'assets']);add_action('admin_post_myschool_shift_save',[$this,'save']);add_action('admin_post_myschool_shift_status',[$this,'status']);}
 private function table():string{global $wpdb;return $wpdb->prefix.'myschool_shifts';}
 private function school():int{global $wpdb;return (int)$wpdb->get_var('SELECT id FROM '.$wpdb->prefix.'myschool_schools ORDER BY id ASC LIMIT 1');}
 private function guard(string $action):void{if(!current_user_can('myschool_manage_school'))wp_die('Access denied','',['response'=>403]);check_admin_referer($action,'myschool_nonce');}
 private function redirect(string $notice):void{wp_safe_redirect(add_query_arg(['page'=>'myschool-shifts','notice'=>$notice],admin_url('admin.php')));exit;}
 public function menu():void{add_submenu_page('myschool','شیفت‌ها و زنگ‌ها','شیفت‌ها و زنگ‌ها','myschool_manage_school','myschool-shifts',[$this,'render']);}
 public function assets(string $hook):void{if($hook!=='myschool_page_myschool-shifts')return;wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',[],MYSCHOOL_VERSION);wp_enqueue_style('myschool-shifts',MYSCHOOL_URL.'assets/css/shifts.css',[],MYSCHOOL_VERSION);wp_enqueue_script('myschool-shifts',MYSCHOOL_URL.'assets/js/shifts.js',[],MYSCHOOL_VERSION,true);}
 private function conflicting(array $data,int $school,int $except=0):bool{
  global $wpdb;$table=$this->table();$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE school_id=%d AND status='active' AND id<>%d",$school,$except),ARRAY_A);
  foreach($rows as $r)if(ShiftValidator::overlaps($data,['days'=>explode(',',$r['active_days']),'start'=>substr($r['starts_at'],0,5),'end'=>substr($r['ends_at'],0,5)]))return true;return false;
 }
 public function save():void{
  $this->guard('myschool_shift_save');global $wpdb;$school=$this->school();if(!$school)$this->redirect('no-school');$table=$this->table();$pTable=$wpdb->prefix.'myschool_shift_periods';
  $raw=isset($_POST['shift'])&&is_array($_POST['shift'])?wp_unslash($_POST['shift']):[];$id=absint($raw['id']??0);
  $prev=null;if($id){$prev=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND school_id=%d",$id,$school),ARRAY_A);if(!$prev)$this->redirect('invalid');}
  $periods=[];foreach((array)($raw['periods']??[]) as $p){if(!is_array($p))continue;$title=sanitize_text_field($p['title']??'');$start=sanitize_text_field($p['start']??'');$end=sanitize_text_field($p['end']??'');if($title===''&&$start===''&&$end==='')continue;$periods[]=['title'=>$title,'type'=>sanitize_key($p['type']??'lesson'),'start'=>$start,'end'=>$end];}
  try{$data=ShiftValidator::validate(sanitize_text_field($raw['name']??''),sanitize_text_field($raw['start']??''),sanitize_text_field($raw['end']??''),(array)($raw['days']??[]),$periods);}catch(\InvalidArgumentException $e){$this->redirect('invalid');}
  $status=$prev?$prev['status']:'active';if($status==='active'&&$this->conflicting($data,$school,$id))$this->redirect('overlap');
  $record=['name'=>$data['name'],'starts_at'=>$data['start'].':00','ends_at'=>$data['end'].':00','active_days'=>implode(',',$data['days']),'status'=>$status];
  $wpdb->query('START TRANSACTION');try{
   if($id){if($wpdb->update($table,$record,['id'=>$id,'school_id'=>$school])===false)throw new \RuntimeException('Could not update shift');}
   else{$record['school_id']=$school;if($wpdb->insert($table,$record)===false)throw new \RuntimeException('Could not insert shift');$id=(int)$wpdb->insert_id;}
   if($wpdb->delete($pTable,['shift_id'=>$id])===false)throw new \RuntimeException('Could not update periods');
   foreach($data['periods'] as $p)if($wpdb->insert($pTable,['shift_id'=>$id,'title'=>$p['title'],'period_type'=>$p['type'],'starts_at'=>$p['start'].':00','ends_at'=>$p['end'].':00','sort_order'=>$p['order']])===false)throw new \RuntimeException('Could not add period');
   $wpdb->query('COMMIT');
  }catch(\Throwable $e){$wpdb->query('ROLLBACK');$this->redirect('error');}
  AuditLog::record('shift.saved','shift',$id,['source'=>'admin']);$this->redirect('saved');
 }
 public function status():void{
  $this->guard('myschool_shift_status');global $wpdb;$id=absint($_POST['id']??0);$new=sanitize_key($_POST['status']??'');if(!$id||!in_array($new,['active','inactive'],true))$this->redirect('invalid');
  $school=$this->school();$table=$this->table();$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND school_id=%d",$id,$school),ARRAY_A);if(!$row)$this->redirect('invalid');
  if($new==='active'&&$this->conflicting(['days'=>explode(',',$row['active_days']),'start'=>substr($row['starts_at'],0,5),'end'=>substr($row['ends_at'],0,5)],$school,$id))$this->redirect('overlap');
  if($wpdb->update($table,['status'=>$new],['id'=>$id,'school_id'=>$school])===false)$this->redirect('error');
  AuditLog::record('shift.status_changed','shift',$id,['old_status'=>$row['status'],'new_status'=>$new]);$this->redirect('saved');
 }
 private function periodRow(string $key,array $p):void{
  echo '<div class="myschool-period-row"><input required name="shift[periods]['.esc_attr($key).'][title]" placeholder="نام زنگ" value="'.esc_attr($p['title']??'').'"><select name="shift[periods]['.esc_attr($key).'][type]">';
  foreach(['lesson'=>'کلاس','break'=>'استراحت','assembly'=>'مراسم','other'=>'سایر'] as $value=>$name)echo '<option value="'.$value.'" '.selected($p['period_type']??'lesson',$value,false).'>'.$name.'</option>';
  echo '</select><input required type="time" name="shift[periods]['.esc_attr($key).'][start]" value="'.esc_attr(substr($p['starts_at']??'',0,5)).'"><input required type="time" name="shift[periods]['.esc_attr($key).'][end]" value="'.esc_attr(substr($p['ends_at']??'',0,5)).'"><button type="button" class="button myschool-remove-period">حذف</button></div>';
 }
 public function render():void{
  if(!current_user_can('myschool_manage_school'))wp_die('Access denied');global $wpdb;$school=$this->school();$table=$this->table();$pTable=$wpdb->prefix.'myschool_shift_periods';
  $rows=$school?$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE school_id=%d ORDER BY id DESC",$school),ARRAY_A):[];$edit=absint($_GET['edit']??0);$current=null;foreach($rows as $r)if((int)$r['id']===$edit)$current=$r;
  $periods=$current?$wpdb->get_results($wpdb->prepare("SELECT * FROM {$pTable} WHERE shift_id=%d ORDER BY starts_at ASC",$edit),ARRAY_A):[];$notice=sanitize_key($_GET['notice']??'');
  $messages=['saved'=>'ذخیره شد','invalid'=>'اطلاعات نامعتبر است','error'=>'خطا هنگام ذخیره','overlap'=>'شیفت با شیفت فعال دیگری تداخل دارد','no-school'=>'ابتدا مشخصات مدرسه را ثبت کنید'];
  echo '<div class="wrap myschool-admin" dir="rtl"><h1>شیفت‌ها و زنگ‌ها</h1>';if(isset($messages[$notice]))echo '<div class="notice notice-info"><p>'.esc_html($messages[$notice]).'</p></div>';if(!$school){echo '<p>ابتدا مشخصات مدرسه را ثبت کنید.</p></div>';return;}
  echo '<div class="myschool-shifts-grid"><section class="myschool-panel"><h2>'.($current?'ویرایش':'افزودن').' شیفت</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('myschool_shift_save','myschool_nonce');
  echo '<input type="hidden" name="action" value="myschool_shift_save"><input type="hidden" name="shift[id]" value="'.esc_attr((string)($current['id']??0)).'"><div class="myschool-shift-main"><label>نام شیفت<input name="shift[name]" required maxlength="120" value="'.esc_attr($current['name']??'').'"></label>';
  foreach(['start'=>'شروع','end'=>'پایان'] as $key=>$name){$value=substr($current[$key==='start'?'starts_at':'ends_at']??($key==='start'?'07:30':'12:30'),0,5);echo '<label>'.esc_html($name).'<input type="time" required name="shift['.$key.']" value="'.esc_attr($value).'"></label>';}
  echo '</div><h3>روزهای فعالیت</h3><div class="myschool-days">';$days=$current?array_map('intval',explode(',',$current['active_days'])):[0,1,2,3,4];foreach(self::DAYS as $day=>$name)echo '<label><input name="shift[days][]" type="checkbox" value="'.$day.'" '.checked(in_array($day,$days,true),true,false).'>'.esc_html($name).'</label>';
  echo '</div><div class="myschool-period-title"><h3>زنگ‌ها و استراحت</h3><button id="myschool-add-period" class="button" type="button">+ زنگ جدید</button></div><div id="myschool-periods">';foreach($periods as $i=>$p)$this->periodRow((string)$i,$p);echo '</div>';submit_button('ذخیره شیفت');echo '</form></section><section class="myschool-panel"><h2>شیفت‌های ثبت‌شده</h2><div class="myschool-table-scroll"><table class="widefat striped"><thead><tr><th>نام</th><th>ساعت</th><th>روزها</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
  foreach($rows as $r){$id=(int)$r['id'];$active=$r['status']==='active';$other=$active?'inactive':'active';$names=[];foreach(explode(',',$r['active_days']) as $d)if(isset(self::DAYS[(int)$d]))$names[]=self::DAYS[(int)$d];
   echo '<tr><td>'.esc_html($r['name']).'</td><td>'.esc_html(substr($r['starts_at'],0,5).'–'.substr($r['ends_at'],0,5)).'</td><td>'.esc_html(implode('، ',$names)).'</td><td>'.($active?'فعال':'غیرفعال').'</td><td><a class="button" href="'.esc_url(add_query_arg(['page'=>'myschool-shifts','edit'=>$id],admin_url('admin.php'))).'">ویرایش</a> <form class="myschool-inline" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('myschool_shift_status','myschool_nonce');echo '<input name="action" value="myschool_shift_status" type="hidden"><input name="id" value="'.$id.'" type="hidden"><input name="status" value="'.$other.'" type="hidden"><button class="button">'.($active?'غیرفعال‌سازی':'فعال‌سازی').'</button></form></td></tr>';
  }if(!$rows)echo '<tr><td colspan="5">شیفتی ثبت نشده است.</td></tr>';echo '</tbody></table></div></section></div><template id="myschool-period-template">';$this->periodRow('__INDEX__',[]);echo '</template></div>';
 }
}