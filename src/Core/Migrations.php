<?php
namespace MySchool\Core;
use MySchool\Database\Migrations\CreateCoreTables;
use MySchool\Database\Migrations\CreateAuditTable;
use MySchool\Database\Migrations\ExtendSchoolProfile;
if(!defined('ABSPATH')){exit;}
final class Migrations {
 public static function all():array{return [1=>new CreateCoreTables(),2=>new CreateAuditTable(),3=>new ExtendSchoolProfile()];}
}
