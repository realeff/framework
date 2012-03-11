<?php


class Cache {
  
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
   * @param array $bin 存储容器，每个存储器允许最多500个容器。
   * @param array $options 存储参数
   */
  public function __construct(array $bin, array $options = array());

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
   * @return bool
   *   操作成功返回TRUE，失败返回FALSE。
   */
  public function set($key, $data, $lifetime = 0);

  /**
   * 存储多个元素数据
   *
   * @param array $items 存放键/数据对数组
   * @param int $lifetime 生成期
   *
   * @return bool
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
   * @return bool
   *   成功时返回TRUE，或者在失败时返回FALSE。
   */
  public function delete($key);

  /**
   * 清除所有缓存数据
   *
   * @return bool
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
   * @return bool
   *   如果为空返回TRUE，否则返回FALSE。
   */
  public function isEmpty();
}


abstract class AbstractCache {
  
  /**
   * @var array
   */
  protected $bin;
  
  protected $time;
  
  public function __construct(array $bin) {
    // 缓存容器
    $this->bin = $bin;
    
    $this->time = $_SERVER['REQUEST_TIME'];
  }
  
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
  
  protected $lifetime;
  
  public function __construct(array $bin, array $options = array()) {
    parent::__construct($bin);
    
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
    $this->lifetime = isset($options['lifetime']) ? (int)$options['lifetime'] : 0;
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
    $key = strtr($key, '/\\?"\'<>*|: ', '--_________');
    
    $filepath = $this->path;
    $path = strtok($key, '-');
    if (!in_array($path, $this->bin)) {
      $path = reset($this->bin);
    }
    
    while ($path !== FALSE) {
      if ($path) {
        // 检查文件目录
        $this->check_dir($filepath);
        // 追加文件名
        $filepath .= '/'. $path;
      }
      
      $path = strtok('-');
    }
    
    if (empty($this->secret)) {
      return $filepath .'.html';
    }
    else {
      $filename = $this->secret .'-cache-'. basename($filepath);
      
      return dirname($filepath) .'/'. md5($filename) .'.php';
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
   * @param bool $gc
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
   * (non-PHPdoc)
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
   * (non-PHPdoc)
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub
    $status = FALSE;
    
    foreach ($this->bin as $bin) {
      $path = $this->path .'/'. $bin;
      
      if (is_writable($path)) {
        $this->clear_dir($path, FALSE);
        
        $status = TRUE;
      }
    }
    
    return $status;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::gc()
   */
  public function gc() {
    // TODO Auto-generated method stub
    $status = FALSE;
    
    foreach ($this->bin as $bin) {
      $path = $this->path .'/'. $bin;
      
      if (is_writable($path)) {
        $this->clear_dir($path, TRUE);
        
        $status = TRUE;
      }
    }
    
    return $status;
  }

  /**
   * (non-PHPdoc)
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
   * (non-PHPdoc)
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

  /**
   * (non-PHPdoc)
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub
    $value = $this->get($key);
    $value = is_numeric($value) ? intval($value) + $offset : $offset;
    
    return $this->set($key, $value) ? $value : FALSE;
  }
  
  /**
   * (non-PHPdoc)
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub
    $value = $this->get($key);
    $value = is_numeric($value) ? intval($value) - $offset : $offset;
    
    return $this->set($key, $value) ? $value : FALSE;
  }
  
  
  private function _is_empty_dir($path) {
    if (is_dir($path) && $handle = opendir($path)) {
      $empty = TRUE;
      while ($empty && FALSE !== ($file = readdir($handle))) {
        if (!in_array($file, array('.', '..', '.svn', 'CVS', 'index.html')) && $file[0] != '.') {
          if (is_dir("$path/$file")) {
            $empty = $this->_is_empty_dir("$path/$file");
          }
          else if (preg_match('/.*\\.php$/', $file) && is_file($file)) {
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
   * (non-PHPdoc)
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub
    foreach ($this->bin as $bin) {
      if (!$this->_is_empty_dir($this->path .'/'. $bin)) {
        return FALSE;
      };
    }
    
    return TRUE;
  }

  /**
   * (non-PHPdoc)
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
   * (non-PHPdoc)
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
  
  protected $db;
  
  protected $lifetime;
  
  
  public function __construct(array $bin, array $options = array()) {
    parent::__construct($bin);
    
    $this->db = store_get_querier(isset($options['querier']) ? $options['querier'] : REALEFF_QUERIER_CACHE);
    
    $this->lifetime = isset($options['lifetime']) ? (int)$options['lifetime'] : 0;
  }
  
  
  protected function cutBinKey($key) {
    if (empty($key)) {
      return FALSE;
    }
    
    // 将key中含有“/\”字符转换为目录分隔符-，并清理字符 ? " ' % * 
    $key = strtr($key, '/\\?"\'%* ', '--______');
    $pieces = explode('-', $key, 2);
    if (empty($pieces)) {
      $pieces = array(reset($this->bin), $key);
    }
    else if (count($pieces) == 1) {
      array_unshift($pieces, reset($this->bin));
    }
    else if (!in_array($pieces[0], $this->bin)) {
      $pieces[0] = reset($this->bin);
    }
    
    return $pieces;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::delete()
   */
  public function delete($key) {
    // TODO Auto-generated method stub
    list($bin, $cid) = $this->cutBinKey($key);
    
    if (isset($bin)) {
      $this->db->clear();
      $this->db->delete($bin)
               ->where()->compare('cid', $cid)->end()
               ->end();
      
      $this->db->addFilter('remove-cid');
      return $this->db->execute();
    }
    
    return FALSE;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub
    $status = FALSE;
    
    foreach ($this->bin as $bin) {
      $this->db->clear();
      $this->db->delete($bin)->end();
      
      $this->db->addFilter('flush');
      $this->db->execute();
      
      $status = TRUE;
    }
    
    return $status;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::gc()
   */
  public function gc() {
    // TODO Auto-generated method stub
    $status = FALSE;
    
    foreach ($this->bin as $bin) {
      $this->db->clear();
      $this->db->delete($bin)->where()
               ->compare('expire', 0, QueryCondition::NOT_EQUAL)
               ->compare('expire', $this->time, QueryCondition::LESS)->end()
               ->end();
      
      $this->db->addFilter('gc');
      $this->db->execute();
      
      $status = TRUE;
    }
    
    return $status;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::get()
   */
  public function get($key) {
    // TODO Auto-generated method stub
    list($bin, $cid) = $this->cutBinKey($key);
    if (isset($bin)) {
      $this->db->clear();
      $this->db->select($bin)
               ->field('data')->field('serialized')
               ->where()->compare('cid', $cid)
               ->add()->compare('expire', 0)
               ->_OR()
               ->compare('expire', $this->time, QueryCondition::GREATER_EQUAL)->end()
               ->end();
      
      $this->db->addFilter('serialize-data');
      $stmt = $this->db->query();
      if ($cache = $stmt->fetchObject()) {
        return $cache->serialized ? unserialize($cache->data) : $cache->data;
      }
    }
    
    return FALSE;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::getMulti()
   */
  public function getMulti(array $keys) {
    // TODO Auto-generated method stub
    $tkeys = array();
    foreach ($keys as $key) {
      list($bin, $cid) = $this->cutBinKey($key);
      if (isset($bin)) {
        $tkeys[$bin][$cid] = $key;
      }
    }
    
    $items = array();
    foreach ($tkeys as $bin => $cids) {
      $this->db->clear();
      $this->db->select($bin)
               ->field(array('cid', 'data', 'serialized'))
               ->where()->contain('cid', array_keys($cids))
               ->add()->compare('expire', 0)
               ->_OR()
               ->compare('expire', $this->time, QueryCondition::GREATER_EQUAL)->end()
               ->end();
      
      $this->db->addFilter('serialize-data');
      $stmt = $this->db->query();
      while ($cache = $stmt->fetchObject()) {
        $items[$cids[$cache->cid]] = $cache->serialized ? @unserialize($cache->data) : $cache->data;
      }
    }
    
    return $items;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub
    list($bin, $cid) = $this->cutBinKey($key);
    if (isset($bin)) {
      $this->db->clear();
      $this->db->update($bin)
               ->expression('data', 'data + :offset', array('offset' => $offset))
               ->field('serialized', FALSE)->field('expire', 0)
               ->where()->compare('cid', $cid)->end()
               ->end();
      
      $this->db->addFilter('increment');
      $this->db->execute();
      if ($this->db->affected_rows()) {
        $this->db->clear();
        $this->db->select($bin)->field('data')
                 ->where()->compare('cid', $cid)->end()
                 ->end();
        
        $this->db->addFilter('data');
        return $this->db->query()->fetchField();
      }
    }
    
    return FALSE;
  }
  
  /**
   * (non-PHPdoc)
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub
    list($bin, $cid) = $this->cutBinKey($key);
    if (isset($bin)) {
      $this->db->clear();
      $this->db->update($bin)
               ->expression('data', 'data - :offset', array('offset' => $offset))
               ->field('serialized', FALSE)->field('expire', 0)
               ->where()->compare('cid', $cid)->end()
               ->end();
      
      $this->db->addFilter('decrement');
      $this->db->execute();
      if ($this->db->affected_rows()) {
        $this->db->clear();
        $this->db->select($bin)->field('data')
                 ->where()->compare('cid', $cid)->end()
                 ->end();
        
        $this->db->addFilter('data');
        return $this->db->query()->fetchField();
      }
    }
    
    return FALSE;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub
    foreach ($this->bin as $bin) {
      $this->db->clear();
      $this->db->select($bin)->forCount();
      
      $this->db->addFilter('isempty');
      if ($this->db->query()->fetchField()) {
        return FALSE;
      }
    }
    
    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::set()
   */
  public function set($key, $data, $lifetime = 0) {
    // TODO Auto-generated method stub
    list($bin, $cid) = $this->cutBinKey($key);
    if (isset($bin)) {
      $fields = array('created' => $this->time);
      $fields['expire'] = $lifetime > 0 ? $this->time + $lifetime : 0;
      if (is_string($data)) {
        $fields['data'] = $data;
        $fields['serialized'] = FALSE;
      }
      else {
        $fields['data'] = serialize($data);
        $fields['serialized'] = TRUE;
      }
      
      $this->db->clear();
      $this->db->insert_unique($bin)
               ->key('cid', $cid)->fields($fields)
               ->end();
      return (bool)$this->db->execute();
    }
    
    return FALSE;
  }

  /**
   * (non-PHPdoc)
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



function cache_get($key, $bin = 'cache') {
  //$cache = new FileCache(array('data'));
  $cache = new DatabaseCache(array('cache'));
  
  return $cache->get($bin .'-'. $key);
}

function cache_get_multi(array $keys, $bin = 'cache') {
  
}

function cache_set($key, $data, $lifetime = 0, $bin = 'cache') {
  //$cache = new FileCache(array('data'));
  $cache = new DatabaseCache(array('cache'));
  
  return $cache->set($bin .'-'. $key, $data, $lifetime);
}

function cache_set_multi(array $items, $lifetime = 0, $bin = 'cache') {
  
}

function cache_clear_all($key = NULL, $bin = NULL) {
  $clears =& realeff_static(__FUNCTION__, array());
  
  $clear_id = $bin .'_'. $key;
  if (isset($clears[$clear_id])) {
    return TRUE;
  }
  
  //$cache = new FileCache(array('data'));
  $cache = new DatabaseCache(array('cache'));
  if (isset($bin) && isset($key)) {
    $clears[$clear_id] = $cache->delete($key);
  }
  else {
    $clears[$clear_id] = $cache->flush();
  }
}

