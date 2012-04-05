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

if (!defined('REALEFF_DEBUG')) {
  define('REALEFF_DEBUG', FALSE);
}

if (!defined('REALEFF_DEBUG_LOG')) {
  define('REALEFF_DEBUG_LOG', FALSE);
}


include_once REALEFF_ROOT .'/includes/storage/store.php';

define('REALEFF_QUERIER_SYSTEM', 'system');

define('REALEFF_QUERIER_LOCK', 'lock');

define('REALEFF_QUERIER_CACHE', 'cache');

define('REALEFF_QUERIER_SESSION', 'session');

include_once REALEFF_ROOT .'/includes/cache/cache.php';

define('CACHE_BIN_FIREWALL', 'cache_firewall');

define('CACHE_BIN_SESSION', 'cache_session');


abstract class Realeff {

  const BOOTSTRAP_CONFIGURATION = 0x3; // 0x001 + 0x002

  const BOOTSTRAP_FIREWALL = 0x7; // 0x001 + 0x002 + 0x004

  const BOOTSTRAP_REQUEST = 0xB; // 0x001 + 0x002 + 0x008

  const BOOTSTRAP_DATABASE = 0x13; // 0x001 + 0x002 + 0x010

  const BOOTSTRAP_CACHE = 0x23; // 0x001 + 0x002 + 0x020

  const BOOTSTRAP_SESSION = 0x41; // 0x001 + 0x040

  const BOOTSTRAP_VARIABLE = 0xA3; // 0x001 + 0x002 + 0x20 + 0x080

  const BOOTSTRAP_LANGUAGE = 0x103; // 0x001 + 0x002 + 0x100

  const BOOTSTRAP_PAGE_HEADER = 0x201; // 0x001 + 0x200

  const BOOTSTRAP_FINISH = 0x7FE; // 0x001 + 0x002 + 0x010 + 0x020 + 0x040 + 0x080 + 0x100 + 0x200 + 0x400

  const BOOTSTRAP_FULL = 0xFFF; // 0xFFFF

  /**
   * 计时器
   */
  protected static $timers = array();

  /**
   * 配置已修改
   */
  protected static $config_modifed = FALSE;

  /**
   * 启动指定名称的计时器,如果你开始和停止多次计时，测量时间间隔会积累起来。
   *
   * @param string $name 计时器名称
   */
  final public static function timer_start($name) {
    self::$timers[$name]['start'] = microtime(TRUE);
    self::$timers[$name]['count'] = isset(self::$timers[$name]['count']) ? ++self::$timers[$name]['count'] : 1;
  }

  /**
   * 读取指定名称的计时器没有停止时的计数值。
   *
   * @param string $name 计时器名称
   *
   * @return integer
   *   指定计时器计数值(毫秒)
   */
  final public static function timer_read($name) {
    if (isset(self::$timers[$name]['start'])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - self::$timers[$name]['start']) * 1000, 2);

