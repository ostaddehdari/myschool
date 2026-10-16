<?php
namespace MySchool\Modules\Shifts;
if(!defined('ABSPATH'))exit;
final class ShiftValidator {
 public static function minutes(string $time):int {
  if(!preg_match('/^([01][0-9]|2[0-3]):([0-5][0-9])$/D',$time,$m))throw new \InvalidArgumentException('زمان نامعتبر');
  return (int)$m[1]*60+(int)$m[2];
 }
 public static function validate(string $name,string $start,string $end,array $days,array $periods):array {
  $name=trim($name);if($name===''||strlen($name)>360)throw new \InvalidArgumentException('نام شیفت معتبر نیست');
  $a=self::minutes($start);$b=self::minutes($end);if($b<=$a)throw new \InvalidArgumentException('ساعت پایان باید بعد از شروع باشد');
  $days=array_values(array_unique(array_map('intval',$days)));sort($days);
  if(!$days||count(array_filter($days,static fn($d)=>$d<0||$d>6)))throw new \InvalidArgumentException('حداقل یک روز معتبر انتخاب کنید');
  $out=[];foreach($periods as $i=>$period){
   $title=trim((string)($period['title']??''));$type=(string)($period['type']??'lesson');
   $p1=self::minutes((string)($period['start']??''));$p2=self::minutes((string)($period['end']??''));
   if($title===''||strlen($title)>450||!in_array($type,['lesson','break','assembly','other'],true)||$p1<$a||$p2>$b||$p1>=$p2)throw new \InvalidArgumentException('زنگ یا ساعت آن نامعتبر است');
   $out[]=['title'=>$title,'type'=>$type,'start'=>$period['start'],'end'=>$period['end'],'from'=>$p1,'to'=>$p2,'order'=>$i];
  }
  usort($out,static fn($x,$y)=>$x['from']<=>$y['from']);
  for($i=1;$i<count($out);$i++)if($out[$i]['from']<$out[$i-1]['to'])throw new \InvalidArgumentException('زنگ‌های آموزشی با یکدیگر تداخل دارند');
  return ['name'=>$name,'start'=>$start,'end'=>$end,'days'=>$days,'periods'=>$out];
 }
 public static function overlaps(array $a,array $b):bool {
  return (bool)array_intersect($a['days'],$b['days'])&&self::minutes($a['start'])<self::minutes($b['end'])&&self::minutes($b['start'])<self::minutes($a['end']);
 }
}