<?php
/**
 * 这是系统运行所需要的引导文件，定义了系统启动时所需要的函数。
 * 
 * 数据库、会话、用户、缓存、文件、变量 、请求、业务、输出
 */


/**
 * 系统版本
 */
define('VERSION', '1.0');

/**
 * 最低PHP版本要求
 */
define('RESYS_PHP_MINIMUM_VERSION', '5.2.4');

/**
 * 最低PHP内存使用要求
 */
define('RESYS_PHP_MINIMUM_MEMORY', '32M');

/**
 * RealEff系统根目录。
 */
if (!defined('RESYS_ROOT')) {
  define('RESYS_ROOT', substr(dirname(__FILE__), 0, -9));
}


/**
 * 启动指定名称的计时器,如果你开始和停止多次计时，测量时间间隔会积累起来。
 *
 * @param string $name 计时器名称
 */
function timer_start($name) {
  global $timers;

  $timers[$name]['start'] = microtime(TRUE);
  $timers[$name]['count'] = isset($timers[$name]['count']) ? ++$timers[$name]['count'] : 1;
}

/**
 * 读取指定名称的计时器没有停止时的计数值。
 *
 * @param string $name 计时器名称
 *
 * @return integer
 *   指定计时器计数值(毫秒)
 */
function timer_read($name) {
  global $timers;

  if (isset($timers[$name]['start'])) {
    $stop = microtime(TRUE);
    $diff = round(($stop - $timers[$name]['start']) * 1000, 2);

    if (isset($timers[$name]['time'])) {
      $diff += $timers[$name]['time'];
    }
    return $diff;
  }
  return $timers[$name]['time'];
}

/**
 * 停止指定名称的计时器。
 *
 * @param string $name 计时器名称
 *
 * @return array
 *   计时器数组。这个数组包括计时累计次数、停止次数和累计的计数值（毫秒）。
 */
function timer_stop($name) {
  global $timers;

  if (isset($timers[$name]['start'])) {
    $stop = microtime(TRUE);
    $diff = round(($stop - $timers[$name]['start']) * 1000, 2);
    if (isset($timers[$name]['time'])) {
      $timers[$name]['time'] += $diff;
    }
    else {
      $timers[$name]['time'] = $diff;
    }
    unset($timers[$name]['start']);
  }

  return $timers[$name];
}

/**
 * 统一静态变量
 * 
 * @param string $name 变量名
 * @param mixed $init_value 初始值
 * 
 * @return mixed
 */
function &realeff_static($name, $init_value = NULL) {
static $statics = array();

  if (!isset($statics[$name])) {
    $statics[$name] = $init_value;
  }

  if (isset($name)) {
    return $statics[$name];
  }

  return $statics;
}

/**
 * 初始化环境变量
 */
