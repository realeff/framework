<?php

/**
 * 缓存储器驱动文件根路径
 */
define('CACHE_DRIVER_PATH', dirname(__FILE__));


define('CACHE_BIN_DEFAULT', 'cache');


/**
 *
 * @author realeff
 *
 */
abstract class Cache {

  /**
   * 根据容器名称获取缓存储器
   * $config['caches'] 缓存储器定义初始化
   * 建立缓存设备连接，当使用缓存容器时激活缓存设备连接。
   * 根据缓存容器及缓存储器数量配置命中
   * Cache::getBin('cache')->
   */

  /**
   * 链接缓存储器信息
   *
   * @var array
   */
  protected static $config = array();

  /**
   * 缓存处理程序
   *
   * @var array
   */
  protected static $handler = array();

  /**
   * 加载缓存处理器配置信息
   */
  protected static function loadConfig() {
    self::$config = Realeff::getConfig('cache_handler', array());
  }

  /**
   * 添加缓存配置信息
   *
   * @param string $bin
   * @param array $config
   */
  public static function addConfig($bin, array $config) {
    if (empty(self::$config[$bin])) {
      self::$config[$bin] = $config;
    }
  }

  /**
   * 加载缓存驱动文件
   *
   * @param string $driver
   */
  protected static function loadDriver($driver) {
    static $drivers = array();

    if (isset($drivers[$driver])) {
      return ;
    }

    $filename = CACHE_DRIVER_PATH ."/{$driver}.php";

    if (file_exists($filename)) {
      require_once $filename;
    }

    $drivers[$driver] = TRUE;
  }

  /**
   * 获取缓存处理程序
   *
   * @param string $bin
   * @param array $options
   */
  protected static function getHandler($bin, array $options) {

    if (empty($options['driver']) ||
          $options['driver'] == 'database' && !isset($_ENV['storage'])) {
      $options['driver'] = 'file';
    }
    if ('database' == $options['driver'] && isset($_ENV['storage'])) {
      store_querier_register(new CacheQuerier($bin));;
    }

    // 装载驱动
    self::loadDriver($options['driver']);

    // 实例化缓存储器
    $class = ucfirst($options['driver']) .'Cache';
    $cache = new $class($bin, $options);

    // 日志功能

    return $cache;
  }

  /**
   * 取得缓存容器
   *
   * @param string $bin
   *
   * @return CacheInterface
   */
  public static function getBin($bin = CACHE_BIN_DEFAULT) {
    if (empty(self::$config)) {
      self::loadConfig();
    }

    // 如果缓存容器未配置存储器，则使用已开启的默认缓存器，或者使用文件存储器。
    if (!isset(self::$config[$bin])) {
      if (isset(self::$handler['cache'])) {
        $bin = 'cache';
      }
      else {
        self::$config[$bin] = array();
      }
    }

    // 初始化缓存处理程序
    if (!isset(self::$handler[$bin])) {
      self::$handler[$bin] = self::getHandler($bin, self::$config[$bin]);
    }

    return self::$handler[$bin];
  }

  /**
   * 移除缓存容器
   * @param string $bin
   *
   * @return boolean
   */
  public static function removeBin($bin) {
    if (!isset(self::$config[$bin])) {
      return FALSE;
    }

    unset(self::$config[$bin]);
    unset(self::$handler[$bin]);

    return TRUE;
  }

  /**
   * 清除所有缓存
   */
  public static function flush() {
    $status = FALSE;

    foreach (array_keys(self::$config) as $bin) {
      $status |= self::getBin($bin)->flush();
    }

    return $status;
  }

  /**
   * 销毁所有垃圾缓存
   */
  public static function gc() {
    $status = FALSE;

    foreach (array_keys(self::$config) as $bin) {
      $status |= self::getBin($bin)->gc();
    }

    return $status;
  }
}


/**
 * 缓存存储器接口
 *
 * @author realeff
 *
 */
interface CacheInterface {

  /**
   * 构造器
   *
   * @param string $bin 存储容器
   * @param array $options 存储参数
   */
  public function __construct($bin, array $options = array());

  /**
   * 获取一个元素数据
   *
   * @param string $key
   *
   * @return mixed
   *   返回存储元素的值或者在其他情况下返回FALSE。
   */
  public function get($key);

  /**
   * 获取多个元素数据
   *
   * @param array $keys
   *
   * @return array
   *   返回检索到的元素的数组，失败时返回FALSE。
   */
  public function getMulti(array $keys);

  /**
   * 存储一个元素数据
   *
   * @param string $key 存储键名
   * @param mixed $data 存储数据
   * @param int $lifetime 生存期
   *
   * @return boolean
   *   操作成功返回TRUE，失败返回FALSE。
   */
  public function set($key, $data, $lifetime = 0);

