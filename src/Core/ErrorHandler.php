<?php
namespace MySchool\Core;
if (!defined('ABSPATH')) { exit; }
final class ErrorHandler {
 public static function report(\Throwable $error,string $area): void {
  $category=preg_replace('/[^a-z0-9_.-]/','',strtolower($area));
  error_log('[MySchool]['.$category.'] '.get_class($error).' at '.basename($error->getFile()).':'.$error->getLine());
  do_action('myschool/error_reported',$category,get_class($error));
 }
}
