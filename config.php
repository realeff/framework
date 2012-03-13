<?php
// 数据库
$databases['realeff'] = array(
  'default' => array(
      'driver' => 'mysql',
      'dbname' => 'realeff',
      'host' => '127.0.0.1',
      'username' => 'root',
      'password' => '123',
      'prefix' => 'real_',
      'prefix_exts' => array(
          ),
      'collation' => 'utf8_general_ci',
      ),
  'slave' => array(
    array(
      'driver' => 'mysql',
      'dbname' => 'micfm_rd',
      'host' => '127.0.0.1',
      'username' => 'root',
      'password' => '123',
      'prefix' => 'mic_',
      'collation' => 'utf8_general_ci',
      ),
    array(
      'driver' => 'mysql',
      'dbname' => 'micfm_rd1',
      'host' => '127.0.0.1',
      'username' => 'root',
      'password' => '123',
      'prefix' => 'mic_',
      'collation' => 'utf8_general_ci',
      )
  )
);
$databases['othersys'] = array();

// 数据查询器
$dataquerier['realeff'] = array(
  'cache' => 'default',
  'site' => 'slave',
  'test' => 'slave',
);

// 第三方应用提供的API类库目录
$library_dir = 'libraries';

// 扩展模块目录
$extension_dir = 'extension';

// 是否支持多站点
// 映射分站配置目录，默认分站相关内容存放在sites目录下
$multisites['default'] = array(
    'serial' => 0, // 站点序号
    'directory' => 'example.com', // 站点目录
    );
$multisites['example.com'] = array(
    'serial' => 1,
    'directory' => '',
    );
$multisites['*.realeff.com'] = array(
    'serial' => 2,
    'directory' => 'sites/realeff',
    );

// 授权KEY
$auth_key = 'd7xphQoX7B8GN1S5JDPKqHITWUOrpiw06E96kgfxaug';

$cookie_domain = '.realeff.com';

// 缓存
$config['caches'] = array(
  'default' => array(
    'driver' => 'file',
    'path'   => '{workspace}/cache',
    'secret'   => 'UOrpiw06E',
    'lifetime' => 0,
    'bin' => array(),
   ),
  'database' => array(
    'driver' => 'database',
    'querier' => 'cache', // 查询器名称
    'lifetime' => 0,
    'split'  => 1,
    'bin' => array('cache' => 10, 'cache_menu', 'cache_content' => 100) // 容器名称 => 容器数量
   ),
  'memcache' => array(
    'driver' => 'memcache',
    'prefix' => 'rpiw06E96',
    'lifetime' => 0,
    'bin' => array(),
    'option' => array('compress' => 1024, 'saving' => 0.2, 'persistent' => TRUE, 'timeout' => 1, 'retry_interval' => 15, 'status' => TRUE),
    'server' => array(
        'host' => '127.0.0.1',
        'port' => '11211',
        'persistent' => TRUE,
        'weight' => 0,
        'timeout' => 1,
        'retry_interval' => 15,
        'status' => TRUE
     )
   ),
  'memcached' => array(
    'driver' => 'memcached',
    'prefix' => 'rpiw06E96',
    'lifetime' => 0,
    'bin' => array(),
    'option' => array(),
    'server' => array(
        array('host' => '127.0.0.1', 'port' => '11211', 'weight' => 0)
     )
   )
);

// 站点名称
$config['site_name'] = 'RealEff';
// 系统日志文件目录
//$config['log_file_path'] = '';
// 系统临时文件目录
//$config['tmp_file_path'] = '';


// 支持移动平台
// 机器人访问
// 限制IP访问

// 包含Realeff Define文件
//include_once SYS_ROOT .'/define.php';