  /**
   * 存储多个元素数据
   *
   * @param array $items 存放键/数据对数组
   * @param int $lifetime 生成期
   *
   * @return boolean
   *   操作成功返回TRUE，失败返回FALSE。
   */
  public function setMulti(array $items, $lifetime = 0);

  /**
   * 减少数值元素的值，将指定元素的值减少offset。
   * 如果指定的key对应的元素不是数值类型并且不能被转换为数值， 会将此值修改为offset。
   *
   * @param string $key 将要减少值的元素key
   * @param int $offset 将指定元素值减少多少
   *
   * @return int
   *   返回新的元素值，失败时返回FALSE。
   */
  public function decrement($key, $offset = 1);

  /**
   * 增加数值元素的值，将指定元素的值增加offset。
   * 如果指定的key对应的元素不是数值类型并且不能被转换为数值， 会将此值修改为offset。
   *
   * @param string $key 将要增加值的元素key
   * @param int $offset 将指定元素值增加多少
   *
   * @return int
   *   返回新的元素值，失败时返回FALSE。
   */
  public function increment($key, $offset = 1);

  /**
   * 删除一个元素数据
   *
   * @return boolean
   *   成功时返回TRUE，或者在失败时返回FALSE。
   */
  public function delete($key);

  /**
   * 清除所有缓存数据
   *
   * @return boolean
   *   成功时返回TRUE，或者在失败时返回FALSE。
   */
  public function flush();

  /**
   * 销毁过期的缓存数据
   */
  public function gc();

  /**
   * 检查此缓存容器是否空的
   *
   * @return boolean
   *   如果为空返回TRUE，否则返回FALSE。
   */
  public function isEmpty();
}


/**
 * 提供一个缓存储器
 *
 * @author realeff
 *
 */
abstract class AbstractCache {

  /**
   * @var string
   */
  protected $bin;

  protected $lifetime;

  protected $time;

  /**
   * 实现一个缓存储器
   *
   * @param string $bin
   * @param array $options
   */
  public function __construct($bin, array $options) {
    // 缓存容器
    $this->bin = $bin;
    $this->lifetime = isset($options['lifetime']) ? (int)$options['lifetime'] : 0;

    $this->time = $_SERVER['REQUEST_TIME'];
  }

  /**
   * 根据缓存键名取得容器名称
   *
   * @param string $key
   *
   * @return string
   */
  //public function getBin($key) {

  //}

}

/**
 * 文件缓存
 *
 * @author realeff
 *
 */
class FileCache extends AbstractCache implements CacheInterface {

  protected $path;

  protected $secret;

  public function __construct($bin, array $options = array()) {
    parent::__construct($bin, $options);

    // 指定文件缓存目录位置，如果目录位置是{workspace}则表示指定的缓存位置是当前工作空间路径。
    if (!empty($options['path'])) {
      $this->path = str_replace('{workspace}', $_ENV['workspace'], $options['path']);
      $this->path = rtrim($this->path, '/\\');
    }
    if (empty($this->path) || !is_dir($this->path)) {
      $this->path = $_ENV['workspace'] .'/cache';
    }
    $this->path = $this->check_dir($this->path) ? realpath($this->path) : REALEFF_ROOT .'/data/cache';

    // 指定数据文件名加密码，如果指定的secret为FALSE或空，则表示缓存的是静态页面内容。
    $this->secret = isset($options['secret']) ? $options['secret'] : substr($GLOBALS['auth_key'], 0, 8);
  }

  protected function check_dir(&$dir) {
    // 如果目录不存在则创建目录
    if (!is_dir($dir)) {
      if (@mkdir($dir)) {
        @chmod($dir, 0775);
      }
      else {
        return FALSE;
      }
    }

    if (!is_writable($dir)) {
      if (!@chmod($dir, 0775)) {
        return FALSE;
      }
    }

    // 并在目录下建立index.html文件<!DOCTYPE html><title></title>
    if (!is_file("$dir/index.html")) {
      file_put_contents("$dir/index.html", '<!DOCTYPE html><title></title>');
    }

    return TRUE;
  }

