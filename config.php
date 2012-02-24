<?php

// 数据库
$databases['realeff'] = array(
  'default' => array(
      'driver' => 'mysql',
      'dbname' => 'micfm_db',
      'host' => '127.0.0.1',
      'username' => 'root',
      'password' => '123',
      'prefix' => 'mic_',
      'prefix_exts' => array(
          'session' => 'shared_',
          'authmap' => 'shared_'
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
  'cache' => 'slave',
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

$cookie_domain = '.example.com';

// 重写session处理inc方法
// 重写cache处理inc方法

$config['site_name'] = 'RealEff';


