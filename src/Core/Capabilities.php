<?php
namespace MySchool\Core;
if (!defined('ABSPATH')) { exit; }
/** Central permission catalog. Never grant arbitrary WordPress administrator privileges. */
final class Capabilities {
 public const VERSION='1';
 public const VERSION_OPTION='myschool_capabilities_version';
 public static function groups(): array {
  return [
   'school'=>['myschool_access','myschool_manage_school','myschool_manage_academic_years','myschool_manage_classes','myschool_manage_staff','myschool_manage_students','myschool_manage_guardians','myschool_manage_enrollments'],
   'teaching'=>['myschool_view_assigned_classes','myschool_take_attendance','myschool_view_attendance','myschool_manage_grades','myschool_view_grades','myschool_manage_homework','myschool_view_homework'],
   'reports'=>['myschool_publish_reportcards','myschool_view_reportcards','myschool_view_reports'],
   'services'=>['myschool_manage_canteen','myschool_view_canteen','myschool_manage_tickets','myschool_create_ticket','myschool_send_messages','myschool_manage_sms','myschool_manage_integrations'],
   'family'=>['myschool_view_own_children','myschool_view_own_attendance','myschool_view_own_grades','myschool_view_own_reportcards','myschool_view_own_homework','myschool_order_canteen','myschool_view_own_tickets']
  ];
 }
 public static function all(): array { return array_values(array_unique(array_merge(...array_values(self::groups())))); }
 public static function roles(): array {
  $g=self::groups();
  return [
   'myschool_manager'=>['name'=>'مدیر مدرسه','caps'=>self::all()],
   'myschool_teacher'=>['name'=>'معلم مدرسه','caps'=>array_merge(['myschool_access'],$g['teaching'],['myschool_view_reportcards','myschool_send_messages'])],
   'myschool_parent'=>['name'=>'ولی دانش‌آموز','caps'=>array_merge(['myschool_access'],$g['family'],['myschool_view_canteen','myschool_create_ticket'])],
   'myschool_staff'=>['name'=>'کارمند مدرسه','caps'=>['myschool_access','myschool_view_assigned_classes','myschool_view_attendance','myschool_manage_students','myschool_manage_enrollments','myschool_manage_tickets','myschool_view_reports']],
   'myschool_canteen_staff'=>['name'=>'متصدی بوفه','caps'=>['myschool_access','myschool_view_canteen','myschool_manage_canteen']]
  ];
 }
 public static function install(): void {
  foreach(self::roles() as $slug=>$spec){
   $role=get_role($slug);
   if(!$role){add_role($slug,$spec['name'],['read'=>true]);$role=get_role($slug);}
   if(!$role) throw new \RuntimeException('Could not register MySchool role: '.$slug);
   $role->add_cap('read');
   foreach($spec['caps'] as $cap)$role->add_cap($cap);
  }
  $admin=get_role('administrator');
  if(!$admin)throw new \RuntimeException('Administrator role is unavailable.');
  foreach(self::all() as $cap)$admin->add_cap($cap);
  update_option(self::VERSION_OPTION,self::VERSION,false);
 }
 public static function maybeUpgrade(): void {
  if(!current_user_can('manage_options'))return;
  if(get_option(self::VERSION_OPTION,'')!==self::VERSION)self::install();
 }
}