      if (isset(self::$timers[$name]['time'])) {
        $diff += self::$timers[$name]['time'];
      }
      return $diff;
    }
    return self::$timers[$name]['time'];
  }

  /**
   * 停止指定名称的计时器。
   *
   * @param string $name 计时器名称
   *
   * @return array
   *   计时器数组。这个数组包括计时累计次数、停止次数和累计的计数值（毫秒）。
   */
  final public static function timer_stop($name) {
    if (isset(self::$timers[$name]['start'])) {
      $stop = microtime(TRUE);
      $diff = round(($stop - self::$timers[$name]['start']) * 1000, 2);
      if (isset(self::$timers[$name]['time'])) {
        self::$timers[$name]['time'] += $diff;
      }
      else {
        self::$timers[$name]['time'] = $diff;
      }
      unset(self::$timers[$name]['start']);
    }

    return self::$timers[$name];
  }

  /**
   * 启动Realeff系统引导程序
   */
  public static function bootstrap($service) {
    static $stored_bootstrap = self::BOOTSTRAP_FULL;

    $bootstrap = 0x001;
    $service &= $stored_bootstrap;

    // 启动引导程序计时器
    self::timer_start('bootstrap');

    while ($service >= $bootstrap) {
      if (!($service & $bootstrap)) {
        $bootstrap <<= 1;
        continue;
      }

      $stored_bootstrap &= ~$bootstrap;
      switch ($bootstrap) {
        case 0x001:
          // 实始化系统运行环境
          self::environment_initialize();
          break;

        case 0x002:
          // 加载系统配置
          self::bootstrap_configuration();
          break;

        case 0x004:
          // 启动系统简单防卫
          if (!REALEFF_DEBUG) {
            self::bootstrap_firewall();
          }
          break;

        case 0x008:
          // 检查客户端请求数据
          self::bootstrap_request();
          break;

        case 0x010:
          // 初始化数据库（访问介质有数据库、文件、内存、第三方数据）
          self::bootstrap_database();
          break;

        case 0x020:
          // 启动系统缓存
          self::bootstrap_cache();
          break;

        case 0x040:
          self::session_initialize();
          break;

        case 0x080:
          self::variable_initialize();
          break;

        case 0x100:
          self::language_initialize();
          break;

        case 0x200:
          self::page_header();
          break;

        case 0x400:
          self::bootstrap_finish();
          break;
      }

      $bootstrap <<= 1;
    }

    // 停止引导程序计时器
    self::timer_stop('bootstrap');
  }

  /**
   * RealEff系统运行环境初始化
   */
  protected static function environment_initialize() {
    // 设置默认错误警告级别，DEBUG调试模式下打开E_ALL级别并显示错误信息。
    // 正常工作模式下打开错误报告及核心警告级别。
    if (REALEFF_DEBUG) {
      error_reporting(E_ALL);
      ini_set('display_errors', 'on');

      if (REALEFF_DEBUG_LOG) {
        // 打开调试日志功能，将错误信息写入系统目录下的日志文件中。
        ini_set( 'log_errors', 1);
        ini_set( 'error_log', REALEFF_ROOT . '/data/logs/debug.log');
      }
    }
    else {
      error_reporting(E_COMPILE_ERROR | E_PARSE | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR | E_CORE_WARNING | E_USER_ERROR);
    }

    // 设置RealEff系统运行安全模式
    ini_set('magic_quotes_runtime', '0');
    ini_set('session.use_cookies', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cache_limiter', 'none');
    ini_set('session.cookie_httponly', '1');

    // 设置时间、时间区、字符编码
    setlocale(LC_ALL, 'C');

    // 检查RealEff系统平台版本
    //if (version_compare(phpversion(), REALEFF_PHP_MINIMUM_VERSION) < 0) {
      // 输出警告信息
      //trigger_error('', E_CORE_WARNING);
    //}

    // 由RealEff接管PHP错误信息处理
    set_error_handler('realeff_error_handler');
    set_exception_handler('realeff_exception_handler');

    // 由RealEff接管自动加载class和interface方法
    spl_autoload_register('realeff_autoload_class');
    spl_autoload_register('realeff_autoload_interface');

    // 由RealEff系统接管关机处理
    register_shutdown_function('realeff_shutdown_handler');

    // 清除文件状态缓存
    clearstatcache();

    // 初始化环境变量
    $_ENV = array();

    // 初始化$_SERVER变量
    self::_server_variable_initialize();

    // 初始化移动设备支持
    $_ENV['mobile'] = FALSE;
    // 初始化用户浏览器环境
    $_ENV['wml'] = TRUE; // 无线标记语言
    $_ENV['html'] = TRUE; // 超文本标记语言
    $_ENV['css'] = TRUE; // 风格样式表
    $_ENV['images'] = TRUE; // 支持图片显示
    $_ENV['frames'] = FALSE; // 支持嵌入框架
    $_ENV['iframes'] = FALSE; // 支持框架中再嵌入框架内容
    $_ENV['xhtml'] = FALSE; // 支持xhtml标签
    $_ENV['svg'] = FALSE; // 可缩放矢量图形
    $_ENV['javascript'] = FALSE;

    // 切换到系统工作根目录
    chdir(REALEFF_ROOT);
  }

  private static function _server_variable_initialize() {
    if (!isset($_SERVER['REQUEST_TIME'])) {
      $_SERVER['REQUEST_TIME'] = time();
    }
    if (!isset($_SERVER['HTTP_REFERER'])) {
      $_SERVER['HTTP_REFERER'] = '';
    }
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
      $_SERVER['HTTP_USER_AGENT'] = '';
    }
    if (!isset($_SERVER['HTTP_ACCEPT'])) {
      $_SERVER['HTTP_ACCEPT'] = '';
    }
    // 获取请求信息头中的用户代理信息
    $_ENV['agent'] = strtolower($_SERVER['HTTP_USER_AGENT']);
    $_ENV['accept'] = strtolower($_SERVER['HTTP_ACCEPT']);

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
   * 引导程序加载RealEff系统配置
   */
  protected static function bootstrap_configuration() {
    global $databases, $dataquerier, $multisites, $config, $auth_key, $cookie_domain;
    global $library_dir, $extension_dir;

    // 初始化以下数组变量
    $databases = array();
    $dataquerier = array();
    $multisites = array();
    $config = array();
    $auth_key = '';

    $library_dir = $extension_dir = NULL;

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
    //spl_autoload_register('_realeff_autoload_library');

    // 设置站点序号及站点工作空间
    $_ENV['serial'] = 0;
    $_ENV['workspace'] = REALEFF_ROOT .'/workspace';
    if ($multisites) {
      $site = self::_load_site_config();
      // 站点序号（编码）
      $_ENV['serial'] = $site['serial'];
      if ($site['directory'] && is_dir($site['directory'])) {
        $_ENV['workspace'] = realpath($site['directory']);
      }
    }

    // 初始化站点设置
    self::_setting_initialize();

    // 加载系统config数据
    $config += self::loadData('config');

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

  private static function _load_site_config() {
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

  private static function _setting_initialize() {
    global $databases, $dataquerier, $setting, $cookie_domain, $auth_key;

    $setting = array();

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
   * 获取系统数据文件名称
   *
   * @param string $name 数据名
   * @param string $type 数据类型
   *
   * @return string
   */
  private static function _data_filename($name, $type = NULL) {
    if (isset($type)) {
      return REALEFF_ROOT ."/data/{$type}/{$name}.php";
    }

    return REALEFF_ROOT ."/data/{$name}.php";
  }

  /**
   * 清空Realeff系统缓存文件数据
   *
   * @param string $type 数据类型
   *
   * @return boolean
   *   清除php缓存数据成功返回TRUE，失败返回FALSE。
   */
  private static function _clear_data_file($type = 'cache') {
    $filemask = REALEFF_ROOT ."/data/{$type}/*.php";

    return array_map( "unlink", glob($filemask));
  }

  /**
   * 读取系统数据
   *
   * @param string $name 数据名
   * @param string $type 数据类型
   *
   * @return array
   */
  public static function loadData($name, $type = NULL) {
    $filename = self::_data_filename($name, $type);

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
   * 写入系统数据
   *
   * @param string $name 数据名
   * @param array $data 存储的数据
   */
  public static function saveData($name, array $data, $type = NULL) {
    $filename = self::_data_filename($name, $type);

    file_put_contents($filename, '<?php return ' . var_export($data, true) . ';', LOCK_EX);
  }


  /**
   * 获取系统配置数据
   *
   * @param string $name 名称
   * @param mixed $default 准备的默认数据
   *
   * @return mixed
   *   返回系统配置数据
   */
  public static function getConfig($name, $default = NULL) {
    global $config;

    return isset($config[$name]) ? $config[$name] : $default;
  }

  /**
   * 更新系统配置数据
   *
   * @param string $name 名称
   * @param mixed $default 需要更新的数据
   */
  public static function setConfig($name, $default = NULL) {
    global $config;

    if (isset($default)) {
      $config[$name] = $default;
    }
    else {
      unset($config[$name]);
    }

    realeff_data_save('config', $config);
  }

  /**
   * 启动Realeff系统安全检查
   */
  protected static function bootstrap_firewall() {
    // 检查客户端IP访问限制
    if (realeff_is_denied(ip_address())) {
      header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
      die('Sorry, ' . check_plain(ip_address()) . ' has been banned.');
    }

    // 禁止机器人访问
    if (self::getConfig('robot_disable', FALSE)) {
      // 检查机器人访问(robot)
      if (isset($_SERVER['HTTP_USER_AGENT'])) {
        // 已知的机器人列表
        $robots = self::getConfig('robots', array());

        $agent = $_SERVER['HTTP_USER_AGENT'];

        foreach ($robots as $robot) {
          if (FALSE !== strpos($agent, $robot)) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            die('Sorry, Robot access is denied.');
          }
        }

      }
    }

    // 检查恶意访问攻击
    if (self::getConfig('ddos_check', FALSE)) {
      $key_ip = 'ddos-ip:'. ip_address();

      if (!$access = cache_increment($key_ip, 1, CACHE_BIN_FIREWALL)) {
        // 记录IP在指定时间（秒）内的访问次数
        $ddos_lifetime = self::getConfig('ddos_lifetime', 5);
        cache_set($key_ip, 1, $ddos_lifetime, CACHE_BIN_FIREWALL);
      }
      else if ($access > ($maximum = self::getConfig('ddos_maximum', 10))) {

        if ($access > (self::getConfig('ddos_attack', 1000) | $maximum)) {
          // 如果系统被攻击,则IP禁止访问10小时。
          cache_set($key_ip, $maximum + 1, 36000, CACHE_BIN_FIREWALL);
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
        die('Sorry, ' . check_plain(ip_address()) . ' has been banned.');
      }
    }

    // 检查并加载自定义安全文件
    if ($custom_php = self::getConfig('custom_firewall_php', FALSE)) {
      if (file_exists(REALEFF_ROOT .'/'. $custom_php)) {
        include_once REALEFF_ROOT .'/'. $custom_php;
      }
    }
  }

  /**
   * 引导程序检查客户端请求及提交数据
   */
  protected static function bootstrap_request() {
    // 检查globals是否开启
    if (ini_get('register_globals')) {
      // 检查输入数据GLOBALS重写尝试
      if (isset($_REQUEST['GLOBALS'])) {
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
        die('Detected GLOBALS rewrite try.');
      }

      // 这些变量不应该被unset，它们都是被系统所允许的。
      $excluded = array('GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES',
          'databases', 'dataquerier', 'multisites', 'config', 'setting', 'auth_key', 'cookie_domain',
          'library_dir', 'extension_dir');

      $input = array_merge($_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES);
      isset($_SESSION) && is_array($_SESSION) && ($input = array_merge($input, $_SESSION));
      foreach ($input as $key => $value) {
        if (!in_array($key, $excluded) && isset($GLOBALS[$key])) {
          $GLOBALS[$key] = NULL;
          unset($GLOBALS[$key]);
        }
      }
    }

    // 初始化用户代理运行环境支持
    self::_agent_initialize();


    // 支持移动设备访问
    if (self::getConfig('mobile_enable', FALSE) && $_ENV['mobile']) {
      // 初始化移动数据支持环境
  //     $_ENV['frames'] = FALSE;
  //     $_ENV['iframes'] = FALSE;
  //     $_ENV['xhtml'] = FALSE;
  //     $_ENV['svg'] = FALSE; // 可缩放矢量图形
  //     $_ENV['javascript'] = FALSE;
    }

    // 客户平台检查
    /**
     * 客户端系统(Windows、Mac、iOS、Linux、)
     * 客户端浏览器(IE、Firefox、chrome、Safri、Opera、)
     * 客户端浏览器版本(version)
     * 客户端Javascript支持及版本号
     * 是否移动平台(mobile)
     * 使用语言(language)
     * 使用字符(charset)
     * 是否机器人访问(robot)
     * 是否安全链接(https)
     */

    // 解析客户端提交的数据
    // 检查请求数据安全性
  }

  private static function _agent_initialize() {
    //   $_ENV['wml'] = TRUE; // 无线标记语言
    //   $_ENV['html'] = TRUE; // 超文本标记语言
    //   $_ENV['css'] = TRUE; // 风格样式表
    //   $_ENV['images'] = TRUE;
    //   $_ENV['frames'] = FALSE;
    //   $_ENV['iframes'] = FALSE;
    //   $_ENV['xhtml'] = FALSE;
    //   $_ENV['svg'] = FALSE; // 可缩放矢量图形
    //   $_ENV['javascript'] = FALSE;
    if ($agent = $_ENV['agent']) {
      $os = realeff_agent_os_kernel();

      // 获取浏览器及版本并检查浏览器所支持的内容格式
      if ((preg_match('|msie ([0-9.]+)|', $agent, $version)) ||
          (preg_match('|internet explorer/([0-9.]+)|', $agent, $version))) {

        $_ENV['browser'] = 'msie';
        $_ENV['browser.version'] = $version[1];

        if (FALSE !== strpos($version[1], '.')) {
          $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
          $_ENV['browser.minorver'] = (int)strtok('.');
        } else {
          $_ENV['browser.majorver'] = (int)$version[1];
          $_ENV['browser.minorver'] = 0;
        }

        // 一些移动设备的代理信息可能包含屏幕分辨率
        if (preg_match('/; (120x160|240x280|240x320|320x320)\)/', $agent)) {
          $_ENV['mobile'] = TRUE;
        }

        if ($_ENV['browser.majorver'] >= 5) {
          $_ENV['frames'] = TRUE;
          $_ENV['iframes'] = TRUE;
          $_ENV['xhtml'] = TRUE;
          $_ENV['svg'] = TRUE; // 可缩放矢量图形
          $_ENV['javascript'] = TRUE;
        }
      } else if (preg_match('|firefox[/ ]([0-9.]+)|', $agent, $version)) {
        $_ENV['browser'] = 'firefox';
        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
        $_ENV['browser.minorver'] = (int)strtok('.');

        if ($_ENV['browser.majorver'] >= 3) {
          $_ENV['frames'] = TRUE;
          $_ENV['iframes'] = TRUE;
          $_ENV['xhtml'] = TRUE;
          $_ENV['svg'] = TRUE; // 可缩放矢量图形
          $_ENV['javascript'] = TRUE;
        }
      } else if (preg_match('|chrome[/ ]([0-9.]+)|', $agent, $version)) {
        $_ENV['browser'] = 'chrome';
        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
        $_ENV['browser.minorver'] = (int)strtok('.');

        $_ENV['frames'] = TRUE;
        $_ENV['iframes'] = TRUE;
        $_ENV['xhtml'] = TRUE;
        $_ENV['svg'] = TRUE; // 可缩放矢量图形
        $_ENV['javascript'] = TRUE;
      } else if (preg_match('|opera[/ ]([0-9.]+)|', $agent, $version)) {
        $_ENV['browser'] = 'opera';
        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
        $_ENV['browser.minorver'] = (int)strtok('.');

        $_ENV['frames'] = TRUE;
        $_ENV['iframes'] = TRUE;

        if ($_ENV['browser.majorver'] >= 7) {
          $_ENV['xhtml'] = TRUE;
          $_ENV['svg'] = TRUE; // 可缩放矢量图形
          $_ENV['javascript'] = TRUE;
        }
        /* Due to changes in Opera UA, we need to check Version/xx.yy,
         * but only if version is > 9.80. See: http://dev.opera.com/articles/view/opera-ua-string-changes/ */
        if ($_ENV['browser.majorver'] == 9 && $_ENV['browser.minorver'] >= 80) {
          preg_match('|version[/ ]([0-9.]+)|', $agent, $version);

          $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
          $_ENV['browser.minorver'] = (int)strtok('.');
        }
      } else if (preg_match('|konqueror/([0-9]+)|', $agent, $version) ||
          preg_match('|safari/([0-9]+)\.?([0-9]+)?|', $agent, $version)) {
        $_ENV['browser'] = 'konqueror';
        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)$version[1];
        if (isset($version[2])) {
          $_ENV['browser.minorver'] = (int)$version[2];
        }

        $_ENV['frames'] = TRUE;
        if (FALSE !== strpos($agent, 'safari') && $_ENV['browser.majorver'] >= 60) {
          $_ENV['browser'] = 'safari';

          $_ENV['iframes'] = TRUE;
          $_ENV['xhtml'] = TRUE;
          $_ENV['javascript'] = TRUE;

          if ($_ENV['browser.majorver'] > 522) {
            $_ENV['svg'] = TRUE;
          }

          // Set browser version, not engine version
          preg_match('|version[/ ]([0-9.]+)|', $agent, $version);
          $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
          $_ENV['browser.minorver'] = (int)strtok('.');
        } else {
          $_ENV['javascript'] = TRUE;
          if ($_ENV['browser.majorver'] >= 3) {
            $_ENV['iframes'] = TRUE;
          }
        }
      } else if (preg_match('|mozilla/([0-9.]+)|', $agent, $version)) {
        $_ENV['browser'] = 'mozilla';
        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)strtok($version[1], '.');
        $_ENV['browser.minorver'] = (int)strtok('.');

        $_ENV['frames'] = TRUE;
        switch ($_ENV['browser.majorver']) {
          case 5:
            $_ENV['iframes'] = TRUE;
            $_ENV['xhtml'] = TRUE;
            $_ENV['javascript'] = TRUE;

            if (preg_match('|rv:(.*)\)|', $agent, $revision)) {
              if ($revision[1] >= 1.5) {
                $_ENV['svg'] = TRUE;
              }
            }
            break;
        }
      } else if (preg_match('|amaya/([0-9.]+)|', $agent, $version)) {
        $_ENV['browser'] = 'amaya';

        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)$version[1];
        if (isset($version[2])) {
          $_ENV['browser.minorver'] = (int)$version[2];
        }

        $_ENV['frames'] = TRUE;
        $_ENV['iframes'] = TRUE;
        $_ENV['xhtml'] = TRUE;
        $_ENV['javascript'] = TRUE;

        if ($_ENV['browser.majorver'] > 1) {
          $_ENV['svg'] = TRUE;
        }
      } else if (preg_match('|w3c_validator/([0-9.]+)|', $agent, $version)) {
        $_ENV['browser'] = 'w3c';

        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)$version[1];
        if (isset($version[2])) {
          $_ENV['browser.minorver'] = (int)$version[2];
        }

        $_ENV['frames'] = TRUE;
        $_ENV['iframes'] = TRUE;
        $_ENV['xhtml'] = TRUE;
        $_ENV['svg'] = TRUE; // 可缩放矢量图形
        $_ENV['javascript'] = TRUE;
      } else if (preg_match('|antfresco/([0-9]+)|', $agent, $version)) {
        $_ENV['browser'] = 'fresco';

        $_ENV['browser.version'] = $version[1];

        $_ENV['browser.majorver'] = (int)$version[1];

        $_ENV['frames'] = TRUE;
        $_ENV['javascript'] = TRUE;
      } else if (FALSE !== strpos($agent, 'avantgo')) {
        $_ENV['mobile'] = TRUE;

        $_ENV['browser'] = 'avantgo';
        $_ENV['images'] = FALSE;
      } else if (preg_match('|lynx/([0-9]+)|', $agent, $version)) {
        $_ENV['browser'] = 'lynx';
        $_ENV['browser.version'] = $version[1];

        $_ENV['images'] = FALSE;
      } else if (preg_match('|links \(([0-9]+)|', $agent, $version)) {
        $_ENV['browser'] = 'links';
        $_ENV['browser.version'] = $version[1];

        $_ENV['images'] = FALSE;
      } else if (preg_match('|hotjava/([0-9]+)|', $agent, $version)) {
        $_ENV['browser'] = 'hotjava';
        $_ENV['browser.version'] = $version[1];
      }
      else {
        $accept = $_ENV['accept'];
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
          $_ENV['mobile'] = TRUE;

          $_ENV['browser'] = 'wap';
        }
        else if (FALSE !== strpos($accept,'text/vnd.wap.wml') ||
            FALSE !== strpos($accept,'application/vnd.wap.xhtml+xml')) {
          $_ENV['mobile'] = TRUE;

          $_ENV['browser'] = 'wap';
          if (FALSE !== strpos($accept,'xhtml')) {
            $_ENV['xhtml'] = TRUE;
          }
        }
        else {
          $devices = array(
            'android'       => 'android',
            'blackberry'    => 'blackberry',
            'iphone'        => '(iphone|ipod)',
            'opera'         => '(mobileexplorer|openwave|opera mini|opera mobi|operamini)',
            'palm'          => '(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)',
            'windows'       => 'windows ce; (iemobile|ppc|smartphone)',
            'generic'       => '(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap)'
          );

          foreach ($devices as $device => $regexp)
            if (preg_match('/' . $regexp . '/i', $agent, $browser)) {
            $_ENV['mobile'] = TRUE;

            $_ENV['browser'] = $device;

            if ($device != 'generic') {
              $_ENV['frames'] = TRUE;
              $_ENV['xhtml'] = TRUE;
              $_ENV['svg'] = TRUE; // 可缩放矢量图形
              $_ENV['javascript'] = TRUE;
            }
            break;
          }
        }
      }
    }
  }

  /**
   * 引导程序启动存储数据库
   */
  protected static function bootstrap_database() {
    global $databases;

    include_once REALEFF_ROOT .'/includes/querier.php';

    // 检查数据库是否配置，如果没有配置，则引导安装程序。
    if (empty($databases)) {
      // install.php执行安装程序
    }
    else {
      // 检查存储器是否正常工作
      store_ping();

      // 检查存储器版本号

      // 注册查询器名称
      store_querier_register(new SystemQuerier());
    }

    $_ENV['storage'] = TRUE;
  }

  /**
   * 引导程序启动缓存系统
   */
  protected static function bootstrap_cache() {
    // 检查页面缓存并处理
    // 检查数据缓存并处理
  }

  /**
   * 初始化Realeff系统会话
   */
  protected static function session_initialize() {
    include_once REALEFF_ROOT .'/includes/session.php';

    // 获取系统配置的有效会话时间（秒），使用此会话时间作为服务器配置的会话时间（秒）。
    $lifetime = self::getConfig('session_lifetime', 1440);
    if (ini_get('session.gc_maxlifetime') != $lifetime) {
      // 设置服务器环境最大会话时间
      ini_set('session.gc_maxlifetime', $lifetime);
    }

    if (isset($_ENV['storage'])) {
      store_querier_register(new SessionQuerier());
    }

    // 取得存储会话数据的处理程序名称，如果未配置或者配置的处理程序无法打开，则使用默认的files存储处理程序。
    $handler = self::getConfig('session_handler');
    if (isset($handler) && class_exists(ucfirst($handler) .'Session', FALSE)) {
      // 此会话存储类必须在类构造方法中使用session_set_save_handler()方法绑定open,close,read,write,destroy,gc方法。
      $class = ucfirst($handler) .'Session';
      $session = new $class($lifetime);
    }
    else {
      // 设置会话默认存储处理程序
      ini_set('session.save_handler', 'files');
    }

    // 检查session id是否安全有效，经过hash_base64编码后的session id只接收[a-zA-Z0-9_]及-字符，其它
    // 字符都被认为是非法注入字符（不安全的）。
    if (!empty($_COOKIE[session_name()]) && !preg_match('/[^\w\-]+/', $_COOKIE[session_name()])) {
      // 如果session cookie存在，则完成session初始化。
      // 匿名用户是不使用session cookie的，除非$_SESSION已经存在数据。
      realeff_session_start();
    }
    else {
      // 为匿名用户生成随机session id
      session_id(realeff_hash_base64(uniqid(mt_rand(), TRUE)));
    }
  }

  /**
   * 初始化Realeff系统变量
   */
  protected static function variable_initialize() {
    global $setting;

    $setting = variable_initialize($setting);
  }

  /**
   * 初始化Realeff系统语言
   */
  protected static function language_initialize() {}

  /**
   * 引导程序设置页面信息头
   */
  protected static function page_header() {}

  /**
   * Realeff系统引导程序完成启动
   */
  protected static function bootstrap_finish() {}
}


