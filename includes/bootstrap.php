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
define('REALEFF_MINIMUM_PHP_VERSION', '5.2.4');

/**
 * 最低PHP内存使用要求
 */
define('REALEFF_MINIMUM_PHP_MEMORY_LIMIT', '32M');

/**
 * RealEff系统根目录。
 */
if (!defined('REALEFF_ROOT')) {
  define('REALEFF_ROOT', substr(dirname(__FILE__), 0, -9));
}

define('REALEFF_BOOTSTRAP_CONFIGURATION', 0x3); // 0x001 + 0x002

define('REALEFF_BOOTSTRAP_FIREWALL', 0x7); // 0x001 + 0x002 + 0x004

define('REALEFF_BOOTSTRAP_REQUEST', 0xB); // 0x001 + 0x002 + 0x008

define('REALEFF_BOOTSTRAP_STORAGE', 0x13); // 0x001 + 0x002 + 0x010

define('REALEFF_BOOTSTRAP_SESSION', 0x21); // 0x001 + 0x020

define('REALEFF_BOOTSTRAP_VARIABLE', 0x43); // 0x001 + 0x002 + 0x040

define('REALEFF_BOOTSTRAP_LANGUAGE', 0x83); // 0x001 + 0x002 + 0x080

define('REALEFF_BOOTSTRAP_PAGE_HEADER', 0x101); // 0x001 + 0x100

define('REALEFF_BOOTSTRAP_FINISH', 0x3FE); // 0x001 + 0x002 + 0x010 + 0x020 + 0x040 + 0x080 + 0x100 + 0x200

define('REALEFF_BOOTSTRAP_FULL', 0xFFF); // 0xFFFF

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
function _realeff_server_variable_initialize() {
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
}

/**
 * RealEff系统运行环境初始化
 */
