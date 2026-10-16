<?php
namespace MySchool\Modules\Api;
use MySchool\Contracts\ModuleInterface;
use MySchool\Support\Jalali;
if (!defined('ABSPATH')) { exit; }
final class ApiModule implements ModuleInterface {
 public function id(): string { return 'api'; }
 public function register(): void { add_action('rest_api_init',[$this,'routes']); }
 public function routes(): void {
  register_rest_route('myschool/v1','/status',[
   'methods'=>'GET','callback'=>[$this,'status'],
   'permission_callback'=>static fn()=>current_user_can('myschool_manage_school'),
  ]);
  register_rest_route('myschool/v1','/date/convert',[
   'methods'=>'GET','callback'=>[$this,'convert'],
   'permission_callback'=>static fn()=>current_user_can('myschool_access'),
   'args'=>['date'=>['required'=>true,'type'=>'string','sanitize_callback'=>'sanitize_text_field']],
  ]);
 }
 public function status(): array {return ['version'=>MYSCHOOL_VERSION,'db_version'=>get_option('myschool_db_version','0'),'modules'=>\MySchool\Core\Plugin::instance()->modules()->ids()];}
 public function convert($request) {
  try { $date=$request->get_param('date');return ['gregorian'=>$date,'jalali'=>Jalali::format($date)]; }
  catch(\InvalidArgumentException $e){return new \WP_Error('myschool_bad_date','Invalid Gregorian date',['status'=>400]);}
 }
}
