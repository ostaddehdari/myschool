<?php
namespace MySchool\Database\Migrations;
if (!defined('ABSPATH')) { exit; }
final class ExtendSchoolProfile {
 public function up($wpdb): void {
  $table = $wpdb->prefix . 'myschool_schools';
  $charset = $wpdb->get_charset_collate();
  dbDelta("CREATE TABLE {$table} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 name varchar(255) NOT NULL,
 code varchar(64) NOT NULL DEFAULT '',
 status varchar(20) NOT NULL DEFAULT 'active',
 province varchar(120) NOT NULL DEFAULT '',
 city varchar(120) NOT NULL DEFAULT '',
 address text NULL,
 postal_code varchar(24) NOT NULL DEFAULT '',
 phone varchar(40) NOT NULL DEFAULT '',
 email varchar(190) NOT NULL DEFAULT '',
 website varchar(255) NOT NULL DEFAULT '',
 principal_name varchar(190) NOT NULL DEFAULT '',
 logo_attachment_id bigint(20) unsigned NOT NULL DEFAULT 0,
 created_at datetime NOT NULL,
 updated_at datetime NOT NULL,
 PRIMARY KEY  (id),
 KEY code (code)
) {$charset};");
  $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
  if ($found !== $table) throw new \RuntimeException('MySchool school profile migration failed');
 }
}
