<?php
namespace MySchool\Database\Migrations;
if (!defined('ABSPATH')) { exit; }
final class CreateAuditTable {
 public function up($wpdb): void {
  $table=$wpdb->prefix.'myschool_audit_logs';
  $collate=$wpdb->get_charset_collate();
  dbDelta("CREATE TABLE {$table} (
 id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
 actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
 event varchar(100) NOT NULL,
 subject_type varchar(80) NOT NULL DEFAULT '',
 subject_id bigint(20) unsigned NOT NULL DEFAULT 0,
 context longtext NULL,
 occurred_at datetime NOT NULL,
 PRIMARY KEY  (id),
 KEY occurred_at (occurred_at),
 KEY actor_id (actor_id),
 KEY event (event)
) {$collate};");
  $found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
  if($found!==$table) throw new \RuntimeException('MySchool could not create audit table');
 }
}