/**
 * 启动Realeff系统引导程序
 */
function realeff_bootstrap($service = Realeff::BOOTSTRAP_FULL) {
  Realeff::bootstrap($service);
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
function realeff_error_handler($level, $message, $filename, $line, $context) {
  print $message ."\n";
  //print_r($context);
}

/**
 * RealEff异常处理
 *
 * 在try/catch块外产生未捕获到的异常，他们总是致命的（退出异常处理程序将立即停止执行程序）。
 *
 * @param object $exception 抛出的异常对象信息
 */
function realeff_exception_handler($exception) {
  print_r($exception) ;
}

/**
 * RealEff关机处理
 *
 * 如果模块中有关机方法，则调用模块关机方法。
 */
function realeff_shutdown_handler() {
  print 'shutdown';
}


/**
 * 自动加载第三方应用API库
 *
 * @param string $library 要加载的库名
 * @return 如果API库存在返回TRUE，否则返回FALSE。
 */
function _realeff_autoload_library($library) {
global $library_dir;

  $libraries =& realeff_static(__FUNCTION__);
  if (!isset($libraries)) {
    $libraries = Realeff::loadData('library_file', 'cache');
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

    Realeff::saveData('library_file', $libraries, 'cache');
  }
}

/**
 * 自动确认加载接口文件
 *
 * @param string $interface 检查和加载的接口名
 * @return 如果接口是可用的返回TRUE，否则返回FALSE。
 */
function realeff_autoload_interface($interface) {
  if (interface_exists($interface, FALSE)) {
    return TRUE;
  }

  return _realeff_autoload_library($interface);
}

/**
 * 自动确认加载类文件
 *
 * @param string $class 检查和加载的类名
 * @return 如果类是可用的返回TRUE，否则返回FALSE。
 */
function realeff_autoload_class($class) {
  if (class_exists($class, FALSE)) {
    return TRUE;
  }

  return _realeff_autoload_library($class);
}

/**
 * 注册一个在关机时执行的函数
 *
 * 包装register_shutdown_function()以捕获抛出的异常，避免“抛出一个未知的异常”。
 *
 * @param string $callback
 *   注册这个关机函数
 * @param ...
 *   传递给这个关机函数的附加参数
 *
 * @return
 *   返回关机所需要执行函数的数组
 *
 * @see register_shutdown_function()
 */
function realeff_register_shutdown_function($callback) {
  static $callbacks = array();

  if (isset($callback)) {
    $args = func_get_args();
    array_shift($args);
    // Save callback and arguments
    $callbacks[] = array('callback' => $callback, 'arguments' => $args);
  }
  return $callbacks;
}

/**
 * 这是register_shutdown_function()用来处理RealEff系统关机方法
 */
function _realeff_shutdown_function() {
  $callbacks = &realeff_shutdown_function_register(NULL);

  // Set the CWD to DRUPAL_ROOT as it is not guaranteed to be the same as it
  // was in the normal context of execution.
  chdir(REALEFF_ROOT);

  try {
    while (list($key, $callback) = each($callbacks)) {
      call_user_func_array($callback['callback'], $callback['arguments']);
    }
  }
  catch (Exception $exception) {
  }
}

/**
 * 0123456789
 * abcdefghijklmnopqrstuvwxyz
 * ABCDEFGHIJKLMNOPQRSTUVWXYZ
 */
/**
 *
 * @param string $first
 * @param string $second
 */
function stranimate($first, $second) {

  preg_match('//', $first);
}

/**
 * 根据ID生成分表序列名称
 *
 * @param string $name 表名
 * @param string $id 编号
 * @param int $count 分割数量
 *
 * @return string 新表名
 */
function realeff_partition_table($name, $id, $count = 10) {
  return $name .'_'. strval(abs(crc32($id)) % $count + 1);
}

/**
 * 递归铺平关联的多维数组
 *
 * @param array $input
 * @param boolean $preserve_keys
 *   保留输入数组中原来的键名
 *
 * @return array
 *   返回铺平的数组
 */
function array_flatten(array $input, $preserve_keys = FALSE, &$r = array()){
  foreach($input as $key => $value){
    if (is_array($value)){
      array_flatten($value, $preserve, $r);
    }
    else if ($preserve_keys && !is_int($key)) {
      $r[$key] = $value;
    }
    else {
      $r[] = $value;
    }
  }

  return $r;
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

    if (isset($_SERVER['HTTP_CLIENT_IP']) && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/', $_SERVER['HTTP_CLIENT_IP'])) {
      $ip_address = $_SERVER['HTTP_CLIENT_IP'];
    }
    else if(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {

      $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);

      // 删除IPs空格，它们可能由“,”和空格组成分隔符。
      $forwarded = array_map('trim', $forwarded);

      foreach ($forwarded AS $xip) {
        if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
          $ip_address = $xip;
          break;
        }
      }
    }
  }

  return $ip_address;
}

