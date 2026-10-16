<?php
namespace MySchool\Core;
if (!defined('ABSPATH')) { exit; }
/** Security-conscious activity log. Never store passwords, tokens or full request payloads. */
final class AuditLog {
 public static function record(string $event,string $subjectType='',int $subjectId=0,array $context=[]): bool {
  global $wpdb;
  if (!preg_match('/^[a-z][a-z0-9_.-]{1,99}$/D',$event)) throw new \InvalidArgumentException('Invalid audit event');
  $allowed=['old_status','new_status','setting_keys','source'];
  $safe=array_intersect_key($context,array_fill_keys($allowed,true));
  foreach($safe as $key=>$value) {if(!is_scalar($value))unset($safe[$key]);}
  $ok=$wpdb->insert($wpdb->prefix.'myschool_audit_logs',[
   'actor_id'=>get_current_user_id(),'event'=>$event,'subject_type'=>sanitize_key($subjectType),
   'subject_id'=>max(0,$subjectId),'context'=>wp_json_encode($safe),'occurred_at'=>current_time('mysql',true)
  ],['%d','%s','%s','%d','%s','%s']);
  return $ok===1;
 }
}
