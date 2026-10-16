<?php
namespace MySchool\Contracts;
if (!defined('ABSPATH')) { exit; }
interface ModuleInterface {
 public function id(): string;
 public function register(): void;
}