/**
 * Detect whether the current script is running in a command-line environment.
 */
function realeff_is_cli() {
  return (!isset($_SERVER['SERVER_SOFTWARE']) && (php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0)));
}

/**
 * Returns a string of highly randomized bytes (over the full 8-bit range).
 *
 * This function is better than simply calling mt_rand() or any other built-in
 * PHP function because it can return a long string of bytes (compared to < 4
 * bytes normally from mt_rand()) and uses the best available pseudo-random source.
 *
 * @param $count
 *   The number of characters (bytes) to return in the string.
 */
function realeff_random_bytes($count)  {
  // $random_state does not use drupal_static as it stores random bytes.
  static $random_state, $bytes;
  // Initialize on the first call. The contents of $_SERVER includes a mix of
  // user-specific and system information that varies a little with each page.
  if (!isset($random_state)) {
    $random_state = print_r($_SERVER, TRUE);
    if (function_exists('getmypid')) {
      // Further initialize with the somewhat random PHP process ID.
      $random_state .= getmypid();
    }
    $bytes = '';
  }
  if (strlen($bytes) < $count) {
    // /dev/urandom is available on many *nix systems and is considered the
    // best commonly available pseudo-random source.
    if ($fh = @fopen('/dev/urandom', 'rb')) {
      // PHP only performs buffered reads, so in reality it will always read
      // at least 4096 bytes. Thus, it costs nothing extra to read and store
      // that much so as to speed any additional invocations.
      $bytes .= fread($fh, max(4096, $count));
      fclose($fh);
    }
    // If /dev/urandom is not available or returns no bytes, this loop will
    // generate a good set of pseudo-random bytes on any system.
    // Note that it may be important that our $random_state is passed
    // through hash() prior to being rolled into $output, that the two hash()
    // invocations are different, and that the extra input into the first one -
    // the microtime() - is prepended rather than appended. This is to avoid
    // directly leaking $random_state via the $output stream, which could
    // allow for trivial prediction of further "random" numbers.
    while (strlen($bytes) < $count) {
      $random_state = hash('sha256', microtime() . mt_rand() . $random_state);
      $bytes .= hash('sha256', mt_rand() . $random_state, TRUE);
    }
  }
  $output = substr($bytes, 0, $count);
  $bytes = substr($bytes, $count);
  return $output;
}

