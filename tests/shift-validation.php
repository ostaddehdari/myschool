<?php
const ABSPATH='/';
require dirname(__DIR__).'/src/Modules/Shifts/ShiftValidator.php';
use MySchool\Modules\Shifts\ShiftValidator as V;
function test($ok,$msg){if(!$ok)throw new RuntimeException($msg);echo "PASS ".$msg."\n";}
$a=V::validate('صبح','07:30','12:30',[0,1,2],[['title'=>'اول','type'=>'lesson','start'=>'07:30','end'=>'08:15'],['title'=>'استراحت','type'=>'break','start'=>'08:15','end'=>'08:30']]);test(count($a['periods'])===2,'adjacent periods');try{V::validate('صبح','07:30','12:30',[0],[['title'=>'اول','type'=>'lesson','start'=>'08:00','end'=>'09:00'],['title'=>'دوم','type'=>'lesson','start'=>'08:30','end'=>'09:30']]);throw new RuntimeException('overlap accepted');}catch(InvalidArgumentException $e){test(true,'overlap rejected');}