function _realeff_init_env_vars() {
  if (!isset($_SERVER['REQUEST_TIME'])) {
    $_SERVER['REQUEST_TIME'] = time();
  }
  if (!isset($_SERVER['HTTP_REFERER'])) {
    $_SERVER['HTTP_REFERER'] = '';
  }
  if (!isset($_SERVER['SERVER_PROTOCOL']) || 
      ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0')) {
    $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
  }
  if ((isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') || getenv('SSL_PROTOCOL_VERSION')) {
    $_SERVER['HTTPS'] = 'on';
    $_ENV['https'] = TRUE;
  }
  else {
    $_SERVER['HTTPS'] = 'off';
    $_ENV['https'] = FALSE;
  }
  $_SERVER['HTTP_HOST'] = (isset($_SERVER['HTTP_HOST']) ? strtolower($_SERVER['HTTP_HOST']) : '');
  
   $http_protocol = $_ENV['https'] ? 'https' : 'http';
   $_ENV['base_url'] = $_ENV['base_root'] = $http_protocol . '://' . $_SERVER['HTTP_HOST'];
  
   if ($path = dirname($_SERVER['SCRIPT_NAME'])) {
     $_ENV['base_path'] = rtrim($path, '/');
     $_ENV['base_url'] .= $path;
     $_ENV['base_path'] .= '/';
   }
   else {
     $_ENV['base_path'] = '/';
   }
}

/**
 * 引导程序初始化RealEff运行环境支持
 */
function _realeff_init_env() {
  // 默认设置PHP的错误报告级别，DEBUG模式下设置为E_ALL级别。
  if (defined('RESYS_DEBUG')) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'on');
  }
  else {
    error_reporting(E_COMPILE_ERROR | E_PARSE | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR | E_CORE_WARNING | E_USER_ERROR);
  }
  
  // RealEff接管PHP错误信息处理
  set_error_handler('_realeff_error_handler');
  set_exception_handler('_realeff_exception_handler');
  
  // 设置RealEff系统的PHP运行环境
  ini_set('magic_quotes_runtime', '0');
  ini_set('session.use_cookies', '1');
  ini_set('session.use_only_cookies', '1');
  ini_set('session.use_trans_sid', '0');
  ini_set('session.cache_limiter', 'none');
  ini_set('session.cookie_httponly', '1');
  
  // 设置时间、时间区、字符编码
  setlocale(LC_ALL, 'C');
  
  // RealEff接管PHP自动加载class和interface方法
  //spl_autoload_register('_realeff_autoload_class');
  //spl_autoload_register('_realeff_autoload_interface');
  
  // 检查PHP版本支持
  //if (version_compare(phpversion(), RESYS_PHP_MINIMUM_VERSION) < 0) {
    // 输出警告信息
    //trigger_error('', E_CORE_WARNING);
  //}
  
  // 清除文件状态缓存
  clearstatcache();
  
  // 初始化环境变量
  $_ENV = array();
  
  _realeff_init_env_vars();
  
  // 切换到系统工作根目录
  //chdir(RESYS_ROOT);
}


/**
 * 获取config.php中配置multisites的当前站点序号和目录等信息
 * 
 * @return array
 */
function _realeff_site_config() {
global $multisites;
  $site = array(
      'serial' => 0,
      'directory' => '',
      );
  
  $script_name = $_ENV['base_path'];
  $http_host = strtok($_SERVER['HTTP_HOST'], ':');
  $http_port = $_SERVER['SERVER_PORT'];
  
  $script_uris = array();
  //$script_uris[] = $http_host.':'.$http_port.$script_name;
  $script_uris[] = $http_host.':'.$http_port;
  //$script_uris[] = $http_host.$script_name;
  $script_uris[] = $http_host;
  foreach ($multisites as $pattern => $info) {
    $pattern_quoted = preg_quote($pattern, '/');
    if (preg_grep('/^' . str_replace('\\*', '.*', $pattern_quoted) . '$/', $script_uris)) {
      $site = $info + $site;
      break;
    }
  }
  
  return $site;
}

/**
 * 引导程序初始化RealEff系统配置
 */
