<?php
namespace MySchool\Database\Migrations;
if (!defined('ABSPATH')) exit;
final class CreateShiftSchedule {
 public function up($wpdb): void {
  $prefix=$wpdb->prefix.'myschool_';$cc=$wpdb->get_charset_collate();
  $schemas=[
   'shifts'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, name varchar(120) NOT NULL, starts_at time DEFAULT NULL, ends_at time DEFAULT NULL, active_days varchar(20) NOT NULL DEFAULT '0,1,2,3,4', status varchar(20) NOT NULL DEFAULT 'active', PRIMARY KEY  (id), KEY school_id (school_id)",
   'shift_periods'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, shift_id bigint(20) unsigned NOT NULL, title varchar(150) NOT NULL, period_type varchar(20) NOT NULL DEFAULT 'lesson', starts_at time NOT NULL, ends_at time NOT NULL, sort_order int(11) NOT NULL DEFAULT 0, PRIMARY KEY  (id), KEY shift_id (shift_id), KEY starts_at (starts_at)"
  ];
  foreach($schemas as $name=>$schema){$table=$prefix.$name;dbDelta("CREATE TABLE {$table} (\n{$schema}\n) {$cc};");$found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));if($found!==$table)throw new \RuntimeException('Unable to create table '.$table);}
 }
}