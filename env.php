<?php
define('RESYS_DEBUG', TRUE);
define('RESYS_ROOT', getcwd());
// 装载引导程序
include_once RESYS_ROOT .'/includes/bootstrap.php';
// 引导程序
realeff_bootstrap();

$_ENV['timer'] = timer_read('bootstrap');
echo "<pre style=\"border: 1px solid #000; margin: 0.5em;\">";
var_dump($_ENV);
echo "</pre>\n";
//print htmlentities(var_export($_ENV, TRUE));

include_once RESYS_ROOT .'/includes/storage/store.php';
include_once RESYS_ROOT .'/includes/storage/query.php';
include_once RESYS_ROOT .'/includes/storage/analyzer.php';
include_once RESYS_ROOT .'/includes/storage/mysql/connection.php';
include_once RESYS_ROOT .'/includes/storage/mysql/command.php';
include_once RESYS_ROOT .'/includes/storage/mysql/analyzer.php';

$basememory = memory_get_usage();
$conn = new StoreConnection_mysql(array('host' => 'localhost', 'username' => 'root', 'password' => '123', 'dbname' => 'test'));
$conn->openConnection();

$cmd = $conn->command('test');
$firstmemory = memory_get_usage();
function testmem($cmd) {
$param = new QueryParameter('test');
$query = new UpdateQuery('test', $param);
$query->fields(array('field0' => 'value0', 'field1' => 'value1', 'field2' => 'value2'));
$query->condition()
->compare('a', '1', '=')
->contain('b', '%abc%')
->add()->add()
->compare('c', 0, '>')->_OR()->compare('d', 10, '<')->compare('dd', '3', '>')
->append()
->compare('f', '20', '<')->_OR()->compare('d', '10', '<')
->contain('b', '%abc%')
->end()
->addComment('这就是一个测试')->end();

  return new SQLUpdateAnalyzer($query);
}
print testmem($cmd) .'<br>';
echo "<pre style=\"border: 1px solid #000; margin: 0.5em;\">";
var_dump(testmem($cmd)->arguments());
echo "</pre>\n";
print memory_get_usage()-$firstmemory .'<br>';
$conn->closeConnection();
$firstmemory = memory_get_usage();
//$query = "delete from test where a = 1 and b like '%abc%' and ((c > 0 or d < 10 or dd > 3) and f < 20 or d < 10)";

function testmquery($cmd) {
  
  $param = new QueryParameter('test');
  $query = new SelectQuery('test', $param);
  $query->fields(array('field0' => 'value0', 'field1' => 'value1', 'field2' => 'value2'));
  $query->condition()
  ->compare('a', '1', '=')
  ->contain('b', '%abc%');
  $mquery = new MultiSelectQuery('test', 't', $param);
  $mquery->field('abc')->field('ddd')->field('bdd');
  $mquery->innerJoin('abc', 'dd', '')->addField('dd', 'bdc', 'bccd');
  $mquery->innerJoin($query, 'subquery', '')->addField('subquery', 'dbc');
  $mquery->condition()
->compare('a', '1', '=')
->contain('b', '%abc%')
->add()->add()
->compare('c', 0, '>')->_OR()->compare('d', 10, '<')->compare('dd', '3', '>')
->append()
->compare('f', '20', '<')->_OR()->compare('d', '10', '<')
->end();
  $mquery->orderBy('random_field', SelectQuery::RANDOM)->addComment('这就是一个测试')->end();
  
  return new SQLSelectAnalyzer($mquery);
}
print testmquery($cmd) .'<br>';
echo "<pre style=\"border: 1px solid #000; margin: 0.5em;\">";
var_dump(testmquery($cmd)->arguments());
echo "</pre>\n";

print memory_get_usage()-$firstmemory .'<br>';

timer_start('test');
for ($i = 0; $i < 1000; $i++) {
  testmquery($cmd)->arguments();
}
timer_stop('test');
print timer_read('test') .'<br>';

print $firstmemory-$basememory .'<br>';
print memory_get_usage()-$basememory .'<br>';
print memory_get_usage();