function _realeff_init_config() {
global $databases, $datatables, $cache_devnum, $multisites, $auth_key, $cookie_domain, $library_dir, $extension_dir, $config;

  // 允许设置以下变量
  $databases = $datatables = $multisites = $config = array();
  $library_dir = $extension_dir = $cookie_domain = $auth_key = '';
  $cache_devnum = 1;

  if (file_exists(RESYS_ROOT .'/config.php')) {
    include_once RESYS_ROOT .'/config.php';
  }
  
  // 允许在config.php文件夹指定第三方应用提供的API类库的具体位置
  if ($library_dir && file_exists($library_dir)) {
    $library_dir = realpath($library_dir);
  }
  else {
    $library_dir = RESYS_ROOT .'/libraries';
  }
  
  // 允许在config.php文件中指定扩展目录的具体位置
  if ($extension_dir && file_exists($extension_dir)) {
    $extension_dir = realpath($extension_dir);
  }
  else {
    $extension_dir = RESYS_ROOT .'/extension';
  }
  
  // 自动加载第三方应用API库
  spl_autoload_register('_realeff_autoload_library');
  
  // 设置站点序号及站点工作空间
  $_ENV['serial'] = 0;
  $_ENV['workspace'] = RESYS_ROOT .'/workspace';
  if ($multisites) {
    $site = _realeff_site_config();
    // 站点序号（编码）
    $_ENV['serial'] = $site['serial'];
    if ($site['directory'] && is_dir($site['directory'])) {
      $_ENV['workspace'] = realpath($site['directory']);
    }
  }
  
  // 如果站点目录下建立了settings.php配置文件，则加载此文件。
  if (file_exists($_ENV['workspace'] .'/settings.php')) {
    include_once $_ENV['workspace'] .'/settings.php';
  
    $_ENV['include_settings_php'] = TRUE;
  }

  // 检查存储介质是否配置，如果没有配置，则引导安装程序。
  
  // 加载站点配置信息（config缓存）
  
  // 处理多站点cookie共享问题，区分https模式，防止cookie泄漏。
  if ($cookie_domain) {
    $session_name = $cookie_domain;
  }
  
  // 检查客户移动平台(mobile)
  
  // 检查机器人访问(robot)
  
  // 客户平台检查
  
  // 初始化服务端和客户端信息
  /**
   * HTTP_HOST 
   * SERVER_NAME 
   * SERVER_ADDR
   * SERVER_PORT 
   * REMOTE_ADDR 
   * DOCUMENT_ROOT
   * SERVER_ADMIN 
   * SCRIPT_FILENAME 
   * GATEWAY_INTERFACE 
   * REQUEST_METHOD 
   * QUERY_STRING 
   * REQUEST_URI 
   * SCRIPT_NAME 
   * HTTP_USER_AGENT 
   * HTTP_ACCEPT 
   * HTTP_ACCEPT_LANGUAGE
   * HTTP_ACCEPT_ENCODING
   * HTTP_ACCEPT_CHARSET 
   * HTTP_CONNECTION 
   * HTTP_COOKIE 
   * SERVER_SIGNATURE 
   * SERVER_SOFTWARE 
   * 
   */
  /**
   * 服务端软件(Apache、Nginx、Lighttpd、Tomcat、IBM WebSphere、IIS)
   * 服务端软件版本(version)
   * HTTP主机(SERVER_NAME、HTTP_HOST)
   * HTTP端口(SERVER_NAME)
   * 主文档目录(DOCUMENT_ROOT)
   * 执行的文件名(SCRIPT_FILENAME、SCRIPT_NAME)
   * ...
   */
  /**
   * 客户端平台(Windows、Mac、iOS、Linux、)
   * 客户浏览器(IE、Firefox、chrome、Safri、Opera、)
   * 客户浏览器版本(version)
   * 是否移动平台(mobile)
   * 是否机器人访问(robot)
   * 是否安全链接(https)
   */
  

  // 初始化系统配置
  // http和https分别需要作何处理
  
  // 检查或初始化SESSIONID，COOKIEID等信息
}

function _realeff_init_database() {
  ;
}

function _realeff_init_session() {
  ;
}

/**
 * 引导程序
 */
function realeff_bootstrap() {

  // 初始化系统运行环境
  _realeff_init_env();
  // 启动引导程序计时
  timer_start('bootstrap');

  // 初始化系统配置
  _realeff_init_config();

  // 初始化数据访问（访问介质有数据库、文件、内存、第三方数据）
  _realeff_init_database();

  // 初始化会话（session、http、cli、cgi、soap、ajax等））
  _realeff_init_session();

  // 读取系统缓存内容

  // 初始化系统变量

  // 初始化语言

  // 初始化系统核心框架

  // 停止引导程序计时
  timer_stop('bootstrap');

  return TRUE;
}


/**
 * RealEff错误处理
 *
 * @param int $level 错误级别
 * @param string $message 错误消息
 * @param string $filename 产生错误的文件名
 * @param int $line 产生错误的所在文件行号
 * @param array $context 这是一个数组，指出在产生错误时活动的标志数据。
 */
