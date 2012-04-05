<?php
defined('CACHE_DRIVER_PATH') or die;

class MemcacheCache extends AbstractCache implements CacheInterface {
  
  protected $memcache;
  
  protected $compress = 0;
  
  public function __construct($bin, array $options = array()) {
    parent::__construct($bin, $options);
    
    // 连接选项
    $option = isset($options['option']) ? $options['option'] : array();
    $option += array(
      'saving' => 0.2,
      'persistent' => TRUE,
      'timeout' => 1,
      'retry_interval' => 15,
      'status' => TRUE
    );
    // 服务器列表
    $servers = isset($options['server']) ? $options['server'] : array(array('host' => '127.0.0.1', 'port' => '11211'));
    //检查server选项，如果server数组中有host键名，则server包含一个服务器，否则包含一组服务器。
    if (isset($servers['host'])) {
      $servers = array($servers);
    }
    
    $this->memcache = new Memcache;
    foreach ($servers as $weight => $server) {
      $server = array_merge($option, $server);
      empty($server['weight']) && ($server['weight'] =  $weight + 1);
      
      $this->memcache->addServer($server['host'], $server['port'], $server['persistent'],
          $server['weight'], $server['timeout'], $server['retry_interval'], $server['status']);
    }
    
    if (isset($option['compress'])) {
      // 开启对于大数据的自动压缩
      $this->compress = $option['compress'] === FALSE ? 0 : MEMCACHE_COMPRESSED;
      $this->memcache->setCompressThreshold($option['compress'], $option['saving']);
    }
  }
  
  protected function prefixKey($key) {
    if (empty($key)) {
      return FALSE;
    }
    
    // 将key中含有“/\”字符转换为目录分隔符-，并清理字符 ? " ' % * 
    return $this->bin .'-'. strtr($key, '/\\', '--');
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::delete()
   */
  public function delete($key) {
    // TODO Auto-generated method stub
    $key = $this->prefixKey($key);
    
    return $this->memcache->delete($key);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub
    return $this->memcache->flush();
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::gc()
   */
  public function gc() {
    // TODO Auto-generated method stub
    return TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::get()
   */
  public function get($key) {
    // TODO Auto-generated method stub
    $key = $this->prefixKey($key);
    
    return $this->memcache->get($key);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::getMulti()
   */
  public function getMulti(array $keys) {
    // TODO Auto-generated method stub
    $mkeys = array();
    foreach ($keys as $key) {
      $mkeys[$this->prefixKey($key)] = $key;
    }

    if ($items = $this->memcache->get(array_keys($mkeys))) {
      $kitems = array();
      foreach ($items as $key => $value) {
        $kitems[$mkeys[$key]] = $value;
      }
      
      return $kitems;
    }
    
    return $items;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub
    $key = $this->prefixKey($key);
    
    return $this->memcache->increment($key, (int)$offset);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub
    $key = $this->prefixKey($key);
    
    return $this->memcache->decrement($key, (int)$offset);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub
    $status = $this->memcache->getStats();
    
    return $status && intval($status['curr_items']) > 0 ? FALSE : TRUE;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::set()
   */
  public function set($key, $data, $lifetime = 0) {
    // TODO Auto-generated method stub
    $key = $this->prefixKey($key);
    
    return $this->memcache->set($key, $data, $this->compress, $lifetime > 0 ? $this->time + $lifetime : 0);
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