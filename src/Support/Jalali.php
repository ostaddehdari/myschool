<?php
namespace MySchool\Support;
if (!defined('ABSPATH')) { exit; }
/** Gregorian/Jalali civil-date conversion. Dates persisted in SQL remain Gregorian. */
final class Jalali {
 private static function div(int $a,int $b): int { return intdiv($a,$b); }
 public static function toJalali(int $gy,int $gm,int $gd): array {
  self::validate($gy,$gm,$gd);
  $gdm=[0,31,59,90,120,151,181,212,243,273,304,334];
  $gy2=$gm>2?$gy+1:$gy;
  $days=355666+365*$gy+self::div($gy2+3,4)-self::div($gy2+99,100)+self::div($gy2+399,400)+$gd+$gdm[$gm-1];
  $jy=-1595+33*self::div($days,12053); $days%=12053;
  $jy+=4*self::div($days,1461); $days%=1461;
  if($days>365){$jy+=self::div($days-1,365);$days=($days-1)%365;}
  if($days<186){$jm=1+self::div($days,31);$jd=1+$days%31;}
  else {$jm=7+self::div($days-186,30);$jd=1+($days-186)%30;}
  return [$jy,$jm,$jd];
 }
 public static function toGregorian(int $jy,int $jm,int $jd): array {
  if($jy<1||$jy>3000||$jm<1||$jm>12||$jd<1||$jd>31) throw new \InvalidArgumentException('Invalid Jalali date');
  $jy+=1595;
  $days=-355668+365*$jy+self::div($jy,33)*8+self::div($jy%33+3,4)+$jd+($jm<7?($jm-1)*31:($jm-7)*30+186);
  $gy=400*self::div($days,146097);$days%=146097;
  if($days>36524){$gy+=100*self::div(--$days,36524);$days%=36524;if($days>=365)$days++;}
  $gy+=4*self::div($days,1461);$days%=1461;
  if($days>365){$gy+=self::div($days-1,365);$days=($days-1)%365;}
  $gd=$days+1;
  $sal=[0,31,self::leap($gy)?29:28,31,30,31,30,31,31,30,31,30,31];
  $gm=1;while($gm<=12&&$gd>$sal[$gm]){$gd-=$sal[$gm];$gm++;}
  if($gm>12)throw new \InvalidArgumentException('Invalid Jalali date');
  if(self::toJalali($gy,$gm,$gd)!==[$jy-1595,$jm,$jd])throw new \InvalidArgumentException('Invalid Jalali day');
  return [$gy,$gm,$gd];
 }
 public static function format(string $iso): string {
  $d=\DateTimeImmutable::createFromFormat('!Y-m-d',$iso);
  if(!$d||$d->format('Y-m-d')!==$iso)throw new \InvalidArgumentException('Date must be YYYY-MM-DD');
  return implode('/',array_map(static fn($v)=>str_pad((string)$v,2,'0',STR_PAD_LEFT),self::toJalali((int)$d->format('Y'),(int)$d->format('m'),(int)$d->format('d'))));
 }
 private static function leap(int $y):bool{return $y%4===0&&($y%100!==0||$y%400===0);}
 private static function validate(int $y,int $m,int $d):void {if(!checkdate($m,$d,$y))throw new \InvalidArgumentException('Invalid Gregorian date');}
}