function _realeff_error_handler($level, $message, $filename, $line, $context) {
  print $message ."\n";
  print_r($context);
}

/**
 * RealEff异常处理
 *
 * 在try/catch块外产生未捕获到的异常，他们总是致命的（退出异常处理程序将立即停止执行程序）。
 *
 * @param object $exception 抛出的异常对象信息
 */
function _realeff_exception_handler($exception) {
  print_r($exception) ;
}

/**
 * 读取系统php缓存数据
 * 
 * @param string $name 数据名
 * 
 * @return array
 */
function _realeff_read_phpdata($name) {
  $filename = RESYS_ROOT ."/cache/{$name}.php";
  
  $data = array();
  if (file_exists($filename)) {
    $data = @include $filename;
  
    if (!is_array($data)) {
      $data = array();
    }
  }
  
  return $data;
}

/**
 * 写入系统php缓存数据
 * 
 * @param string $name 数据名
 * @param mixed $data 存储的数据
 */
function _realeff_write_phpdata($name, $data) {
  $filename = RESYS_ROOT ."/cache/{$name}.php";
  
  file_put_contents($filename, '<?php return ' . var_export($data, true) . ';', LOCK_EX);
}

/**
 * 清空系统php缓存数据
 * 
 * @return boolean
 *   清除php缓存数据成功返回TRUE，失败返回FALSE。
 */
function _realeff_clear_phpdata() {
  $filemask = RESYS_ROOT .'/cache/*.php';
  
  return array_map( "unlink", glob($filemask));
}

/**
 * 自动加载第三方应用API库
 * 
 * @param string $library 要加载的库名
 * @return 如果API库存在返回TRUE，否则返回FALSE。
 */
function _realeff_autoload_library($library) {
global $library_dir;

  if (class_exists($library, FALSE) || interface_exists($library, FALSE)) {
    return ;
  }
  
  $libraries =& realeff_static(__FUNCTION__);
  if (!isset($libraries)) {
    $libraries = _realeff_read_phpdata(__FUNCTION__);
  }
  
  if (isset($libraries[$library])) {
    if ($libraries[$library] && file_exists($library_dir ."/{$libraries[$library]}"))
      include_once $library_dir ."/{$libraries[$library]}";
  }
  else {
    $libraries[$library] = "{$library}/{$library}.php";
    
    // 检查library_dir目录下是否存在此类库
    if (file_exists($library_dir ."/{$libraries[$library]}")) {
      include_once $library_dir ."/{$libraries[$library]}";
    }
    else {
      $libraries[$library] = FALSE;
      
      $paths = array();
      $files = explode('_', $library);
      while (count($files) > 1) {
        
        $path = array_shift($files);
        if ($path) {
          $paths[] = $path;
        }
        else {
          continue;
        }
        
        $filename =  implode('/', $paths) .'/'. implode('_', $files). '.php';
        if (file_exists($library_dir .'/'. $filename)) {
          include_once $library_dir .'/'. $filename;
          $libraries[$library] = $filename;
          break;
        }
        else if (count($paths) > 1) {
          $filename = implode('_', $paths) .'/'. implode('_', $files). '.php';
          if (file_exists($library_dir .'/'. $filename)) {
            include_once $library_dir .'/'. $filename;
            $libraries[$library] = $filename;
            break;
          }
        }
      }
    }
    
    _realeff_write_phpdata(__FUNCTION__, $libraries);
  }
}

/**
 * 自动确认加载接口文件
 *
 * @param string $interface 检查和加载的接口名
 * @return 如果接口是可用的返回TRUE，否则返回FALSE。
 */
function _realeff_autoload_interface($interface) {
  return FALSE;
}

/**
 * 自动确认加载类文件
 *
 * @param string $class 检查和加载的类名
 * @return 如果类是可用的返回TRUE，否则返回FALSE。
 */
function _realeff_autoload_class($class) {
  print $class;
  return FALSE;
}

