<?php
namespace MySchool\Core;
use MySchool\Database\Migrations\CreateCoreTables;
if (!defined('ABSPATH')) { exit; }
final class Migrations {
 public static function all(): array { return [1=>new CreateCoreTables()]; }
}