function _realeff_environment_initialize() {
  // 默认设置PHP的错误报告级别，DEBUG模式下设置为E_ALL级别。
  if (defined('SYS_DEBUG')) {
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
  //if (version_compare(phpversion(), REALEFF_PHP_MINIMUM_VERSION) < 0) {
    // 输出警告信息
    //trigger_error('', E_CORE_WARNING);
  //}
  
  // 清除文件状态缓存
  clearstatcache();
  
  // 初始化环境变量
  $_ENV = array();
  
  _realeff_server_variable_initialize();
  
  // 切换到系统工作根目录
  //chdir(REALEFF_ROOT);
  
  
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
//   if (!is_array($multisites)) {
//     return $site;
//   }
  
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
 * 引导程序初始化RealEff站点设置
 */
function _realeff_setting_initialize() {
global $databases, $dataquerier, $config, $cookie_domain, $auth_key;

  // 如果站点工作目录下建立了settings.php配置文件，则加载此文件。
  if (file_exists($_ENV['workspace'] .'/settings.php')) {
    include_once $_ENV['workspace'] .'/settings.php';
  }
  
  // cookie域
  if (empty($cookie_domain)) {
    // HTTP_HOST can be modified by a visitor, but we already sanitized it
    // in drupal_settings_initialize().
    if (!empty($_SERVER['HTTP_HOST'])) {
      $cookie_domain = $_SERVER['HTTP_HOST'];
      // Strip leading periods, www., and port numbers from cookie domain.
      $cookie_domain = ltrim($cookie_domain, '.');
      if (strpos($cookie_domain, 'www.') === 0) {
        $cookie_domain = substr($cookie_domain, 4);
      }
      $cookie_domain = explode(':', $cookie_domain);
      $cookie_domain = '.' . $cookie_domain[0];
    }
  }
  
  // 授权码
  if (empty($auth_key)) {
    $auth_key = md5($cookie_domain);
  }
}

/**
 * 引导程序加载RealEff系统配置
 */
function _realeff_bootstrap_configuration() {
global $databases, $dataquerier, $multisites, $config, $auth_key, $cookie_domain;
global $library_dir, $extension_dir;

  // 初始化以下数组变量
  $databases = array();
  $dataquerier = array();
  $multisites = array();
  $config = array();

  // 加载系统配置文件
  if (file_exists(REALEFF_ROOT .'/config.php')) {
    include_once REALEFF_ROOT .'/config.php';
  }
  
  // 允许在config.php文件夹指定第三方应用提供的API类库位置
  if ($library_dir && file_exists($library_dir)) {
    $library_dir = realpath($library_dir);
  }
  else {
    $library_dir = REALEFF_ROOT .'/libraries';
  }
  
  // 允许在config.php文件中指定扩展目录位置
  if ($extension_dir && file_exists($extension_dir)) {
    $extension_dir = realpath($extension_dir);
  }
  else {
    $extension_dir = REALEFF_ROOT .'/extension';
  }
  
  // 注册自动加载第三方应用API
  spl_autoload_register('_realeff_autoload_library');
  
  // 设置站点序号及站点工作空间
  $_ENV['serial'] = 0;
  $_ENV['workspace'] = REALEFF_ROOT .'/workspace';
  if ($multisites) {
    $site = _realeff_site_config();
    // 站点序号（编码）
    $_ENV['serial'] = $site['serial'];
    if ($site['directory'] && is_dir($site['directory'])) {
      $_ENV['workspace'] = realpath($site['directory']);
    }
  }
  
  // 初始化站点设置
  _realeff_setting_initialize();
  
  // 处理多站点cookie共享问题，区分https模式，防止cookie泄漏。
  if ($cookie_domain) {
    $session_name = $cookie_domain;
  }
  else {
    // Otherwise use $base_url as session name, without the protocol
    // to use the same session identifiers across http and https.
    list( , $session_name) = explode('://', $_ENV['base_url'], 2);
  }
  
  // Per RFC 2109, cookie domains must contain at least one dot other than the
  // first. For hosts such as 'localhost' or IP Addresses we don't set a cookie domain.
  if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
    ini_set('session.cookie_domain', $cookie_domain);
  }
  
  // To prevent session cookies from being hijacked, a user can configure the
  // SSL version of their website to only transfer session cookies via SSL by
  // using PHP's session.cookie_secure setting. The browser will then use two
  // separate session cookies for the HTTPS and HTTP versions of the site. So we
  // must use different session identifiers for HTTPS and HTTP to prevent a
  // cookie collision.
  if ($_ENV['https']) {
    ini_set('session.cookie_secure', TRUE);
  }
  $prefix = ini_get('session.cookie_secure') ? 'SSESS' : 'SESS';
  session_name($prefix . substr(hash('sha256', $session_name), 0, 32));
}

/**
 * 启动Realeff系统防火墙
 */
function _realeff_bootstrap_firewall() {
  
}

/**
 * 引导程序检查客户端请求及数据
 */
function _realeff_bootstrap_request() {

  // 检查客户端IP访问限制
  //realeff_block_denied
  //realeff_is_denied
  
  // 检查恶意访问攻击
  
  // 禁止机器人访问
  if (defined('DISABLE_ROBOT')) {
    // 检查机器人访问(robot)
  }
  
  // 支持移动设备访问
  if (defined('ENABLE_MOBILE')) {
    // 检查客户端移动设备(mobile)
  }
  
  // 检查输入数据安全性
  
  // 客户平台检查
  
  /**
   * 客户端系统(Windows、Mac、iOS、Linux、)
   * 客户端浏览器(IE、Firefox、chrome、Safri、Opera、)
   * 客户端浏览器版本(version)
   * 客户端Javascript支持及版本号
   * 是否移动平台(mobile)
   * 是否机器人访问(robot)
   * 是否安全链接(https)
   */
  
  // 解析客户端提交的数据
  // 检查请求数据安全性
  //$no_unset = array( 'GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', 'table_prefix' );
}

/**
 * 引导程序加载数据存储器
 */
function _realeff_bootstrap_storage() {
  
  // 检查数据库是否配置，如果没有配置，则引导安装程序。
  if (empty($databases)) {
    // install.php执行安装程序
  }
  ;
}

/**
 * 初始化Realeff系统会话
 */
function _realeff_session_initialize() {
  ;
}

/**
 * 初始化Realeff系统变量
 */
function _realeff_variable_initialize() {
  
}

/**
 * 引导程序设置页面信息头
 */
function _realeff_bootstrap_page_header() {
  
}

/**
 * 初始化Realeff系统语言
 */
function _realeff_language_initialize() {
  
}

/**
 * Realeff系统引导程序完成启动
 */
function _realeff_bootstrap_finish() {
  
}

/**
 * 启动Realeff系统引导程序
 */
function realeff_bootstrap($service) {
  static $stored_bootstrap = REALEFF_BOOTSTRAP_FULL;
  $bootstrap = 0x001;

  // 启动引导程序计时器
  timer_start('bootstrap');
  
  while ($service > $bootstrap) {
    if (!($stored_bootstrap & $service & $bootstrap)) {
      $bootstrap <<= 1;
      continue;
    }
    
    $stored_bootstrap &= ~$bootstrap;
    switch ($bootstrap) {
      case 0x001:
        // 实始化系统运行环境
        _realeff_environment_initialize();
        break;
    
      case 0x002:
        // 加载系统配置
        _realeff_bootstrap_configuration();
        break;
    
      case 0x004:
        _realeff_bootstrap_firewall();
        break;
    
      case 0x008:
        _realeff_bootstrap_request();
        break;
    
      case 0x010:
        // 初始化数据库（访问介质有数据库、文件、内存、第三方数据），实始化缓存系统
        _realeff_bootstrap_storage();
        break;
    
      case 0x020:
        _realeff_session_initialize();
        break;
    
      case 0x040:
        _realeff_variable_initialize();
        break;
    
      case 0x080:
        _realeff_language_initialize();
        break;
    
      case 0x100:
        _realeff_bootstrap_page_header();
        break;
    
      case 0x200:
        _realeff_bootstrap_finish();
        break;
    }
    
    $bootstrap <<= 1;
  }
  
  // 停止引导程序计时器
  timer_stop('bootstrap');
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
  $filename = REALEFF_ROOT ."/cache/{$name}.php";
  
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
  $filename = REALEFF_ROOT ."/cache/{$name}.php";
  
  file_put_contents($filename, '<?php return ' . var_export($data, true) . ';', LOCK_EX);
}

/**
 * 清空系统php缓存数据
 * 
 * @return boolean
 *   清除php缓存数据成功返回TRUE，失败返回FALSE。
 */
function _realeff_clear_phpdata() {
  $filemask = REALEFF_ROOT .'/cache/*.php';
  
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

/**
 * 获取客户端IP地址
 *
 * @return string
 */
function ip_address() {
  $ip_address = &realeff_static(__FUNCTION__);

  if (!isset($ip_address)) {
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (variable_get('reverse_proxy', 0)) {
      $reverse_proxy_header = variable_get('reverse_proxy_header', 'HTTP_X_FORWARDED_FOR');
      if (!empty($_SERVER[$reverse_proxy_header])) {
        // If an array of known reverse proxy IPs is provided, then trust
        // the XFF header if request really comes from one of them.
        $reverse_proxy_addresses = variable_get('reverse_proxy_addresses', array());

        // Turn XFF header into an array.
        $forwarded = explode(',', $_SERVER[$reverse_proxy_header]);

        // Trim the forwarded IPs; they may have been delimited by commas and spaces.
        $forwarded = array_map('trim', $forwarded);

        // Tack direct client IP onto end of forwarded array.
        $forwarded[] = $ip_address;

        // Eliminate all trusted IPs.
        $untrusted = array_diff($forwarded, $reverse_proxy_addresses);

        // The right-most IP is the most specific we can trust.
        $ip_address = array_pop($untrusted);
      }
    }
  }

  return $ip_address;
}
