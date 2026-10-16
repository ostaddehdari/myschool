<?php
namespace MySchool\Core;
if (!defined('ABSPATH')) { exit; }
final class Autoloader {
 private const PREFIX = 'MySchool\\';
 private static bool $registered = false;
 public static function register(): void { if (self::$registered) return; spl_autoload_register(array(self::class,'load')); self::$registered=true; }
 public static function load(string $class): void {
   if (strncmp($class,self::PREFIX,strlen(self::PREFIX))!==0) return;
   $relative=substr($class,strlen(self::PREFIX));
   if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/D',$relative)) return;
   $path=MYSCHOOL_PATH.'src/'.str_replace('\\','/',$relative).'.php';
   if (is_file($path)) require_once $path;
 }
}
