<?php
namespace MySchool\Database\Migrations;
if(!defined('ABSPATH'))exit;
final class CreateAcademicStructure {
 public function up($wpdb):void {
  $p=$wpdb->prefix.'myschool_';$c=$wpdb->get_charset_collate();
  $tables=[
   'sections'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, title varchar(120) NOT NULL, sort_order int(11) NOT NULL DEFAULT 0, status varchar(20) NOT NULL DEFAULT 'active', PRIMARY KEY  (id), KEY school_id (school_id)",
   'grade_levels'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, section_id bigint(20) unsigned NOT NULL DEFAULT 0, title varchar(120) NOT NULL, sort_order int(11) NOT NULL DEFAULT 0, status varchar(20) NOT NULL DEFAULT 'active', PRIMARY KEY  (id), KEY school_id (school_id), KEY section_id (section_id)",
   'classes'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, academic_year_id bigint(20) unsigned NOT NULL, grade_level_id bigint(20) unsigned NOT NULL, shift_id bigint(20) unsigned DEFAULT NULL, title varchar(160) NOT NULL, capacity int(11) unsigned NOT NULL DEFAULT 0, status varchar(20) NOT NULL DEFAULT 'active', PRIMARY KEY  (id), KEY school_id (school_id), KEY academic_year_id (academic_year_id), KEY grade_level_id (grade_level_id), KEY shift_id (shift_id)"
  ];
  foreach($tables as $name=>$columns){$table=$p.$name;dbDelta("CREATE TABLE {$table} (\n{$columns}\n) {$c};");if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)throw new \RuntimeException('Missing MySchool table '.$table);}
 }
}
