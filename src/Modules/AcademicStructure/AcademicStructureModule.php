<?php
namespace MySchool\Modules\AcademicStructure;
use MySchool\Contracts\ModuleInterface;
if(!defined('ABSPATH'))exit;
final class AcademicStructureModule implements ModuleInterface {
 public function id():string{return 'academic-structure';}
 public function register():void {
  add_action('admin_menu',[$this,'menu']);
  add_action('admin_enqueue_scripts',[$this,'assets']);
  add_action('admin_post_myschool_academic_save',[$this,'save']);
  add_action('admin_post_myschool_academic_status',[$this,'status']);
 }
 private function table(string $kind):string{global $wpdb;return $wpdb->prefix.'myschool_'.$kind;}
 private function school():int{global $wpdb;return (int)$wpdb->get_var('SELECT id FROM '.$this->table('schools').' ORDER BY id ASC LIMIT 1');}
 private function types():array{return ['sections'=>'مقاطع تحصیلی','grade_levels'=>'پایه‌ها','classes'=>'کلاس‌ها'];}
 private function gate(string $nonce):void{if(!current_user_can('myschool_manage_classes'))wp_die('دسترسی مجاز نیست','',['response'=>403]);check_admin_referer($nonce,'myschool_nonce');}
 private function go(string $type,string $notice):void{wp_safe_redirect(add_query_arg(['page'=>'myschool-academic','section'=>$type,'notice'=>$notice],admin_url('admin.php')));exit;}
 private function exists(string $kind,int $id,int $school,string $extra=''):bool{global $wpdb;if(!$id)return false;$table=$this->table($kind);return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE id=%d AND school_id=%d {$extra}",$id,$school));}
 public function menu():void{add_submenu_page('myschool','مقاطع و کلاس‌ها','مقاطع و کلاس‌ها','myschool_manage_classes','myschool-academic',[$this,'render']);}
 public function assets(string $hook):void{if($hook!=='myschool_page_myschool-academic')return;wp_enqueue_style('myschool-admin',MYSCHOOL_URL.'assets/css/admin.css',[],MYSCHOOL_VERSION);wp_enqueue_style('myschool-academic',MYSCHOOL_URL.'assets/css/academic.css',[],MYSCHOOL_VERSION);}
 public function save():void{
  $this->gate('myschool_academic_save');global $wpdb;$type=sanitize_key($_POST['entity']??'');if(!isset($this->types()[$type]))$this->go('classes','invalid');$school=$this->school();if(!$school)$this->go($type,'school');
  $id=absint($_POST['id']??0);if($id&&!$this->exists($type,$id,$school))$this->go($type,'invalid');
  $title=sanitize_text_field(wp_unslash($_POST['title']??''));if($title===''||mb_strlen($title)>120)$this->go($type,'invalid');
  $data=['title'=>$title];$table=$this->table($type);
  if($type==='grade_levels'){
   $section=absint($_POST['section_id']??0);if(!$this->exists('sections',$section,$school,"AND status='active'"))$this->go($type,'relation');
   $data['section_id']=$section;$data['sort_order']=max(0,min(999,(int)($_POST['sort_order']??0)));
  }
  if($type==='classes'){
   $year=absint($_POST['academic_year_id']??0);$grade=absint($_POST['grade_level_id']??0);$shift=absint($_POST['shift_id']??0);
   if(!$this->exists('academic_years',$year,$school,"AND status<>'archived'")||!$this->exists('grade_levels',$grade,$school,"AND status='active'")||($shift&&!$this->exists('shifts',$shift,$school,"AND status='active'")))$this->go($type,'relation');
   $capacity=filter_var($_POST['capacity']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>2000]]);
   if($capacity===false)$this->go($type,'capacity');
   if($id){$enroll=$this->table('enrollments');$count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$enroll} WHERE class_id=%d AND status='active'",$id));if($capacity<$count)$this->go($type,'capacity');}
   $data+=['academic_year_id'=>$year,'grade_level_id'=>$grade,'shift_id'=>$shift?:null,'capacity'=>$capacity];
   if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE school_id=%d AND academic_year_id=%d AND grade_level_id=%d AND title=%s AND id<>%d",$school,$year,$grade,$title,$id)))$this->go($type,'duplicate');
  }
  if($id)$ok=$wpdb->update($table,$data,['id'=>$id,'school_id'=>$school]);
  else{$data['school_id']=$school;$data['status']='active';$ok=$wpdb->insert($table,$data);$id=(int)$wpdb->insert_id;}
  if($ok===false)$this->go($type,'error');
  try{\MySchool\Core\AuditLog::record('academic.saved',$type,$id,['source'=>'admin']);}catch(\Throwable $e){}
  $this->go($type,'saved');
 }
 public function status():void{
  $this->gate('myschool_academic_status');global $wpdb;$kind=sanitize_key($_POST['entity']??'');$id=absint($_POST['id']??0);$new=sanitize_key($_POST['status']??'');$school=$this->school();
  if(!isset($this->types()[$kind])||!in_array($new,['active','inactive'],true)||!$this->exists($kind,$id,$school))$this->go('classes','invalid');
  $table=$this->table($kind);
  if($new==='inactive'){
   $child=$kind==='sections'?'grade_levels':($kind==='grade_levels'?'classes':'enrollments');
   $key=$kind==='sections'?'section_id':($kind==='grade_levels'?'grade_level_id':'class_id');
   $ct=$this->table($child);if((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$ct} WHERE {$key}=%d AND status='active'",$id)))$this->go($kind,'in-use');
  }
  if($wpdb->update($table,['status'=>$new],['id'=>$id,'school_id'=>$school])===false)$this->go($kind,'error');
  $this->go($kind,'saved');
 }
 public function render():void{
  if(!current_user_can('myschool_manage_classes'))wp_die('Access denied');
  global $wpdb;$school=$this->school();$types=$this->types();$kind=sanitize_key($_GET['section']??'classes');if(!isset($types[$kind]))$kind='classes';
  echo '<div class="wrap myschool-admin" dir="rtl"><h1>مقاطع، پایه‌ها و کلاس‌ها</h1><div class="myschool-tabs">';
  foreach($types as $key=>$name)echo '<a class="'.($key===$kind?'active':'').'" href="'.esc_url(add_query_arg(['page'=>'myschool-academic','section'=>$key],admin_url('admin.php'))).'">'.esc_html($name).'</a>';echo '</div>';
  if(!$school){echo '<p>ابتدا مشخصات مدرسه را ثبت کنید.</p></div>';return;}
  $edit=absint($_GET['edit']??0);$table=$this->table($kind);
  $current=$edit?$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d AND school_id=%d",$edit,$school),ARRAY_A):null;
  $notice=sanitize_key($_GET['notice']??'');if($notice)echo '<div class="notice notice-info"><p>'.esc_html($notice).'</p></div>';
  echo '<div class="myschool-academic-layout"><section class="myschool-panel"><h2>'.($current?'ویرایش':'افزودن').' '.esc_html($types[$kind]).'</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'">';
  wp_nonce_field('myschool_academic_save','myschool_nonce');echo '<input type="hidden" name="action" value="myschool_academic_save"><input type="hidden" name="entity" value="'.esc_attr($kind).'"><input type="hidden" name="id" value="'.(int)($current['id']??0).'">';
  echo '<label class="myschool-field"><span>عنوان</span><input name="title" required maxlength="120" value="'.esc_attr($current['title']??'').'"></label>';
  if($kind==='grade_levels'){$this->select('section_id','مقطع',$this->options('sections',$school),(int)($current['section_id']??0));echo '<label class="myschool-field"><span>ترتیب</span><input type="number" name="sort_order" min="0" value="'.(int)($current['sort_order']??0).'"></label>';}
  if($kind==='classes'){$this->select('academic_year_id','سال تحصیلی',$this->options('academic_years',$school),(int)($current['academic_year_id']??0));$this->select('grade_level_id','پایه',$this->options('grade_levels',$school),(int)($current['grade_level_id']??0));$this->select('shift_id','شیفت',$this->options('shifts',$school),(int)($current['shift_id']??0),false);echo '<label class="myschool-field"><span>ظرفیت</span><input type="number" min="1" max="2000" required name="capacity" value="'.(int)($current['capacity']??30).'"></label>';}
  submit_button('ذخیره');echo '</form></section><section class="myschool-panel"><h2>فهرست</h2><div class="myschool-table-scroll"><table class="widefat striped"><thead><tr><th>عنوان</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>';
  $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE school_id=%d ORDER BY id DESC",$school),ARRAY_A);
  foreach($rows as $r){$rid=(int)$r['id'];$next=$r['status']==='active'?'inactive':'active';echo '<tr><td>'.esc_html($r['title']).'</td><td>'.esc_html($r['status']).'</td><td><a class="button" href="'.esc_url(add_query_arg(['page'=>'myschool-academic','section'=>$kind,'edit'=>$rid],admin_url('admin.php'))).'">ویرایش</a><form class="myschool-inline" method="post" action="'.esc_url(admin_url('admin-post.php')).'">';wp_nonce_field('myschool_academic_status','myschool_nonce');echo '<input type="hidden" name="action" value="myschool_academic_status"><input type="hidden" name="entity" value="'.esc_attr($kind).'"><input type="hidden" name="id" value="'.$rid.'"><input type="hidden" name="status" value="'.$next.'"><button type="submit" class="button">'.($next==='active'?'فعال‌سازی':'غیرفعال‌سازی').'</button></form></td></tr>';}
  if(!$rows)echo '<tr><td colspan="3">موردی ثبت نشده است.</td></tr>';echo '</tbody></table></div></section></div></div>';
 }
 private function options(string $kind,int $school):array{
  global $wpdb;$table=$this->table($kind);$condition=$kind==='academic_years'?"status<>'archived'":"status='active'";
  $column=$kind==='shifts'?'name':'title';return $wpdb->get_results($wpdb->prepare("SELECT id, {$column} AS title FROM {$table} WHERE school_id=%d AND {$condition} ORDER BY id DESC",$school),ARRAY_A);
 }
 private function select(string $name,string $label,array $items,int $value,bool $required=true):void{
  echo '<label class="myschool-field"><span>'.esc_html($label).'</span><select name="'.esc_attr($name).'" '.($required?'required':'').'><option value="">انتخاب کنید</option>';
  foreach($items as $x){$display=$x['title']??$x['name'];echo '<option value="'.(int)$x['id'].'" '.selected($value,(int)$x['id'],false).'>'.esc_html($display).'</option>';}echo '</select></label>';
 }
}
