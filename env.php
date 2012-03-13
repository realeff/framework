<?php
print memory_get_usage();
define('REALEFF_DEBUG', TRUE);
define('REALEFF_ROOT', getcwd());
// 装载引导程序
include_once REALEFF_ROOT .'/includes/bootstrap.php';
// 引导程序
$basememory = memory_get_usage();
realeff_bootstrap(REALEFF_BOOTSTRAP_FULL);

$_ENV['timer'] = timer_read('bootstrap');
echo "<pre style=\"border: 1px solid #000; margin: 0.5em;\">";
var_dump($_ENV);
echo "</pre>\n";

//print htmlentities(var_export($_ENV, TRUE));
$firstmemory = memory_get_usage();
print $firstmemory-$basememory .'<br>';


timer_start('test');
for ($i = 0; $i < 1000; $i++) {
  //variable_set('test'. $i, $i);
  variable_get('test'. $i);
}
timer_stop('test');
print timer_read('test') .'<br>';

print memory_get_usage()-$firstmemory .'<br>';
print memory_get_usage()-$basememory .'<br>';
print memory_get_usage();





