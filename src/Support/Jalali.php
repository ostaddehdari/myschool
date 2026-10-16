<?php
namespace MySchool\Support;
if(!defined('ABSPATH'))exit;
final class Jalali {
 public static function toJalali(int $gy,int $gm,int $gd):array{
  if(!checkdate($gm,$gd,$gy))throw new \InvalidArgumentException('Invalid Gregorian date');
  $gdm=[0,31,59,90,120,151,181,212,243,273,304,334];$gy2=$gm>2?$gy+1:$gy;
  $days=355666+365*$gy+intdiv($gy2+3,4)-intdiv($gy2+99,100)+intdiv($gy2+399,400)+$gd+$gdm[$gm-1];
  $jy=-1595+33*intdiv($days,12053);$days%=12053;$jy+=4*intdiv($days,1461);$days%=1461;
  if($days>365){$jy+=intdiv($days-1,365);$days=($days-1)%365;}
  return $days<186?[$jy,1+intdiv($days,31),1+$days%31]:[$jy,7+intdiv($days-186,30),1+($days-186)%30];
 }
 public static function toGregorian(int $jy,int $jm,int $jd):array{
  if($jy<1||$jy>3000||$jm<1||$jm>12||$jd<1||$jd>31)throw new \InvalidArgumentException('Invalid Jalali date');
  $orig=[$jy,$jm,$jd];$jy+=1595;
  $days=-355668+365*$jy+intdiv($jy,33)*8+intdiv($jy%33+3,4)+$jd+($jm<7?($jm-1)*31:($jm-7)*30+186);
  $gy=400*intdiv($days,146097);$days%=146097;
  if($days>36524){$gy+=100*intdiv(--$days,36524);$days%=36524;if($days>=365)$days++;}
  $gy+=4*intdiv($days,1461);$days%=1461;
  if($days>365){$gy+=intdiv($days-1,365);$days=($days-1)%365;}
  $gd=$days+1;$leap=$gy%4===0&&($gy%100!==0||$gy%400===0);
  $lengths=[0,31,$leap?29:28,31,30,31,30,31,31,30,31,30,31];$gm=1;
  while($gm<=12&&$gd>$lengths[$gm])$gd-=$lengths[$gm++];
  if($gm>12||self::toJalali($gy,$gm,$gd)!==$orig)throw new \InvalidArgumentException('Invalid Jalali day');
  return [$gy,$gm,$gd];
 }
 public static function format(string $iso):string{
  $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$iso);if(!$d||$d->format('Y-m-d')!==$iso)throw new \InvalidArgumentException('Expected YYYY-MM-DD');
  [$y,$m,$day]=self::toJalali((int)$d->format('Y'),(int)$d->format('m'),(int)$d->format('d'));
  return sprintf('%04d/%02d/%02d',$y,$m,$day);
 }
}
