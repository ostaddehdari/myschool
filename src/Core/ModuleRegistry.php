<?php
namespace MySchool\Core;
use MySchool\Contracts\ModuleInterface;
if (!defined('ABSPATH')) { exit; }
final class ModuleRegistry {
 private array $modules=[];
 private bool $booted=false;
 public function add(ModuleInterface $module): void {
   if ($this->booted) throw new \LogicException('Cannot add modules after registry boot.');
   $id=$module->id();
   if (!preg_match('/^[a-z][a-z0-9_-]*$/D',$id)) throw new \InvalidArgumentException('Invalid module id: '.$id);
   if (isset($this->modules[$id])) throw new \LogicException('Duplicate module id: '.$id);
   $this->modules[$id]=$module;
 }
 public function boot(): void {
   if ($this->booted) return;
   $this->booted=true;
   foreach($this->modules as $module) $module->register();
 }
 public function ids(): array { return array_keys($this->modules); }
}