  protected function getFilePath($key) {
    if (empty($key)) {
      return FALSE;
    }

    // 将key中含有“-/\”字符转换为目录分隔符DIRECTORY_SEPARATOR，并清理字符 ? “ / \ < > * | :
    $key = strtr($key, '~/\\?"\'<>*.|: ', '---__________');

    $filepath = $this->path .DIRECTORY_SEPARATOR. $this->bin;
    $this->check_dir($this->path);

    $path = strtok($key, '-');

    while ($path !== FALSE) {
      if ($path) {
        // 检查文件目录
        $this->check_dir($filepath);
        // 追加文件名
        $filepath .= DIRECTORY_SEPARATOR. $path;
      }

      $path = strtok('-');
    }

    if (empty($this->secret)) {
      return $filepath .'.html';
    }
    else {
      $filename = $this->secret .'-cache-'. basename($filepath);

      return dirname($filepath) .DIRECTORY_SEPARATOR. md5($filename) .'.php';
    }
  }

  protected function getFileMeta($filename) {
    if (is_file($filename)) {
      $handle = @fopen($filename, 'rb');
      $buffer = fread($handle, 256);
      fclose($handle);

      if (preg_match('/(.*)\r\n<\\?php die\\("Access Denied"\\); \\?>#xxx#\r\n/', $buffer, $matches)) {
        return @unserialize($matches[1]);
      }
    }

    return FALSE;
  }

