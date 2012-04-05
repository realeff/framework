<!Doctype html>
<html xmlns=http://www.w3.org/1999/xhtml>
<head>
  <meta http-equiv=Content-Type content="text/html;charset=utf-8">
  <title>测试运行环境</title>
</head>
<body>
<?php
print memory_get_usage() .'<br>';
 define('REALEFF_DEBUG', TRUE);
// define('REALEFF_DEBUG_LOG', TRUE);
define('REALEFF_ROOT', getcwd());
// 装载引导程序
include_once REALEFF_ROOT .'/includes/bootstrap.php';
// 引导程序
$basememory = memory_get_usage();
realeff_bootstrap();
// session_regenerate_id(TRUE);
// session_id(realeff_hash_base64(uniqid(mt_rand(), TRUE)));

$_ENV['timer'] = Realeff::timer_read('bootstrap');
$_SESSION['bootstrap_timer'] = $_ENV['timer'];

print $_ENV['timer'] .'<br>';

//print htmlentities(var_export($_ENV, TRUE));
$firstmemory = memory_get_usage();
print $firstmemory-$basememory .'<br>';

$_SESSION['bootstrap_memory'] = $firstmemory-$basememory;

$filters = array('test', 'cache', 'select');
Realeff::timer_start('test');
for ($i = 0; $i < 1000; $i++) {
  //variable_set('test'. $i, $i);
}
Realeff::timer_stop('test');
print Realeff::timer_read('test') .'<br>';

print memory_get_usage()-$firstmemory .'<br>';
print memory_get_usage()-$basememory .'<br>';
print memory_get_usage();

?>
</body>
</html>


