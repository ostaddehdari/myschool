<?php
namespace MySchool\Database\Migrations;
if (!defined('ABSPATH')) { exit; }
final class CreateCoreTables {
 public function up($wpdb): void {
   $prefix=$wpdb->prefix.'myschool_';
   $charset=$wpdb->get_charset_collate();
   $schemas=[
     'schools'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, name varchar(255) NOT NULL, code varchar(64) NOT NULL DEFAULT '', status varchar(20) NOT NULL DEFAULT 'active', created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY code (code)",
     'academic_years'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, title varchar(30) NOT NULL, starts_on date DEFAULT NULL, ends_on date DEFAULT NULL, status varchar(20) NOT NULL DEFAULT 'draft', PRIMARY KEY  (id), KEY school_id (school_id), KEY status (status)",
     'shifts'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, name varchar(120) NOT NULL, starts_at time DEFAULT NULL, ends_at time DEFAULT NULL, PRIMARY KEY  (id), KEY school_id (school_id)",
     'grade_levels'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, title varchar(120) NOT NULL, sort_order int(11) NOT NULL DEFAULT 0, PRIMARY KEY  (id), KEY school_id (school_id)",
     'classes'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, academic_year_id bigint(20) unsigned NOT NULL, grade_level_id bigint(20) unsigned NOT NULL, shift_id bigint(20) unsigned DEFAULT NULL, title varchar(160) NOT NULL, capacity int(11) unsigned NOT NULL DEFAULT 0, status varchar(20) NOT NULL DEFAULT 'active', PRIMARY KEY  (id), KEY academic_year_id (academic_year_id), KEY grade_level_id (grade_level_id), KEY shift_id (shift_id)",
     'students'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, student_code varchar(64) NOT NULL DEFAULT '', first_name varchar(150) NOT NULL, last_name varchar(150) NOT NULL, national_id varchar(32) NOT NULL DEFAULT '', status varchar(24) NOT NULL DEFAULT 'active', created_at datetime NOT NULL, updated_at datetime NOT NULL, PRIMARY KEY  (id), KEY school_id (school_id), KEY student_code (student_code), KEY status (status)",
     'guardians'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, school_id bigint(20) unsigned NOT NULL, wp_user_id bigint(20) unsigned DEFAULT NULL, first_name varchar(150) NOT NULL, last_name varchar(150) NOT NULL, mobile varchar(32) NOT NULL DEFAULT '', PRIMARY KEY  (id), KEY school_id (school_id), KEY wp_user_id (wp_user_id)",
     'student_guardians'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, student_id bigint(20) unsigned NOT NULL, guardian_id bigint(20) unsigned NOT NULL, relation varchar(40) NOT NULL DEFAULT 'guardian', is_primary tinyint(1) NOT NULL DEFAULT 0, PRIMARY KEY  (id), UNIQUE KEY student_guardian (student_id,guardian_id), KEY guardian_id (guardian_id)",
     'enrollments'=>"id bigint(20) unsigned NOT NULL AUTO_INCREMENT, student_id bigint(20) unsigned NOT NULL, academic_year_id bigint(20) unsigned NOT NULL, class_id bigint(20) unsigned NOT NULL, status varchar(24) NOT NULL DEFAULT 'active', enrolled_at datetime NOT NULL, PRIMARY KEY  (id), UNIQUE KEY student_year (student_id,academic_year_id), KEY class_id (class_id)"
   ];
   foreach ($schemas as $suffix=>$definition) {
     $table=$prefix.$suffix;
     dbDelta("CREATE TABLE {$table} (\n{$definition}\n) {$charset};");
     $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
     if ($found!==$table) throw new \RuntimeException('MySchool could not create database table: '.$table);
   }
 }
}