  /**
   * 检查缓存文件是否过期，并删除过期的文件。
   *
   * @param string $filename
   */
  protected function check_expire($filename) {
    if (is_file($filename)) {
      $mtime = filemtime($filename);
      // 取得文件过期时间
      $expire = 0;
      if (!empty($this->secret) && ($header = $this->getFileMeta($filename))) {
        $expire = $header['expire'];
      }
      else if ($this->lifetime > 0) {
        $expire = $mtime + $this->lifetime;
      }

      if (!$mtime || ($expire > 0 && $expire < $this->time)) {
        @unlink($filename);
      }
      else {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * 清理目录中所有过期的文件或清理目录中所有的文件
   *
   * @param string $path
   * @param boolean $gc
   */
  protected function clear_dir($path, $gc = TRUE) {
    if (is_dir($path) && $handle = opendir($path)) {
      while (FALSE !== ($file = readdir($handle))) {
        if (!in_array($file, array('.', '..', '.svn', 'CVS', 'index.html')) && $file[0] != '.') {
          $this->clear_dir("$path/$file", $gc);
        }
      }
      closedir($handle);
    }
    else if (preg_match('/.*\\.php$/', $path) && is_file($path)) {
      if ($gc) {
        $this->check_expire($path);
      }
      else {
        @unlink($path);
      }
    }
  }

  /**
   *
   * @see CacheInterface::delete()
   */
  public function delete($key) {
    // TODO Auto-generated method stub
    $filename = $this->getFilePath($key);
    if (is_file($filename)) {
      return @unlink($filename);
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub
    $path = $this->path .'/'. $this->bin;

    if (is_writable($path)) {
      $this->clear_dir($path, FALSE);

      return TRUE;
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::gc()
   */
  public function gc() {
    // TODO Auto-generated method stub
    $path = $this->path .'/'. $this->bin;

    if (is_writable($path)) {
      $this->clear_dir($path, TRUE);

      return TRUE;
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::get()
   */
  public function get($key) {
    // TODO Auto-generated method stub
    $filename = $this->getFilePath($key);
    if ($this->check_expire($filename)) {
      if (file_exists($filename)) {
        $data = file_get_contents($filename);
        if ($data && !empty($this->secret)) {
          $data = preg_replace('/.*\r\n<\\?php die\\("Access Denied"\\); \\?>#xxx#\r\n/', '', $data, 1);

          return @unserialize($data);
        }

        return $data;
      }
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::getMulti()
   */
  public function getMulti(array $keys) {
    // TODO Auto-generated method stub
    $items = array();
    foreach ($keys as $key) {
      if ($data = $this->get($key)) {
        $items[$key] = $data;
      };
    }

    return $items;
  }

  protected function updateData($key, $data) {
    // TODO Auto-generated method stub
    $filename = $this->getFilePath($key);
    if (!file_exists($filename)) {
      return FALSE;
    }

    $handle = @fopen($filename, 'r+b');
    if ($handle) {
      $status = FALSE;

      if ($header = $this->getFileMeta($filename)) {
        if (isset($header['expire']) && ($header['expire'] > $this->time || $header['expire'] == 0)) {
          // 更新写入文件 expire\r\n<?php die(); ?/>\r\n
          $data = serialize($header) ."\r\n<?php die(\"Access Denied\"); ?>#xxx#\r\n". serialize($data);
          @ftruncate($handle, 0);
          $status = @fwrite($handle, $data, strlen($data));
        }
      }

      @fclose($handle);

      return (bool)$status;
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub
    if ($value = $this->get($key)) {
      $value = is_numeric($value) ? intval($value) + $offset : $offset;

      return $this->updateData($key, $value) ? $value : FALSE;
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub
    if ($value = $this->get($key)) {
      $value = is_numeric($value) ? intval($value) - $offset : $offset;

      return $this->updateData($key, $value) ? $value : FALSE;
    }

    return FALSE;
  }


  private function _is_empty_dir($path) {
    if (is_dir($path) && $handle = opendir($path)) {
      $empty = TRUE;
      while ($empty && FALSE !== ($file = readdir($handle))) {
        if (!in_array($file, array('.', '..', '.svn', 'CVS', 'index.html')) && $file[0] != '.') {
          if (is_dir("$path/$file")) {
            $empty = $this->_is_empty_dir("$path/$file");
          }
          else if (preg_match('/.*\\.php$/', $file) && $this->check_expire($file)) {
            $empty = FALSE;
          }
        }
      }
      closedir($handle);

      return $empty;
    }

    return TRUE;
  }

  /**
   *
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub
    if (!$this->_is_empty_dir($this->path .'/'. $this->bin)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   *
   * @see CacheInterface::set()
   */
  public function set($key, $data, $lifetime = 0) {
    // TODO Auto-generated method stub

    // 在数据中追加头信息
    $filename = $this->getFilePath($key);
    $handle = @fopen($filename, 'wb');
    if ($handle) {
      if (!empty($this->secret)) {
        // 写入文件 expire\r\n<?php die(); ?/>\r\n
        $header = array();
        $header['expire'] = $lifetime > 0 ? $this->time + $lifetime : 0;
        $data = serialize($header) ."\r\n<?php die(\"Access Denied\"); ?>#xxx#\r\n". serialize($data);
      }

      @ftruncate($handle, 0);
      $status = @fwrite($handle, $data, strlen($data));
      @fclose($handle);

      return (bool)$status;
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::setMulti()
   */
  public function setMulti(array $items, $lifetime = 0) {
    // TODO Auto-generated method stub
    $status = FALSE;
    foreach ($items as $key => $data) {
      $status |= $this->set($key, $data, $lifetime);
    }

    return $status;
  }

}


/**
 * 数据库缓存
 *
 * @author realeff
 *
 */
class DatabaseCache extends AbstractCache implements CacheInterface {

  protected $querier;


  public function __construct($bin, array $options = array()) {
    parent::__construct($bin, $options);

    $this->querier = store_getquerier(isset($options['querier']) ? $options['querier'] : REALEFF_QUERIER_CACHE);
  }

  protected function escapeKey($key) {
    if (empty($key)) {
      return FALSE;
    }

    // 将key中含有“/\”字符转换为目录分隔符-，并清理字符 ? " ' % *
    return strtr($key, '/\\?"\'%* ', '--______');
  }

  /**
   *
   * @see CacheInterface::delete()
   */
  public function delete($key) {
    // TODO Auto-generated method stub
    $this->querier->do = 'remove';
    $this->querier->setParam('id', $this->escapeKey($key));

    return (boolean)$this->querier->execute();
  }

  /**
   *
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub
    $this->querier->do = 'flush';

    return (bool)$this->querier->execute();
  }

  /**
   *
   * @see CacheInterface::gc()
   */
  public function gc() {
    // TODO Auto-generated method stub
    $this->querier->do = 'gc';
    $this->querier->setParam('expire_0', $this->time);

    return (bool)$this->querier->execute();
  }

  /**
   *
   * @see CacheInterface::get()
   */
  public function get($key) {
    // TODO Auto-generated method stub
    $this->querier->do = 'get';
    $this->querier->setParam('id', $this->escapeKey($key))
                  ->setParam('expire_0', $this->time);

    $stmt = $this->querier->query();
    if ($cache = $stmt->fetchObject()) {
      return $cache->serialized ? unserialize($cache->data) : $cache->data;
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::getMulti()
   */
  public function getMulti(array $keys) {
    // TODO Auto-generated method stub
    if (empty($keys)) {
      return array();
    }

    $ids = array();
    foreach ($keys as $key) {
      $ids[$this->escapeKey($key)] = $key;
    }

    $items = array();
    $this->querier->do = 'getmulti';
    $this->querier->setParam('id', array_keys($ids))
                  ->setParam('expire_0', $this->time);

    $stmt = $this->querier->query();
    while ($cache = $stmt->fetchObject()) {
      $items[$ids[$cache->id]] = $cache->serialized ? @unserialize($cache->data) : $cache->data;
    }

    return $items;
  }

  /**
   *
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub
    $id = $this->escapeKey($key);

    $this->querier->do = 'increment';
    $this->querier->setParam('offset', $offset)
                  ->setParam('id', $id)
                  ->setParam('expire_0', $this->time);

    $this->querier->execute();
    if ($this->querier->affected_rows()) {
      $this->querier->do = 'get';
      $this->querier->setParam('id', $id)
                    ->setParam('expire_0', $this->time);

      return $this->querier->query()->fetchField();
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub
    $id = $this->escapeKey($key);

    $this->querier->do = 'decrement';
    $this->querier->setParam('offset', $offset)
                  ->setParam('id', $id)
                  ->setParam('expire_0', $this->time);

    $this->querier->execute();
    if ($this->querier->affected_rows()) {
      $this->querier->do = 'get';
      $this->querier->setParam('id', $id)
                    ->setParam('expire_0', $this->time);

      return $this->querier->query()->fetchField();
    }

    return FALSE;
  }

  /**
   *
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub
    $this->querier->do = 'count';
    $this->querier->setFilter('expire_0', $this->time, TRUE);

    if ($this->querier->query()->fetchField()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   *
   * @see CacheInterface::set()
   */
  public function set($key, $data, $lifetime = 0) {
    // TODO Auto-generated method stub
    $id = $this->escapeKey($key);

    $fields = array('created' => $this->time);
    $fields['expire'] = $lifetime > 0 ? $this->time + $lifetime : 0;
    if (is_string($data) || is_numeric($data) || is_int($data)
        || is_float($data) || is_bool($data)) {
      $fields['data'] = $data;
      $fields['serialized'] = FALSE;
    }
    else {
      $fields['data'] = serialize($data);
      $fields['serialized'] = TRUE;
    }

    $this->querier->do = 'set';
    $this->querier->setParam('id', $id);
    $this->querier->addParams($fields);
    $this->querier->addParams($fields);

    return (bool)$this->querier->execute();
  }

  /**
   *
   * @see CacheInterface::setMulti()
   */
  public function setMulti(array $items, $lifetime = 0) {
    // TODO Auto-generated method stub
    $status = FALSE;
    foreach ($items as $key => $data) {
      $status |= $this->set($key, $data, $lifetime);
    }

    return $status;
  }

}


/**
 * 获取指定元素缓存数据
 *
 * @param string $key
 * @param string $bin
 *
 * @return mixed
 */
function cache_get($key, $bin = CACHE_BIN_DEFAULT) {
  return Cache::getBin($bin)->get($key);
}

/**
 * 获取多个元素缓存数据
 *
 * @param array $keys
 * @param string $bin
 *
 * @return array
 */
function cache_get_multi(array $keys, $bin = CACHE_BIN_DEFAULT) {
  return Cache::getBin($bin)->getMulti($keys);
}

/**
 * 存储指定元素数据
 *
 * @param string $key
 * @param mixed $data
 * @param int $lifetime
 * @param string $bin
 *
 * @return boolean
 */
function cache_set($key, $data, $lifetime = 0, $bin = CACHE_BIN_DEFAULT) {
  return Cache::getBin($bin)->set($key, $data, $lifetime);
}

/**
 * 存储多个元素数据
 *
 * @param array $items
 * @param int $lifetime
 * @param string $bin
 *
 * @return boolean
 */
function cache_set_multi(array $items, $lifetime = 0, $bin = CACHE_BIN_DEFAULT) {
  return Cache::getBin($bin)->setMulti($items, $lifetime);
}

/**
 * 增加数值元素值
 *
 * @param string $key
 * @param int $offset
 * @param string $bin
 *
 * @return int
 */
function cache_increment($key, $offset = 1, $bin = CACHE_BIN_DEFAULT) {
  return Cache::getBin($bin)->increment($key, $offset);
}

/**
 * 减少数值元素值
 *
 * @param string $key
 * @param int $offset
 * @param string $bin
 */
function cache_decrement($key, $offset = 1, $bin = CACHE_BIN_DEFAULT) {
  return Cache::getBin($bin)->decrement($key, $offset);
}


function cache_clear_all($key = NULL, $bin = NULL) {
  $clear =& realeff_static(__FUNCTION__, array());

  if (isset($clear['all'])) {
    return TRUE;
  }

  if (empty($bin) && empty($key)) {
    Cache::flush();
    return $clear['all'] = TRUE;
  }
  else if (empty($bin)) {
    $bin = CACHE_BIN_DEFAULT;
  }

  $clear_id = $bin .'_'. $key;
  if (isset($clear[$clear_id])) {
    return TRUE;
  }

  $cache = Cache::getBin($bin);
  return $clear[$clear_id] = isset($key) ? $cache->delete($key) : $cache->flush();
}

