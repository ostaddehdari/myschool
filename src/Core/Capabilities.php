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
 public static function groupLabels(): array {
  return ['school'=>'مدیریت مدرسه','teaching'=>'آموزش و کلاس','reports'=>'کارنامه و گزارش‌ها',
          'services'=>'خدمات و ارتباطات','family'=>'اطلاعات شخصی خانواده'];
 }
 /** Human-readable labels are separate from capability keys, which stay stable in WordPress. */
 public static function labels(): array {
  return [
   'myschool_access'=>'ورود به سامانه مدرسه',
   'myschool_manage_school'=>'مدیریت کامل مدرسه',
   'myschool_manage_academic_years'=>'مدیریت سال‌های تحصیلی',
   'myschool_manage_classes'=>'مدیریت کلاس‌ها',
   'myschool_manage_staff'=>'مدیریت کارکنان و معلمان',
   'myschool_manage_students'=>'مدیریت دانش‌آموزان',
   'myschool_manage_guardians'=>'مدیریت اولیا',
   'myschool_manage_enrollments'=>'مدیریت ثبت‌نام‌ها',
   'myschool_view_assigned_classes'=>'مشاهده کلاس‌های واگذارشده',
   'myschool_take_attendance'=>'ثبت حضور و غیاب',
   'myschool_view_attendance'=>'مشاهده حضور و غیاب',
   'myschool_manage_grades'=>'ثبت و ویرایش نمرات',
   'myschool_view_grades'=>'مشاهده نمرات',
   'myschool_manage_homework'=>'تعریف و ویرایش تکالیف',
   'myschool_view_homework'=>'مشاهده تکالیف',
   'myschool_publish_reportcards'=>'انتشار کارنامه‌ها',
   'myschool_view_reportcards'=>'مشاهده کارنامه‌ها',
   'myschool_view_reports'=>'مشاهده گزارش‌های مدیریتی',
   'myschool_manage_canteen'=>'مدیریت بوفه',
   'myschool_view_canteen'=>'مشاهده بوفه',
   'myschool_manage_tickets'=>'مدیریت درخواست‌های پشتیبانی',
   'myschool_create_ticket'=>'ثبت درخواست پشتیبانی',
   'myschool_send_messages'=>'ارسال پیام‌ها',
   'myschool_manage_sms'=>'مدیریت تنظیمات پیامک',
   'myschool_manage_integrations'=>'مدیریت اتصال به سرویس‌ها',
   'myschool_view_own_children'=>'مشاهده فرزندان خود',
   'myschool_view_own_attendance'=>'مشاهده حضور و غیاب فرزندان خود',
   'myschool_view_own_grades'=>'مشاهده نمرات فرزندان خود',
   'myschool_view_own_reportcards'=>'مشاهده کارنامه فرزندان خود',
   'myschool_view_own_homework'=>'مشاهده تکالیف فرزندان خود',
   'myschool_order_canteen'=>'سفارش بوفه برای فرزندان خود',
   'myschool_view_own_tickets'=>'مشاهده درخواست‌های خود'
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
