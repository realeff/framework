<?php

class MemcachedCache extends AbstractCache implements CacheInterface {
  
  /**
   * 此选项键映射到Memcached操作选项
   *
   * @var array
   */
  private $_options = array(
      'compression'          => Memcached::OPT_COMPRESSION,
      'serializer'           => Memcached::OPT_SERIALIZER,
      'hash'                 => Memcached::OPT_HASH,
      'distribution'         => Memcached::OPT_DISTRIBUTION,
      'buffer_writes'        => Memcached::OPT_BUFFER_WRITES,
      'libketama_compatible' => Memcached::OPT_LIBKETAMA_COMPATIBLE,
      'binary_protocol'      => Memcached::OPT_BINARY_PROTOCOL,
      'no_block'             => Memcached::OPT_NO_BLOCK,
      'tcp_nodelay'          => Memcached::OPT_TCP_NODELAY,
      'send_size'            => Memcached::OPT_SOCKET_SEND_SIZE,
      'recv_size'            => Memcached::OPT_SOCKET_RECV_SIZE,
      'connect_timeout'      => Memcached::OPT_CONNECT_TIMEOUT,
      'retry_timeout'        => Memcached::OPT_RETRY_TIMEOUT,
      'send_timeout'         => Memcached::OPT_SEND_TIMEOUT,
      'recv_timeout'         => Memcached::OPT_RECV_TIMEOUT,
      'poll_timeout'         => Memcached::OPT_POLL_TIMEOUT,
      'cache_lookups'        => Memcached::OPT_CACHE_LOOKUPS,
      'failure_limit'        => Memcached::OPT_SERVER_FAILURE_LIMIT
  );
  
  private $_option_defaults = array(
      'serializer' => array(
          Memcached::SERIALIZER_PHP,
          Memcached::SERIALIZER_IGBINARY,
          Memcached::SERIALIZER_JSON
      ),
      'hash' => array(
          Memcached::HASH_DEFAULT,
          Memcached::HASH_MD5,
          Memcached::HASH_CRC,
          Memcached::HASH_FNV1_64,
          Memcached::HASH_FNV1A_64,
          Memcached::HASH_FNV1_32,
          Memcached::HASH_FNV1A_32,
          Memcached::HASH_HSIEH,
          Memcached::HASH_MURMUR
      ),
      'distribution' => array(
          Memcached::DISTRIBUTION_MODULA,
          Memcached::DISTRIBUTION_CONSISTENT
      ),
  );
  
  protected $memcached;
  
  protected $prefix;
  
  
  public function __construct(array $bin, array $options = array()) {
    parent::__construct($bin, $options);
    
    // 指定数据键名前缀。
    $this->prefix = isset($options['prefix']) ? $options['prefix'] : substr($GLOBALS['auth_key'], 0, 8);
    
    // 连接选项
    $option = isset($options['option']) ? $options['option'] : array();

    // 服务器列表
    $servers = isset($options['server']) ? $options['server'] : array(array('host' => '127.0.0.1', 'port' => '11211'));
    //检查server选项，如果server数组中有host键名，则server包含一个服务器，否则包含一组服务器。
    if (isset($servers['host'])) {
      $servers = array($servers);
    }
    
    $this->memcached = new Memcached();
    foreach ($servers as $weight => $server) {
      $option['weight'] = $weight;
      
      $this->memcached->addServer($server['host'], $server['port'], $server['weight']);
    }
    // 设置键名前缀
    $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $this->prefix);
    
    foreach ($option as $key => $value) {
      if (isset($this->_options[$key])) {
        if (isset($this->_option_defaults[$key])) {
          $value =  in_array($value, $this->_option_defaults[$key]) ? $value : reset($this->_option_defaults[$key]);
        }
        
        $this->memcached->setOption($this->_options[$key], $value);
      }
    }
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::delete()
   */
  public function delete($key) {
    // TODO Auto-generated method stub
    return $this->memcached->delete($key);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::flush()
   */
  public function flush() {
    // TODO Auto-generated method stub
    return $this->memcached->flush();
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
    return $this->memcached->get($key);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::getMulti()
   */
  public function getMulti(array $keys) {
    // TODO Auto-generated method stub
    return $this->memcached->getMulti($keys);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::increment()
   */
  public function increment($key, $offset = 1) {
    // TODO Auto-generated method stub
    return $this->memcached->increment($key, (int)$offset);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::decrement()
   */
  public function decrement($key, $offset = 1) {
    // TODO Auto-generated method stub
    return $this->memcached->decrement($key, (int)$offset);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::isEmpty()
   */
  public function isEmpty() {
    // TODO Auto-generated method stub
    $status = TRUE;
    $stats = $this->memcached->getStats();
    foreach ($status as $stats) {
      $status &= intval($stats['curr_items']) > 0 ? FALSE : TRUE;
    }
    
    return $status;
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::set()
   */
  public function set($key, $data, $lifetime = 0) {
    // TODO Auto-generated method stub
    return $this->memcached->set($key, $data, $lifetime > 0 ? $this->time + $lifetime : 0);
  }

  /**
   * (non-PHPdoc)
   * @see CacheInterface::setMulti()
   */
  public function setMulti(array $items, $lifetime = 0) {
    // TODO Auto-generated method stub
    return $this->memcached->setMulti($items, $lifetime > 0 ? $this->time + $lifetime : 0);
  }

}