/**
 * Calculate a base-64 encoded, URL-safe sha-256 hmac.
 *
 * @param $data
 *   String to be validated with the hmac.
 * @param $key
 *   密钥字符
 *
 * @return
 *   返回一个基于base-64编码的SHA-256的HMAC，并且将字符+替换成字符-，字符/替换成字符_以及把字符=删除。
 */
function realeff_hmac_base64($data, $key) {
  $hmac = base64_encode(hash_hmac('sha256', $data, $key, TRUE));
  // Modify the hmac so it's safe to use in URLs.
  return strtr($hmac, array('+' => '-', '/' => '_', '=' => ''));
}

/**
 * 计算一个base-64编码，URL安全的SHA-256哈希。
 *
 * @param $data
 *   哈希字符串
 *
 * @return
 *   返回一个基于base-64编码的sha-256哈希码，并且将字符+替换成字符-，字符/替换成字符_以及把字符=删除。
 */
function realeff_hash_base64($data) {
  $hash = base64_encode(hash('sha256', $data, TRUE));
  // Modify the hash so it's safe to use in URLs.
  return strtr($hash, array('+' => '-', '/' => '_', '=' => ''));
}

/**
 * 将内容中的特殊字符转换成HTML显示字符
 *
 * 并且验证字符串为UTF-8字符，以防止跨站点脚本攻击。
 *
 * @param string $text
 *   检查并转换这个文本内容
 *
 * @return string
 *   返回一个HTML安全版本的内容，或者内容不是有效的UTF-8字符则返回空字符串。
 */
