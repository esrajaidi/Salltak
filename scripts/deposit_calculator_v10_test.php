<?php
$service = __DIR__.'/../app/Services/DepositCalculator.php';
if (!is_file($service)) { fwrite(STDERR, "DepositCalculator missing\n"); exit(1); }
require_once $service;
use App\Services\DepositCalculator;

$calc = new DepositCalculator();
$rules = [
 ['min_total'=>0,'max_total'=>199.99,'type'=>'percentage','value'=>30,'is_active'=>true,'sort_order'=>1],
 ['min_total'=>200,'max_total'=>500,'type'=>'percentage','value'=>40,'is_active'=>true,'sort_order'=>2],
 ['min_total'=>500.01,'max_total'=>null,'type'=>'percentage','value'=>50,'is_active'=>true,'sort_order'=>3],
];
$cases = [
 ['low band',100,$rules,null,30.00],
 ['middle band',400,$rules,null,160.00],
 ['high band',1000,$rules,null,500.00],
 ['manual fixed override',1000,$rules,['type'=>'fixed','value'=>125],125.00],
 ['manual percent override',1000,$rules,['type'=>'percentage','value'=>25],250.00],
];
$f=0;
foreach($cases as [$name,$total,$ruleset,$override,$expected]){
    $actual=$calc->calculate($total,$ruleset,$override);
    $ok=abs($actual-$expected)<0.001;
    echo ($ok?'[PASS] ':'[FAIL] ').$name.' => '.$actual.PHP_EOL;
    $f += $ok?0:1;
}
exit($f?1:0);
