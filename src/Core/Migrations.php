<?php
namespace MySchool\Core;
use MySchool\Database\Migrations\CreateCoreTables;
use MySchool\Database\Migrations\CreateAuditTable;
if (!defined('ABSPATH')) { exit; }
final class Migrations {
    /** @return array<int,object> Ordered and immutable once released. */
    public static function all(): array { return [1 => new CreateCoreTables(), 2 => new CreateAuditTable()]; }
}