function check_plain($text) {
  return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
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
 * 加载持久变量表
 */
function variable_initialize(array $setting) {
  // 如果数据库可用，则使用数据库，否则使用数据文件
  if (!$variables = cache_get('variables', 'cache')) {
    if (isset($_ENV['storage'])) {
      $querier = store_getquerier(REALEFF_QUERIER_SYSTEM);
      $querier->do = 'getvars';

      $stmt = $querier->query();

      $variables = array();
      while ($variable = $stmt->fetchObject()) {
        $variables[$variable->name] = unserialize($variable->value);
      }

      cache_set('variables', $variables);
    }
    else {
      $variables = realeff_data_load('setting');
    }
  }

  foreach ($setting as $key => $value) {
    $variables[$key] = $value;
  }

  return $variables;
}

/**
 * 获取一个持久变量
 *
 * @param string $name 变量名
 * @param mixed $default 缺省值
 *
 * @return mixed
 *   返回这个变量的值
 */
function variable_get($name, $default = NULL) {
  global $setting;

  return isset($setting[$name]) ? $setting[$name] : $default;
}

/**
 * 设置一个持久变量
 *
 * @param string $name 变量名
 * @param mixed $value 设置变量值
 */
function variable_set($name, $value) {
  global $setting;

  if (isset($_ENV['storage'])) {
    $querier = store_getquerier(REALEFF_QUERIER_SYSTEM);
    $querier->do = 'setvar';
    $querier->setParam('name', $name);
    $querier->addParam('value', serialize($value));
    $querier->addParam('value', serialize($value));

    $querier->execute();

    // 清除缓存数据
    cache_clear_all('variables', 'cache');

    $setting[$name] = $value;
  }
  else {
    $setting[$name] = $value;
    realeff_data_save('setting', $setting);
  }
}

/**
 * 删除一个持久变量
 *
 * @param string $name 变量名
 */
function variable_del($name) {
  global $setting;

  if (isset($_ENV['storage'])) {
    $querier = store_getquerier(REALEFF_QUERIER_SYSTEM);
    $querier->do = 'delvar';
    $querier->setParam('name', $name);

    $querier->execute();

    // 清除缓存数据
    cache_clear_all('variables', 'cache');

    unset($setting[$name]);
  }
  else {
    unset($setting[$name]);
    realeff_data_save('setting', $setting);
  }
}

/**
 * 转换一个标准IP地址使用子网掩码后的网络地址
 *
 * @param string $ip 标准IP地址
 * @param mixed $mask 子网掩码
 *
 * @return int
 */
function ip2long_mask($ip, $mask = NULL) {
  $ip = strtr($ip, '*', '0');

  if (!isset($mask) && FALSE !== strpos($ip, '/')) {
    $ip = strtok($ip, '/');
    $mask = strtok('/');
  }

  if (empty($mask)) {
    return ip2long($ip);
  }

  $ip_dec = ip2long($ip);
  if (FALSE === $ip_dec) {
    return FALSE;
  }

  $mask_dec = is_numeric($mask) ? intval($mask) : ip2long($mask);
  if (FALSE === $mask_dec) {
    return FALSE;
  }

  if ($mask_dec > 0 && $mask_dec <= 32) {
    return $ip_dec & (0xFFFFFFFF << (32 - $mask_dec));
  }

  return $ip_dec & $mask_dec;
}


function realeff_is_network($ip, $network) {
  if ($ip == $network) {
    return TRUE;
  }

  if (FALSE !== strpos($network, '/')) {
    $net = strtok($network, '/');
    $mask = strtok('/');

    return ip2long_mask($ip, $mask) == ip2long_mask($net, $mask);
  }
  else if (FALSE !== strpos($network, '-')) {
    $start = trim(strtok($network, '-'));
    $start = (float)sprintf('%u', ip2long_mask($start));
    $end = trim(strtok('-'));
    $end = (float)sprintf('%u', ip2long_mask($end));
    if ($start == $end) {
      return FALSE;
    }

    $ip = (float)sprintf('%u', ip2long($ip));

    return ($ip >= $start && $ip <= $end) || ($ip >= $start && $ip == 0);
  }

  return FALSE;
}

function realeff_is_denied($ip) {
  $ips = Realeff::getConfig('blocked_ips', array());

  foreach ($ips as $network) {
    if (realeff_is_network($ip, $network)) {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * 获取用户代理操作系统核心名称
 *
 * @return string
 */
function realeff_agent_os_kernel() {
  $os =& realeff_static(__FUNCTION__);

  if (!isset($os)) {
    $os = 'unix';

    if (FALSE !== strpos($_ENV['agent'], 'wind'))
      $os = 'win';
    else if (FALSE !== strpos($_ENV['agent'], 'mac'))
      $os = 'mac';
    else if (FALSE !== strpos($_ENV['agent'], 'linux'))
      $os = 'linux';
  }

  return $os;
}
