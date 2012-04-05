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

// 会话处理程序
$config['session_handler'] = 'database';

// 缓存处理程序
$config['cache_handler'] = array(
  'cache' => array(
    'driver' => 'file',
    'path'   => '{workspace}/cache',
    'secret'   => 'UOrpiw06E',
    'lifetime' => 0
   ),
  'cache' => array(
    'driver' => 'database',
    'querier' => 'cache', // 查询器名称
    'lifetime' => 0
   ),
//   'cache' => array(
//     'driver' => 'memcache',
//     'lifetime' => 0,
//     'option' => array(),
//     'server' => array(
//         'host' => '127.0.0.1',
//         'port' => '11211',
//         'weight' => 0,
//      )
//    ),
//   'cache_session' => array(
//     'driver' => 'memcache',
//     'lifetime' => 0,
//     'option' => array(),
//     'server' => array(
//         'host' => '127.0.0.1',
//         'port' => '11211',
//         'weight' => 0,
//      ),
//    ),
//   'memcached' => array(
//     'driver' => 'memcached',
//     'lifetime' => 0,
//     'option' => array(),
//     'server' => array(
//         array('host' => '127.0.0.1', 'port' => '11211', 'weight' => 0)
//      )
//    )
);

// 站点名称
$config['site_name'] = 'RealEff';
// 系统日志文件目录
//$config['log_file_path'] = '';
// 系统临时文件目录
//$config['tmp_file_path'] = '';

// 锁定范围IP地址列表
$config['blocked_ips'] = array('192.168.1.1/24', '10.0.10.0/24', '202.101.25.*');
// 检查DDOS攻击
$config['ddos_check'] = TRUE;
// 禁止机器人访问
$config['robot_disable'] = TRUE;
// 机器人列表
$config['robots'] = array(
    /* The most common ones. */
    'Googlebot',
    'msnbot',
    'Slurp',
    'Yahoo',
    /* The rest alphabetically. */
    'Arachnoidea',
    'ArchitextSpider',
    'Ask Jeeves',
    'B-l-i-t-z-Bot',
    'Baiduspider',
    'BecomeBot',
    'cfetch',
    'ConveraCrawler',
    'ExtractorPro',
    'FAST-WebCrawler',
    'FDSE robot',
    'fido',
    'geckobot',
    'Gigabot',
    'Girafabot',
    'grub-client',
    'Gulliver',
    'HTTrack',
    'ia_archiver',
    'InfoSeek',
    'kinjabot',
    'KIT-Fireball',
    'larbin',
    'LEIA',
    'lmspider',
    'Lycos_Spider',
    'Mediapartners-Google',
    'MuscatFerret',
    'NaverBot',
    'OmniExplorer_Bot',
    'polybot',
    'Pompos',
    'Scooter',
    'Teoma',
    'TheSuBot',
    'TurnitinBot',
    'Ultraseek',
    'ViolaBot',
    'webbandit',
    'www.almaden.ibm.com/cs/crawler',
    'ZyBorg'
);
// 自定义加载安全检查文件
$config['custom_firewall_php'] = '';
// 支持移动设备访问
$config['mobile_enable'] = TRUE;

// 支持移动平台
// 机器人访问
// 限制IP访问

// 包含Realeff Define文件
//include_once SYS_ROOT .'/define.php';
