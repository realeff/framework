<?php

// 数据库
$databases['realeff'] = array(
  'default' => array(
      'driver' => 'mysql',
      'dbname' => '',
      'host' => '',
      'port' => '',
      'username' => '',
      'password' => '',
      'prefix' => 'main_',
      'exprefix' => array(
          'session' => 'shared_',
          'authmap' => 'shared_'
          ),
      'collation' => 'utf8_general_ci',
      ),
  'slave0' => array(
      'driver' => 'mysql',
      'dbname' => '',
      'host' => '',
      'port' => '',
      'username' => '',
      'password' => '',
      'prefix' => 'main_',
      'collation' => 'utf8_general_ci',
      ),
  'slave1' => array(
      'driver' => '',
      'dbname' => '',
      'host' => '',
      'port' => '',
      'username' => '',
      'password' => '',
      'prefix' => 'main_',
      'collation' => 'utf8_general_ci',
      )
);
$databases['othersys'] = array();


// 默认使用default数据库的定义，
$datatables['realeff'] = array(
  'role' => 'user',
  'user' => array(
      'read' => 'user',
      'write' => 'default',
      ),
);

// 缓存设备数量
$cache_devnum = 1;

// 第三方应用提供的API类库目录
$library_dir = 'libraries1';

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

$cookie_domain = '.example.com';

// 重写session处理inc方法
// 重写cache处理inc方法

$config['site_name'] = 'RealEff';


